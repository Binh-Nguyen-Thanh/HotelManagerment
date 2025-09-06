<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Trả về URL cần chuyển hướng khi chưa xác thực.
     * - Admin area: admin.login
     * - Customer (mặc định): auth_user.login
     * - JSON request: không redirect -> 401
     */
    protected function redirectTo(Request $request)
    {
        // Nếu là AJAX/JSON thì để 401, không redirect
        if ($request->expectsJson()) {
            return null;
        }

        // Xác định ngữ cảnh admin theo path hoặc tên route
        $routeName = optional($request->route())->getName();

        $isAdminContext =
            $request->is('admin') ||
            $request->is('admin/*') ||
            ($routeName && str_starts_with($routeName, 'admin.'));

        if ($isAdminContext) {
            return route('admin.login');         // ví dụ /admin/login
        }

        // Mặc định: khu vực khách hàng
        return route('auth_user.login');         // ví dụ /login
    }
}
