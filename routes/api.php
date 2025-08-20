<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PhoneCallController;

Route::middleware('api')->group(function () {
    Route::post('/voice/answer', [PhoneCallController::class, 'answer'])->name('voice.answer');
    Route::post('/voice/event', [PhoneCallController::class, 'event'])->name('voice.event');
});
