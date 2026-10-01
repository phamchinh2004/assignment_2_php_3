<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ filled($title ?? null) ? $title . ' | ' . config('app.name', 'Dropshipping') : config('app.name', 'Dropshipping') }}</title>
    <link rel="icon" href="{{ asset('images/logo/tta.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/general.css', 'resources/js/react/main.jsx'])
</head>
<body style="margin: 0; background: #0b0b0d;">
    <div id="react-app-root"></div>
    <script id="react-page-bootstrap" type="application/json">@json($reactPageBootstrap)</script>
</body>
</html>
