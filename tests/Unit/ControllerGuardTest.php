<?php

namespace Tests\Unit;

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\LeaveController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ControllerGuardTest extends TestCase
{
    public function test_login_requires_email_and_password(): void
    {
        $controller = new AuthController();
        $request = Request::create('/login', 'POST', []);

        $thrown = false;

        try {
            $controller->login($request);
        } catch (ValidationException $e) {
            $thrown = true;
            $errors = $e->errors();

            $this->assertArrayHasKey('email', $errors);
            $this->assertArrayHasKey('password', $errors);
        }

        $this->assertTrue($thrown);
    }

    public function test_leave_status_update_blocks_unauthorized_roles(): void
    {
        Auth::shouldReceive('user')->once()->andReturn((object) ['role' => 'employee']);

        $controller = new LeaveController();
        $request = Request::create('/attendance/leave-management/1/status', 'PUT', [
            'action' => 'approve',
        ]);

        $response = $controller->updateStatus($request, '1');

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_leave_balance_endpoints_block_unauthorized_roles(): void
    {
        $controller = new LeaveController();

        Auth::shouldReceive('user')->once()->andReturn((object) ['role' => 'employee']);
        $getResponse = $controller->getLeaveBalance(Request::create('/attendance/leave-management/balance', 'GET'));
        $this->assertSame(403, $getResponse->getStatusCode());

        Auth::shouldReceive('user')->once()->andReturn((object) ['role' => 'employee']);
        $storeResponse = $controller->storeBalance(Request::create('/attendance/leave-management/balance', 'POST'));
        $this->assertSame(403, $storeResponse->getStatusCode());

        Auth::shouldReceive('user')->once()->andReturn((object) ['role' => 'employee']);
        $updateResponse = $controller->updateBalance(
            Request::create('/attendance/leave-management/balance/1', 'PUT'),
            '1'
        );
        $this->assertSame(403, $updateResponse->getStatusCode());
    }
}
