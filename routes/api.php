<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PhoneCallController;

Route::post('/voice/answer', [PhoneCallController::class, 'answer']); 