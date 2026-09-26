<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Rank;
use App\Models\Partner;
use App\Services\OrderStatusService;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $status = request()->input('status');
        $rank = request()->input('rank');
        $list_ranks = Rank::withCount('orders')->get();
        
        // Kiểm tra nếu là request AJAX (có tham số status hoặc rank trong query string)
        // hoặc nếu cả hai đều rỗng nhưng vẫn có request (JavaScript sẽ gửi cả hai là "")
        if (request()->has('status') || request()->has('rank')) {
            $query = Order::query();
            if ($status == "0" || $status == "1") {
                $query->where('status', $status);
            }
            if ($rank !== "all" && $rank != "") {
                $query->where('rank_id', $rank);
            }
            $list_orders = $query->with(['partner', 'rank'])->get();
            $response = [
                'status' => 200,
                'message' => 'Lấy dữ liệu thành công!',
                'data' => $list_orders
            ];
            return response()->json($response);
        } else {
            // Load trang lần đầu
            // Chỉ cần đếm tổng số đơn hàng để hiển thị trong nút "Tất cả"
            // Dữ liệu sẽ được load qua JavaScript
            $total_orders_count = Order::count();
            $active_orders_count = Order::where('status', '1')->count();
            $inactive_orders_count = Order::where('status', '0')->count();
            $total_orders_value = Order::sum('price');
            return view('admin.order.index', compact(
                'list_ranks',
                'total_orders_count',
                'active_orders_count',
                'inactive_orders_count',
                'total_orders_value'
            ));
        }
    }
    public function changeStatusOrder(Order $order)
    {
        if ($order) {
            $order->status = $order->status == "1" ? "0" : "1";
            $order->save();
            if ($order->status == "1") {
                return redirect()->route('order.index')->with('success', 'Mở khóa đơn hàng thành công!');
            } else {
                return redirect()->route('order.index')->with('success', 'Khóa đơn hàng thành công!');
            }
        } else {
            return redirect()->route('order.index')->with('error', 'Không tìm thấy đơn hàng cần thay đổi trạng thái!');
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $get_all_ranks = Rank::get();
        $list_ranks = [];
        foreach ($get_all_ranks as $item) {
            $rank_item = [];
            $get_orders_by_vip = Order::where('rank_id', $item->id)->get();
            $sum_price = 0;
            if ($get_orders_by_vip) {
                foreach ($get_orders_by_vip as $order_item) {
                    $sum_price += $order_item->price;
                }
            }
            $rank_item['id'] = $item->id;
            $rank_item['name'] = $item->name;
            $rank_item['value'] = $item->value - $sum_price;
            $rank_item['commission_percentage'] = $item->commission_percentage;
            $rank_item['spin_count'] = $item->spin_count;
            $rank_item['quantity'] = $item->spin_count - count($get_orders_by_vip);
            $rank_item['start'] = count($get_orders_by_vip);
            $list_ranks[] = $rank_item;
        }
        return view('admin.order.create', compact('list_ranks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $list_orders = $request->input('orders');
        $rank_id = $request->input('rank_id');
        $commission_percentage = Rank::find($rank_id);
        if ($commission_percentage) {
            $commission_percentage = $commission_percentage->commission_percentage;
        } else {
            $response = [
                'status' => 400,
                'mess' => "Tạo đơn hàng ko thành công!",
                'data' => $commission_percentage
            ];
            return response()->json($response);
        }
        foreach ($list_orders as $index => $item) {
            $file = $request->file("orders.$index.image");
            $fileName = "";
            if ($file && $file->isValid()) {
                $fileName = $file->store('uploads/images/orders', 'public');
            }
            
            // Random thông tin khách hàng
            $customerInfo = $this->generateRandomCustomerInfo();
            
            // Tạo mã đơn hàng và dùng làm tên đơn hàng
            $orderCode = $this->generateUniqueOrderCode();
            
            $new_order = Order::create([
                'order_code' => $orderCode,
                'index' => $item['index'],
                'name' => $orderCode, // Tự động dùng order_code làm tên đơn hàng
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'image' => $fileName,
                'rank_id' => $rank_id,
                'commission_percentage' => $commission_percentage,
                'customer_name' => $customerInfo['name'],
                'customer_phone' => $customerInfo['phone'],
                'customer_address' => $customerInfo['address'],
                'customer_note' => $customerInfo['note'],
                'is_paid' => $customerInfo['is_paid'],
                'payment_method' => $customerInfo['payment_method'],
                'partner_id' => $customerInfo['partner_id'],
                'api' => $this->generateApiString()
            ]);
            if (!$new_order) {
                $response = [
                    'status' => 400,
                    'mess' => "Tạo đơn hàng thứ $index không thành công!"
                ];
                return response()->json($response);
            }
        }
        $response = [
            'status' => 200,
            'mess' => "Tạo đơn hàng thành công!",
            'redirect_url' => route('order.index')
        ];
        return response()->json($response);
    }
    
    /**
     * Tạo thông tin khách hàng ngẫu nhiên theo quốc tịch
     */
    private function generateRandomCustomerInfo()
    {
        $nationalities = ['vietnamese', 'american', 'chinese', 'japanese'];
        $nationality = $nationalities[array_rand($nationalities)];
        
        $info = [];
        
        switch ($nationality) {
            case 'vietnamese':
                $info = $this->generateVietnameseCustomer();
                break;
            case 'american':
                $info = $this->generateAmericanCustomer();
                break;
            case 'chinese':
                $info = $this->generateChineseCustomer();
                break;
            case 'japanese':
                $info = $this->generateJapaneseCustomer();
                break;
        }
        
        return $info;
    }
    
    /**
     * Tạo thông tin khách hàng Việt Nam
     */
    private function generateVietnameseCustomer()
    {
        $firstNames = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'];
        $middleNames = ['Văn', 'Thị', 'Đức', 'Minh', 'Thanh', 'Hữu', 'Công', 'Quang', 'Đình', 'Xuân'];
        $lastNames = ['An', 'Bình', 'Cường', 'Dũng', 'Giang', 'Hải', 'Hùng', 'Khoa', 'Long', 'Mạnh', 'Nam', 'Phong', 'Quang', 'Sơn', 'Thành', 'Tuấn', 'Vinh', 'Yến', 'Linh', 'Hương', 'Lan', 'Mai', 'Nga', 'Oanh', 'Phương', 'Quỳnh', 'Thảo', 'Uyên', 'Vy', 'Xoan'];
        
        $firstName = $firstNames[array_rand($firstNames)];
        $middleName = $middleNames[array_rand($middleNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $name = "$firstName $middleName $lastName";
        
        // Số điện thoại Việt Nam: +84 9xxxxxxxx
        $phone = '+84 ' . rand(3, 9) . rand(10000000, 99999999);
        
        $streets = ['Nguyễn Trãi', 'Lê Lợi', 'Trần Hưng Đạo', 'Hoàng Diệu', 'Lý Thường Kiệt', 'Hai Bà Trưng', 'Lê Duẩn', 'Nguyễn Du', 'Phạm Văn Đồng', 'Võ Văn Tần'];
        $wards = ['Phường 1', 'Phường 2', 'Phường 3', 'Phường 4', 'Phường 5', 'Phường 6', 'Phường 7', 'Phường 8', 'Phường 9', 'Phường 10'];
        $districts = ['Quận 1', 'Quận 2', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 7', 'Quận 8', 'Quận 9', 'Quận 10', 'Quận 11', 'Quận 12', 'Quận Bình Thạnh', 'Quận Tân Bình', 'Quận Tân Phú', 'Quận Phú Nhuận'];
        $cities = ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu'];
        
        $address = 'Số ' . rand(1, 999) . ', Đường ' . $streets[array_rand($streets)] . ', ' . $wards[array_rand($wards)] . ', ' . $districts[array_rand($districts)] . ', ' . $cities[array_rand($cities)];
        
        $notes = [
            'Vui lòng giao hàng vào buổi sáng',
            'Giao hàng nhanh giúp tôi',
            'Cẩn thận khi giao hàng',
            'Liên hệ trước khi giao',
            'Giao hàng vào cuối tuần',
            '',
            '',
            '' // 50% không có ghi chú
        ];
        $note = $notes[array_rand($notes)];
        
        $paymentMethods = ['COD', 'vnpay', 'momo', 'bank_transfer'];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        
        // Random partner_id từ bảng partners (100% có partner)
        $partnerIds = Partner::pluck('id')->toArray();
        $partnerId = !empty($partnerIds) ? $partnerIds[array_rand($partnerIds)] : null; // 100% có partner nếu có dữ liệu
        
        // COD không được là trạng thái đã thanh toán
        $isPaid = $paymentMethod === 'COD' ? false : (rand(0, 1) == 1);
        
        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'note' => $note,
            'is_paid' => $isPaid,
            'payment_method' => $paymentMethod,
            'partner_id' => $partnerId
        ];
    }
    
    /**
     * Tạo thông tin khách hàng Mỹ
     */
    private function generateAmericanCustomer()
    {
        $firstNames = ['James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph', 'Thomas', 'Christopher', 'Daniel', 'Matthew', 'Anthony', 'Mark', 'Donald', 'Steven', 'Paul', 'Andrew', 'Joshua', 'Kenneth', 'Kevin', 'Brian', 'George', 'Timothy', 'Ronald', 'Edward', 'Jason', 'Jeffrey', 'Ryan', 'Jacob', 'Gary', 'Nicholas', 'Eric', 'Jonathan', 'Stephen', 'Larry', 'Justin', 'Scott', 'Brandon', 'Benjamin', 'Samuel', 'Frank', 'Gregory', 'Raymond', 'Alexander', 'Patrick', 'Jack', 'Dennis', 'Jerry', 'Tyler', 'Aaron', 'Jose', 'Henry', 'Adam', 'Douglas', 'Nathan', 'Zachary', 'Kyle', 'Noah', 'Ethan', 'Jeremy', 'Walter', 'Christian', 'Keith', 'Roger', 'Terry', 'Austin', 'Sean', 'Gerald', 'Carl', 'Dylan', 'Harold', 'Lawrence', 'Wayne', 'Roy', 'Ralph', 'Randy', 'Eugene', 'Vincent', 'Russell', 'Louis', 'Philip', 'Bobby', 'Johnny', 'Willie', 'Emily', 'Emma', 'Olivia', 'Ava', 'Isabella', 'Sophia', 'Charlotte', 'Mia', 'Amelia', 'Harper', 'Evelyn', 'Abigail', 'Emily', 'Elizabeth', 'Mila', 'Ella', 'Avery', 'Sofia', 'Camila', 'Aria', 'Scarlett', 'Victoria', 'Madison', 'Luna', 'Grace', 'Chloe', 'Penelope', 'Layla', 'Riley', 'Zoey', 'Nora', 'Lily', 'Eleanor', 'Hannah', 'Lillian', 'Addison', 'Aubrey', 'Ellie', 'Stella', 'Natalie', 'Zoe', 'Leah', 'Hazel', 'Violet', 'Aurora', 'Savannah', 'Audrey', 'Brooklyn', 'Bella', 'Claire', 'Skylar', 'Lucy', 'Paisley', 'Everly', 'Anna', 'Caroline', 'Nova', 'Genesis', 'Aaliyah', 'Kennedy', 'Kinsley', 'Allison', 'Maya', 'Sarah', 'Madelyn', 'Adeline', 'Alexa', 'Ariana', 'Elena', 'Gabriella', 'Alice', 'Naomi', 'Sadie', 'Hailey', 'Eva', 'Emilia', 'Autumn', 'Quinn', 'Nevaeh', 'Piper', 'Ruby', 'Serenity', 'Willow', 'Ivy', 'Lydia', 'Clara', 'Vivian', 'Aurora', 'Savannah', 'Audrey', 'Brooklyn', 'Bella', 'Claire', 'Skylar', 'Lucy', 'Paisley', 'Everly', 'Anna', 'Caroline', 'Nova', 'Genesis', 'Aaliyah', 'Kennedy', 'Kinsley', 'Allison', 'Maya', 'Sarah', 'Madelyn', 'Adeline', 'Alexa', 'Ariana', 'Elena', 'Gabriella', 'Alice', 'Naomi', 'Sadie', 'Hailey', 'Eva', 'Emilia', 'Autumn', 'Quinn', 'Nevaeh', 'Piper', 'Ruby', 'Serenity', 'Willow', 'Ivy', 'Lydia', 'Clara', 'Vivian'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts', 'Gomez', 'Phillips', 'Evans', 'Turner', 'Diaz', 'Parker', 'Cruz', 'Edwards', 'Collins', 'Reyes', 'Stewart', 'Morris', 'Morales', 'Murphy', 'Cook', 'Rogers', 'Gutierrez', 'Ortiz', 'Morgan', 'Cooper', 'Peterson', 'Bailey', 'Reed', 'Kelly', 'Howard', 'Ramos', 'Kim', 'Cox', 'Ward', 'Richardson', 'Watson', 'Brooks', 'Chavez', 'Wood', 'James', 'Bennett', 'Gray', 'Mendoza', 'Ruiz', 'Hughes', 'Price', 'Alvarez', 'Castillo', 'Sanders', 'Patel', 'Myers', 'Long', 'Ross', 'Foster', 'Jimenez'];
        
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $name = "$firstName $lastName";
        
        // Số điện thoại Mỹ: +1 (XXX) XXX-XXXX
        $areaCode = rand(200, 999);
        $exchange = rand(200, 999);
        $number = rand(1000, 9999);
        $phone = "+1 ($areaCode) $exchange-$number";
        
        $streetNumbers = rand(100, 9999);
        $streetNames = ['Main St', 'Oak Ave', 'Park Blvd', 'Maple Dr', 'Elm St', 'Cedar Ln', 'Pine Rd', 'First St', 'Second Ave', 'Washington Blvd', 'Lincoln Dr', 'Jefferson St', 'Madison Ave', 'Monroe St', 'Adams Ave'];
        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose', 'Austin', 'Jacksonville', 'San Francisco', 'Indianapolis', 'Columbus', 'Fort Worth', 'Charlotte', 'Seattle', 'Denver', 'Washington'];
        $states = ['NY', 'CA', 'IL', 'TX', 'AZ', 'PA', 'FL', 'IN', 'OH', 'NC', 'WA', 'CO', 'DC', 'MA', 'GA', 'MI', 'TN', 'OR', 'LA', 'MN'];
        $zipCode = rand(10000, 99999);
        
        $address = "$streetNumbers " . $streetNames[array_rand($streetNames)] . ", " . $cities[array_rand($cities)] . ", " . $states[array_rand($states)] . " $zipCode";
        
        $notes = [
            'Please deliver in the morning',
            'Fast delivery please',
            'Handle with care',
            'Call before delivery',
            'Weekend delivery preferred',
            '',
            '',
            ''
        ];
        $note = $notes[array_rand($notes)];
        
        // Không được dùng vnpay và momo
        $paymentMethods = ['COD', 'paypal', 'bank_transfer', 'other'];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        
        // Random partner_id từ bảng partners (100% có partner)
        $partnerIds = Partner::pluck('id')->toArray();
        $partnerId = !empty($partnerIds) ? $partnerIds[array_rand($partnerIds)] : null; // 100% có partner nếu có dữ liệu
        
        // COD không được là trạng thái đã thanh toán
        $isPaid = $paymentMethod === 'COD' ? false : (rand(0, 1) == 1);
        
        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'note' => $note,
            'is_paid' => $isPaid,
            'payment_method' => $paymentMethod,
            'partner_id' => $partnerId
        ];
    }
    
    /**
     * Tạo thông tin khách hàng Trung Quốc
     */
    private function generateChineseCustomer()
    {
        $firstNames = ['伟', '芳', '娜', '秀英', '敏', '静', '丽', '强', '磊', '军', '洋', '勇', '艳', '杰', '娟', '涛', '明', '超', '秀兰', '霞', '平', '刚', '桂英', '建华', '文', '华', '建国', '红', '桂芳', '国', '玉', '秀', '英', '梅', '辉', '勇', '艳', '杰', '娟', '涛', '明', '超', '秀兰', '霞', '平', '刚', '桂英', '建华', '文', '华', '建国', '红', '桂芳', '国', '玉', '秀', '英', '梅', '辉'];
        $lastNames = ['王', '李', '张', '刘', '陈', '杨', '赵', '黄', '周', '吴', '徐', '孙', '胡', '朱', '高', '林', '何', '郭', '马', '罗', '梁', '宋', '郑', '谢', '韩', '唐', '冯', '于', '董', '萧', '程', '曹', '袁', '邓', '许', '傅', '沈', '曾', '彭', '吕', '苏', '卢', '蒋', '蔡', '贾', '丁', '魏', '薛', '叶', '阎', '余', '潘', '杜', '戴', '夏', '锺', '汪', '田', '任', '姜', '范', '方', '石', '姚', '谭', '廖', '邹', '熊', '金', '陆', '郝', '孔', '白', '崔', '康', '毛', '邱', '秦', '江', '史', '顾', '侯', '邵', '孟', '龙', '万', '段', '雷', '钱', '汤', '尹', '黎', '易', '常', '武', '乔', '贺', '赖', '龚', '文'];
        
        $lastName = $lastNames[array_rand($lastNames)];
        $firstName = $firstNames[array_rand($firstNames)];
        $name = "$lastName$firstName";
        
        // Số điện thoại Trung Quốc: +86 1XX XXXX XXXX
        $phone = '+86 1' . rand(3, 9) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999);
        
        $provinces = ['北京市', '上海市', '天津市', '重庆市', '广东省', '江苏省', '浙江省', '山东省', '河南省', '四川省', '湖北省', '湖南省', '河北省', '安徽省', '福建省', '江西省', '辽宁省', '黑龙江省', '吉林省', '陕西省'];
        $cities = ['北京', '上海', '广州', '深圳', '杭州', '南京', '成都', '武汉', '西安', '重庆', '天津', '苏州', '长沙', '郑州', '东莞', '青岛', '沈阳', '宁波', '昆明', '大连'];
        $districts = ['朝阳区', '海淀区', '西城区', '东城区', '丰台区', '石景山区', '通州区', '昌平区', '大兴区', '房山区'];
        $streets = ['中关村大街', '王府井大街', '长安街', '建国门外大街', '复兴路', '西单北大街', '东单北大街', '三里屯路', '工体北路', '朝阳路'];
        
        $address = $provinces[array_rand($provinces)] . $cities[array_rand($cities)] . $districts[array_rand($districts)] . $streets[array_rand($streets)] . rand(1, 999) . '号';
        
        $notes = [
            '请早上送货',
            '请快速送货',
            '小心处理',
            '送货前请致电',
            '周末送货',
            '',
            '',
            ''
        ];
        $note = $notes[array_rand($notes)];
        
        // Không được dùng vnpay và momo
        $paymentMethods = ['COD', 'paypal', 'bank_transfer', 'other'];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        
        // Random partner_id từ bảng partners (100% có partner)
        $partnerIds = Partner::pluck('id')->toArray();
        $partnerId = !empty($partnerIds) ? $partnerIds[array_rand($partnerIds)] : null; // 100% có partner nếu có dữ liệu
        
        // COD không được là trạng thái đã thanh toán
        $isPaid = $paymentMethod === 'COD' ? false : (rand(0, 1) == 1);
        
        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'note' => $note,
            'is_paid' => $isPaid,
            'payment_method' => $paymentMethod,
            'partner_id' => $partnerId
        ];
    }
    
    /**
     * Tạo thông tin khách hàng Nhật Bản
     */
    private function generateJapaneseCustomer()
    {
        $firstNames = ['太郎', '花子', '一郎', '次郎', '三郎', '美咲', 'さくら', 'あかり', 'みゆき', 'ゆき', 'あや', 'まな', 'はるか', 'みなみ', 'あい', 'ひなた', 'りん', 'えみ', 'なお', 'みき', 'あき', 'みほ', 'みゆ', 'あやか', 'みお', 'あいり', 'みう', 'あいか', 'みな', 'あおい', 'みゆき', 'あや', 'まな', 'はるか', 'みなみ', 'あい', 'ひなた', 'りん', 'えみ', 'なお', 'みき', 'あき', 'みほ', 'みゆ', 'あやか', 'みお', 'あいり', 'みう', 'あいか', 'みな', 'あおい'];
        $lastNames = ['佐藤', '鈴木', '高橋', '田中', '伊藤', '渡辺', '中村', '小林', '加藤', '吉田', '山田', '松本', '井上', '木村', '林', '斎藤', '清水', '山本', '中島', '前田', '長谷川', '藤田', '岡田', '後藤', '近藤', '村上', '遠藤', '石井', '工藤', '坂本', '星野', '上田', '森田', '原田', '橋本', '野口', '横山', '西村', '福田', '太田', '藤原', '松田', '青木', '三浦', '久保', '竹内', '中川', '原', '平野', '藤井', '小川', '田村', '村田', '新井', '岩崎', '片山', '内田', '古川', '松井', '千葉', '野村', '大野', '田口', '岡本', '松尾', '宮崎', '中野', '小島', '谷口', '今井', '村山', '藤本', '武田', '上野', '荒井', '菅原', '小松', '大塚', '平田', '宮本', '杉山', '早川', '横田', '高田', '菊地', '丸山', '増田', '中西', '松浦', '大西', '小田', '浅野', '野田', '川口', '石田', '飯田', '前田', '長谷川', '藤田', '岡田', '後藤', '近藤', '村上', '遠藤', '石井', '工藤', '坂本', '星野', '上田', '森田', '原田', '橋本', '野口', '横山', '西村', '福田', '太田', '藤原', '松田', '青木', '三浦', '久保', '竹内', '中川', '原', '平野', '藤井', '小川', '田村', '村田', '新井', '岩崎', '片山', '内田', '古川', '松井', '千葉', '野村', '大野', '田口', '岡本', '松尾', '宮崎', '中野', '小島', '谷口', '今井', '村山', '藤本', '武田', '上野', '荒井', '菅原', '小松', '大塚', '平田', '宮本', '杉山', '早川', '横田', '高田', '菊地', '丸山', '増田', '中西', '松浦', '大西', '小田', '浅野', '野田', '川口', '石田', '飯田'];
        
        $lastName = $lastNames[array_rand($lastNames)];
        $firstName = $firstNames[array_rand($firstNames)];
        $name = "$lastName $firstName";
        
        // Số điện thoại Nhật Bản: +81 XX-XXXX-XXXX
        $phone = '+81 ' . rand(70, 99) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999);
        
        $prefectures = ['東京都', '大阪府', '京都府', '神奈川県', '埼玉県', '千葉県', '兵庫県', '福岡県', '北海道', '愛知県', '広島県', '宮城県', '新潟県', '長野県', '静岡県', '熊本県', '鹿児島県', '沖縄県', '群馬県', '栃木県'];
        $cities = ['新宿区', '渋谷区', '港区', '中央区', '千代田区', '品川区', '目黒区', '世田谷区', '大田区', '杉並区', '中野区', '練馬区', '板橋区', '北区', '荒川区', '足立区', '葛飾区', '江戸川区', '墨田区', '台東区'];
        $streets = ['新宿', '渋谷', '銀座', '六本木', '表参道', '原宿', '代々木', '赤坂', '青山', '恵比寿', '目黒', '自由が丘', '三軒茶屋', '下北沢', '吉祥寺', '高円寺', '中野', '荻窪', '西荻窪', '阿佐ヶ谷'];
        $buildingNumbers = rand(1, 50);
        
        $address = $prefectures[array_rand($prefectures)] . $cities[array_rand($cities)] . $streets[array_rand($streets)] . $buildingNumbers . '丁目';
        
        $notes = [
            '午前中に配達してください',
            '早く配達してください',
            '取り扱いに注意してください',
            '配達前に電話してください',
            '週末に配達',
            '',
            '',
            ''
        ];
        $note = $notes[array_rand($notes)];
        
        // Không được dùng vnpay và momo
        $paymentMethods = ['COD', 'paypal', 'bank_transfer', 'other'];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        
        // Random partner_id từ bảng partners (100% có partner)
        $partnerIds = Partner::pluck('id')->toArray();
        $partnerId = !empty($partnerIds) ? $partnerIds[array_rand($partnerIds)] : null; // 100% có partner nếu có dữ liệu
        
        // COD không được là trạng thái đã thanh toán
        $isPaid = $paymentMethod === 'COD' ? false : (rand(0, 1) == 1);
        
        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'note' => $note,
            'is_paid' => $isPaid,
            'payment_method' => $paymentMethod,
            'partner_id' => $partnerId
        ];
    }
    function generateUniqueOrderCode($length = 20)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);

        do {
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }
            $exists = Order::where('order_code', $randomString)->exists();
        } while ($exists);

        return $randomString;
    }
    
    /**
     * Tạo chuỗi API ngẫu nhiên để theo dõi đơn hàng
     */
    function generateApiString($length = 32)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        
        do {
            $apiString = '';
            for ($i = 0; $i < $length; $i++) {
                $apiString .= $characters[random_int(0, $charactersLength - 1)];
            }
            $exists = Order::where('api', $apiString)->exists();
        } while ($exists);
        
        return $apiString;
    }


    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['partner', 'rank', 'frozen_orders.user']);
        return view('admin.order.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $order->load('partner');
        $partners = Partner::all();
        return view('admin.order.edit', compact('order', 'partners'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $data = $request->only([
            'name', 
            'order_code', 
            'price', 
            'quantity',
            'customer_name',
            'customer_phone',
            'customer_address',
            'customer_note',
            'is_paid',
            'payment_method',
            'partner_id',
            'api'
        ]);
        
        // Xử lý is_paid (checkbox có thể không gửi lên nếu không được chọn)
        $data['is_paid'] = $request->has('is_paid') ? (bool)$request->input('is_paid') : false;
        
        // COD không được là trạng thái đã thanh toán
        if (isset($data['payment_method']) && $data['payment_method'] === 'COD') {
            $data['is_paid'] = false;
        }
        
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($order->image && Storage::exists('public/' . $order->image)) {
                Storage::delete('public/' . $order->image);
            }

            $file = $request->file('image');
            $file_name = $file->store('uploads/images/orders', 'public');
            $data['image'] = $file_name;
        }
        
        $order->update($data);
        return redirect()->route('order.index')->with('success', 'Cập nhật đơn hàng thành công!');
    }
}
