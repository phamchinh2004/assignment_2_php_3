<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getPrevious() instanceof TokenMismatchException
                && $request->routeIs('login_done')
                && ! $request->expectsJson()) {
                return redirect()->route('login')
                    ->with('warning', 'Phiên đăng nhập đã thay đổi hoặc hết hạn. Vui lòng thử lại.');
            }
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
