<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::find(8);
Auth::login($user);

$request = Request::create('/api/user/profile', 'POST', [
    'name' => 'Phon Lyhor Verified',
    'gender' => 'male',
    'date_of_birth' => '2007-07-12',
    'phone_number' => '09347343',
    'address' => 'Phnom Penh Test Address'
]);

$controller = new App\Http\Controllers\UserController();
$response = $controller->updateProfile($request);
echo "Profile Update Output: " . $response->getContent() . "\n";

$user->refresh();
echo "Verified Name: " . $user->name . "\n";
echo "Verified Address: " . $user->address . "\n";

// Password change
$passRequest = Request::create('/api/user/password', 'PUT', [
    'old_password' => 'password123',
    'new_password' => 'newpassword123',
    'new_password_confirmation' => 'newpassword123'
]);
$passResponse = $controller->changePassword($passRequest);
echo "Password Change Output: " . $passResponse->getContent() . "\n";
