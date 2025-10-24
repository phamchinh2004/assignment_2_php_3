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

        // Thời gian timeout (giờ) - chỉ gửi auto-reply nếu đã không nhắn với staff >= X giờ
        'timeout_hours' => env('CHAT_AUTO_REPLY_TIMEOUT_HOURS', 1),

        // Nội dung tin nhắn chào tự động (hỗ trợ đa ngôn ngữ)
        'messages' => [
            'vi' => 'Xin chào! Cảm ơn bạn đã liên hệ với chúng tôi. Chúng tôi đã nhận được tin nhắn của bạn và sẽ phản hồi trong thời gian sớm nhất. Vui lòng chờ trong giây lát, đội ngũ hỗ trợ sẽ liên hệ với bạn ngay khi có thể.',
            'en' => 'Hello! Thank you for contacting us. We have received your message and will respond as soon as possible. Please wait a moment, our support team will contact you as soon as we can.',
            'es' => '¡Hola! Gracias por contactarnos. Hemos recibido tu mensaje y te responderemos lo antes posible. Por favor espera un momento, nuestro equipo de soporte se pondrá en contacto contigo lo antes posible.',
            'ja' => 'こんにちは！お問い合わせいただきありがとうございます。メッセージを受け取りました。できるだけ早く返信いたします。しばらくお待ちください。サポートチームができるだけ早くご連絡いたします。',
            'ko' => '안녕하세요! 문의해 주셔서 감사합니다. 메시지를 받았으며 가능한 한 빨리 답변드리겠습니다. 잠시만 기다려 주시면 지원팀이 가능한 한 빨리 연락드리겠습니다.',
            'zh' => '您好！感谢您联系我们。我们已收到您的消息，将尽快回复。请稍等，我们的支持团队会尽快与您联系。',
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

