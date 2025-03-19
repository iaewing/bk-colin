<?php

namespace App\Http\Controllers;

use Vonage\Voice\NCCO\NCCO;
use Illuminate\Support\Facades\Log;
use Vonage\Voice\NCCO\Action\Talk;
use Vonage\Voice\NCCO\Action\Stream;
use Illuminate\Support\Facades\Storage;
use Vonage\Voice\NCCO\Action\Input;

class PhoneCallController extends Controller
{
    public function answer()
    {
        $ncco = new NCCO();

        $stream1 = new Stream(Storage::disk('colin_audio')->url('hello.wav'));
        $ncco->addAction($stream1);

        $input = Input::factory([
            'eventUrl' => 'https://bk-colin.test/voice/event',
            'type' => [
              'speech',
            ],
            'speech' => [
              'endOnSilence' => 2,
            ],
        ]);
        $ncco->addAction($input);


        $stream2 = new Stream(Storage::disk('colin_audio')->url('feedback.wav'));
        $ncco->addAction($stream2);

        return response()->json($ncco->toArray());
    }

    public function event()
    {
        Log::info('Vonage Event');
        Log::info(request()->all());
        
        return response()->json(['status' => 'ok']);
    }
}
