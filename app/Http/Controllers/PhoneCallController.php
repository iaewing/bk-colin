<?php

namespace App\Http\Controllers;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Vonage\Voice\NCCO\NCCO;
use Illuminate\Support\Facades\Log;
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
            'eventUrl' => route('voice.event'),
            'type' => [
              'speech',
            ],
            'speech' => [
              'endOnSilence' => 2,
              'saveAudio' => true,
              'context' => ['burger', 'king', 'complaint', 'fries', 'soggy', 'cold', 'burger' ],
              'language' => 'en-US',
            ],
        ]);

        $ncco->addAction($input);

        return response()->json($ncco->toArray());
    }

    public function event()
    {
        $ncco = new NCCO();
        $input = request()->all();

        $topResult = $input['speech']['results'][0] ?? null;

        Log::info($topResult);

        $response = Prism::text()
            ->using(Provider::Anthropic, 'claude-3-5-haiku-20241022')
            ->withSystemPrompt('someone just complained at burger king, and you are the middle age manager who is payed way less than you deserve. you have to respond in a way that does not lose them as a customer but also lets them know that you resent their complaint and you resent the customer as a person. only return the text that should be spoken to the customer')
            ->withPrompt($topResult)
            ->asText();

        $aiResponse = Talk::factory($response->text, []);
        $ncco->addAction($aiResponse);

        $input = Input::factory([
            'eventUrl' => route('voice.event'),
            'type' => [
                'speech',
            ],
            'speech' => [
                'endOnSilence' => 2,
                'saveAudio' => true,
                'context' => ['burger', 'king', 'complaint', 'fries', 'soggy', 'cold', 'burger' ],
                'language' => 'en-US',
            ],
        ]);

        $ncco->addAction($input);
//        $stream2 = new Stream(Storage::disk('colin_audio')->url('feedback.wav'));
//        $ncco->addAction($stream2);

        return response()->json(['status' => 'ok']);
    }
}
