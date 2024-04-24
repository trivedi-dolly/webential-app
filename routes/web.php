<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::controller(AuthController::class)->group(function () {
    Route::get('/', 'index')->name('signup');
});

Auth::routes();

Route::controller(HomeController::class)->group(function () {
    Route::get('/home', 'index')->name('home');
    Route::get('/list-requests', 'getRequestList')->name('get-requests-list');
    Route::post('/send-request/{user}', 'sendRequest')->name('send-request');
    Route::post('/accept-request/{friendship}', 'acceptRequest')->name('accept-friend-request');
    Route::post('/reject-request/{friendship}', 'rejectRequest')->name('reject-friend-request');
    Route::get('/list-friends', 'getFriendsList')->name('get-friends-list');
    Route::get('/chat/{userId}', 'showChat')->name('chat.show');
});
