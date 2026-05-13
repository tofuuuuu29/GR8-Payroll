<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\LoginLog;
use App\Models\Account;
use Symfony\Component\HttpFoundation\Response;

class LogUserLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log successful login only if user is authenticated
        if (auth()->check() && $request->isMethod('post') && $request->route()->getName() === 'login.post') {
            try {
                $account = auth()->user();
                if ($account instanceof Account) {
                    LoginLog::recordForAccount($account, $request->ip(), $request->userAgent());
                }
            } catch (\Exception $e) {
                // Log error but don't interrupt login flow
                \Log::error('Failed to log user login: ' . $e->getMessage());
            }
        }

        return $response;
    }
}