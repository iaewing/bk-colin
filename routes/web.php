<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PhoneCallController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/voice/answer', [PhoneCallController::class, 'answer']);
