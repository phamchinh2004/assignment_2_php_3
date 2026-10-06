<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cấu hình Auto Reply
    |--------------------------------------------------------------------------
    |
    | Cấu hình cho tính năng tự động trả lời tin nhắn khi khách hàng nhắn tin
    | sau một khoảng thời gian dài không có tin nhắn từ staff.
    |
    */

    'auto_reply' => [
        // Bật/tắt tính năng auto reply
        'enabled' => env('CHAT_AUTO_REPLY_ENABLED', true),

        // Thời gian timeout (giờ) - chỉ gửi auto-reply nếu đã không nhắn với staff >= X giờ.
        // Với chat hỗ trợ, 1 giờ là mức chấp nhận được để không chờ quá lâu nhưng vẫn tránh spam.
        'timeout_hours' => env('CHAT_AUTO_REPLY_TIMEOUT_HOURS', 1),

        // Tần suất tối thiểu giữa các tin nhắn tự động trong cùng conversation.
        'repeat_after_hours' => env('CHAT_AUTO_REPLY_REPEAT_AFTER_HOURS', 1),

        // Nếu quản lý đang online, chờ khoảng thời gian này để họ có cơ hội trả lời trước khi gửi auto-reply.
        'online_manager_delay_minutes' => env('CHAT_AUTO_REPLY_ONLINE_MANAGER_DELAY_MINUTES', 1),

        // Sau bao nhiêu phút kể từ khi auto-reply gửi, nếu chưa có admin/staff reply thì gửi email cảnh báo.
        'escalation_after_minutes' => env('CHAT_AUTO_REPLY_ESCALATION_AFTER_MINUTES', 5),

        // Nội dung tin nhắn chào tự động (hỗ trợ đa ngôn ngữ)
        'messages' => [
            'vi' => 'Chúng tôi đã nhận được tin nhắn của bạn. Vui lòng chờ CSKH phản hồi nhé!',
            'en' => 'We have received your message. Please wait for a reply from our customer support team!',
            'es' => 'Hemos recibido tu mensaje. ¡Por favor, espera la respuesta de nuestro equipo de atención al cliente!',
            'ja' => 'メッセージを受け取りました。カスタマーサポートからの返信をお待ちください！',
            'ko' => '메시지를 받았습니다. 고객지원팀의 답변을 기다려 주세요!',
            'zh' => '我们已收到您的消息，请耐心等待客服回复！',
        ],

        // Ngôn ngữ mặc định nếu không tìm thấy ngôn ngữ của user
        'default_language' => 'vi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cấu hình gợi ý tin nhắn nhanh
    |--------------------------------------------------------------------------
    |
    | Các tin nhắn gợi ý giúp khách hàng gửi tin nhắn nhanh chóng
    | mà không cần phải gõ thủ công.
    |
    */
    'quick_replies' => [
        // Bật/tắt tính năng gợi ý tin nhắn
        'enabled' => env('CHAT_QUICK_REPLIES_ENABLED', true),

        // Hiển thị gợi ý khi nào?
        'show_when' => [
            'chat_empty' => true,        // Hiển thị khi chưa có tin nhắn
            'after_hours' => 2,          // Hiển thị lại sau X giờ không nhắn
        ],

        // Danh sách gợi ý theo ngôn ngữ
        'suggestions' => [
            'vi' => [
                '👋 Xin chào, tôi cần hỗ trợ',
                '🏪 Tôi muốn mở gian hàng',
                '❓ Có ai đang online không?',
                '💰 Tôi muốn nạp tiền',
                '📦 Kiểm tra đơn hàng của tôi',
                '🎁 Hỏi về chương trình khuyến mãi',
            ],
            'en' => [
                '👋 Hello, I need support',
                '🏪 I want to open a store',
                '❓ Is anyone online?',
                '💰 I want to deposit money',
                '📦 Check my order',
                '🎁 Ask about promotions',
            ],
            'es' => [
                '👋 Hola, necesito ayuda',
                '🏪 Quiero abrir una tienda',
                '❓ ¿Hay alguien en línea?',
                '💰 Quiero depositar dinero',
                '📦 Revisar mi pedido',
                '🎁 Preguntar sobre promociones',
            ],
            'ja' => [
                '👋 こんにちは、サポートが必要です',
                '🏪 店舗を開設したい',
                '❓ オンラインの方はいますか？',
                '💰 入金したい',
                '📦 注文を確認する',
                '🎁 プロモーションについて質問',
            ],
            'ko' => [
                '👋 안녕하세요, 도움이 필요합니다',
                '🏪 상점을 열고 싶어요',
                '❓ 온라인 중인 분 계신가요?',
                '💰 입금하고 싶어요',
                '📦 주문 확인',
                '🎁 프로모션 문의',
            ],
            'zh' => [
                '👋 你好，我需要帮助',
                '🏪 我想开店',
                '❓ 有人在线吗？',
                '💰 我想充值',
                '📦 查看我的订单',
                '🎁 询问促销活动',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cấu hình phân trang tin nhắn
    |--------------------------------------------------------------------------
    */
    'messages_per_load' => 5,

    /*
    |--------------------------------------------------------------------------
    | Cấu hình giới hạn tin nhắn
    |--------------------------------------------------------------------------
    */
    'max_message_length' => 500,
    'max_image_size' => 5120, // KB

];
