<?php

namespace App\Console\Commands;

use App\Models\Frozen_order;
use App\Models\OrderStatusTiming;
use App\Services\OrderStatusService;
use App\Jobs\PrepareOrder;
use App\Jobs\TransitOrder;
use App\Jobs\ShipOrder;
use App\Jobs\DeliverOrder;
use App\Jobs\CompleteOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoProcessOrderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-process-status {--sync : Chạy jobs ngay lập tức thay vì dispatch vào queue (dùng cho test)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động chuyển trạng thái đơn hàng dựa trên thời gian đã trôi qua';

    /**
     * Cache cho các cấu hình thời gian để tránh query nhiều lần
     */
    private $timingCache = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu xử lý tự động chuyển trạng thái đơn hàng...');
        
        // Load tất cả cấu hình vào cache
        $this->loadTimingCache();
        
        $processedCount = 0;
        
        // 1. Xử lý đơn hàng từ confirmed → preparing
        $processedCount += $this->processConfirmedOrders();
        
        // 2. Xử lý đơn hàng từ preparing → transit
        $processedCount += $this->processPreparingOrders();
        
        // 3. Xử lý đơn hàng từ transit → shipping
        $processedCount += $this->processTransitOrders();
        
        // 4. Xử lý đơn hàng từ shipping → delivered
        $processedCount += $this->processShippingOrders();
        
        // 5. Xử lý đơn hàng từ delivered → completed
        $processedCount += $this->processDeliveredOrders();
        
        $this->info("Đã xử lý {$processedCount} đơn hàng.");
        
        return Command::SUCCESS;
    }
    
    /**
     * Load tất cả cấu hình thời gian vào cache
     */
    private function loadTimingCache(): void
    {
        try {
            $timings = OrderStatusTiming::where('is_active', 1)->get();
            foreach ($timings as $timing) {
                $key = $timing->from_status . '_' . $timing->to_status;
                $this->timingCache[$key] = $timing;
            }
            $this->line("Đã load " . count($this->timingCache) . " cấu hình thời gian vào cache.");
        } catch (\Exception $e) {
            $this->warn("Không thể load cấu hình thời gian: " . $e->getMessage());
            Log::error('Lỗi khi load timing cache', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Lấy cấu hình thời gian từ cache
     */
    private function getTiming(string $fromStatus, string $toStatus): ?OrderStatusTiming
    {
        $key = $fromStatus . '_' . $toStatus;
        return $this->timingCache[$key] ?? null;
    }
    
    /**
     * Xử lý đơn hàng từ confirmed → preparing
     * Thời gian lấy từ cấu hình
     */
    private function processConfirmedOrders(): int
    {
        $count = 0;
        $timing = $this->getTiming('confirmed', 'preparing');
        
        if (!$timing) {
            return $count;
        }
        
        $minMinutes = $timing->getMinTimeInMinutes();
        
        // Lấy các đơn hàng đã confirmed và đã qua thời gian tối thiểu
        $orders = Frozen_order::where('status', 'confirmed')
            ->whereNotNull('confirmed_at')
            ->where('confirmed_at', '<=', now()->subMinutes($minMinutes))
            ->get();
        
        foreach ($orders as $order) {
            $minutesSinceConfirmed = $order->confirmed_at->diffInMinutes(now());
            
            // Nếu đã qua thời gian tối thiểu, dispatch job ngay
            if ($minutesSinceConfirmed >= $minMinutes) {
                try {
                    $job = new PrepareOrder($order->id);
                    if ($this->option('sync')) {
                        // Chạy ngay lập tức (cho test)
                        $job->handle();
                        $this->line("  ✓ Đơn hàng #{$order->id}: confirmed → preparing (đã qua {$minutesSinceConfirmed} phút) - ĐÃ XỬ LÝ");
                    } else {
                        // Dispatch vào queue (production)
                        PrepareOrder::dispatch($order->id);
                        $this->line("  ✓ Đơn hàng #{$order->id}: confirmed → preparing (đã qua {$minutesSinceConfirmed} phút) - ĐÃ DISPATCH");
                    }
                    $count++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Lỗi khi xử lý đơn hàng #{$order->id}: " . $e->getMessage());
                    Log::error('Lỗi khi dispatch PrepareOrder', [
                        'frozen_order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Xử lý đơn hàng từ preparing → transit
     * Thời gian lấy từ cấu hình
     */
    private function processPreparingOrders(): int
    {
        $count = 0;
        $timing = $this->getTiming('preparing', 'transit');
        
        if (!$timing) {
            return $count;
        }
        
        $minMinutes = $timing->getMinTimeInMinutes();
        
        // Lấy các đơn hàng đã preparing và đã qua thời gian tối thiểu
        $orders = Frozen_order::where('status', 'preparing')
            ->whereNotNull('preparing_at')
            ->where('preparing_at', '<=', now()->subMinutes($minMinutes))
            ->get();
        
        foreach ($orders as $order) {
            $minutesSincePreparing = $order->preparing_at->diffInMinutes(now());
            
            // Nếu đã qua thời gian tối thiểu, dispatch job ngay
            if ($minutesSincePreparing >= $minMinutes) {
                try {
                    $job = new TransitOrder($order->id);
                    if ($this->option('sync')) {
                        $job->handle();
                        $hours = round($minutesSincePreparing / 60, 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: preparing → transit (đã qua {$hours} giờ) - ĐÃ XỬ LÝ");
                    } else {
                        TransitOrder::dispatch($order->id);
                        $hours = round($minutesSincePreparing / 60, 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: preparing → transit (đã qua {$hours} giờ) - ĐÃ DISPATCH");
                    }
                    $count++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Lỗi khi xử lý đơn hàng #{$order->id}: " . $e->getMessage());
                    Log::error('Lỗi khi dispatch TransitOrder', [
                        'frozen_order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Xử lý đơn hàng từ transit → shipping
     * Thời gian lấy từ cấu hình
     */
    private function processTransitOrders(): int
    {
        $count = 0;
        $timing = $this->getTiming('transit', 'shipping');
        
        if (!$timing) {
            return $count;
        }
        
        $minMinutes = $timing->getMinTimeInMinutes();
        
        // Lấy các đơn hàng đã transit và đã qua thời gian tối thiểu
        $orders = Frozen_order::where('status', 'transit')
            ->whereNotNull('transit_at')
            ->where('transit_at', '<=', now()->subMinutes($minMinutes))
            ->get();
        
        foreach ($orders as $order) {
            $minutesSinceTransit = $order->transit_at->diffInMinutes(now());
            
            // Nếu đã qua thời gian tối thiểu, dispatch job ngay
            if ($minutesSinceTransit >= $minMinutes) {
                try {
                    $job = new ShipOrder($order->id);
                    if ($this->option('sync')) {
                        $job->handle();
                        $hours = round($minutesSinceTransit / 60, 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: transit → shipping (đã qua {$hours} giờ) - ĐÃ XỬ LÝ");
                    } else {
                        ShipOrder::dispatch($order->id);
                        $hours = round($minutesSinceTransit / 60, 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: transit → shipping (đã qua {$hours} giờ) - ĐÃ DISPATCH");
                    }
                    $count++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Lỗi khi xử lý đơn hàng #{$order->id}: " . $e->getMessage());
                    Log::error('Lỗi khi dispatch ShipOrder', [
                        'frozen_order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Xử lý đơn hàng từ shipping → delivered
     * Thời gian lấy từ cấu hình
     */
    private function processShippingOrders(): int
    {
        $count = 0;
        $timing = $this->getTiming('shipping', 'delivered');
        
        if (!$timing) {
            return $count;
        }
        
        $minMinutes = $timing->getMinTimeInMinutes();
        
        // Lấy các đơn hàng đã shipping và đã qua thời gian tối thiểu
        $orders = Frozen_order::where('status', 'shipping')
            ->whereNotNull('shipping_at')
            ->where('shipping_at', '<=', now()->subMinutes($minMinutes))
            ->get();
        
        foreach ($orders as $order) {
            $minutesSinceShipping = $order->shipping_at->diffInMinutes(now());
            
            // Nếu đã qua thời gian tối thiểu, dispatch job ngay
            if ($minutesSinceShipping >= $minMinutes) {
                try {
                    $job = new DeliverOrder($order->id);
                    if ($this->option('sync')) {
                        $job->handle();
                        $hours = round($minutesSinceShipping / 60, 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: shipping → delivered (đã qua {$hours} giờ) - ĐÃ XỬ LÝ");
                    } else {
                        DeliverOrder::dispatch($order->id);
                        $hours = round($minutesSinceShipping / 60, 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: shipping → delivered (đã qua {$hours} giờ) - ĐÃ DISPATCH");
                    }
                    $count++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Lỗi khi xử lý đơn hàng #{$order->id}: " . $e->getMessage());
                    Log::error('Lỗi khi dispatch DeliverOrder', [
                        'frozen_order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Xử lý đơn hàng từ delivered → completed
     * Thời gian lấy từ cấu hình
     */
    private function processDeliveredOrders(): int
    {
        $count = 0;
        $timing = $this->getTiming('delivered', 'completed');
        
        if (!$timing) {
            return $count;
        }
        
        $minMinutes = $timing->getMinTimeInMinutes();
        
        // Lấy các đơn hàng đã delivered và đã qua thời gian tối thiểu
        $orders = Frozen_order::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', now()->subMinutes($minMinutes))
            ->get();
        
        foreach ($orders as $order) {
            $minutesSinceDelivered = $order->delivered_at->diffInMinutes(now());
            
            // Nếu đã qua thời gian tối thiểu, dispatch job ngay
            if ($minutesSinceDelivered >= $minMinutes) {
                try {
                    $job = new CompleteOrder($order->id);
                    if ($this->option('sync')) {
                        // Chạy ngay lập tức (cho test) - BỎ QUA DELAY
                        $job->handle();
                        $days = round($minutesSinceDelivered / (24 * 60), 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: delivered → completed (đã qua {$days} ngày) - ĐÃ XỬ LÝ");
                    } else {
                        CompleteOrder::dispatch($order->id);
                        $days = round($minutesSinceDelivered / (24 * 60), 1);
                        $this->line("  ✓ Đơn hàng #{$order->id}: delivered → completed (đã qua {$days} ngày) - ĐÃ DISPATCH");
                    }
                    $count++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Lỗi khi xử lý đơn hàng #{$order->id}: " . $e->getMessage());
                    Log::error('Lỗi khi dispatch CompleteOrder', [
                        'frozen_order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $count;
    }
}
