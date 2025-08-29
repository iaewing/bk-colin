<?php

namespace App\Http\Controllers;

use ArdaGnsrn\ElevenLabs\ElevenLabs;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;
use Vonage\Voice\NCCO\Action\Input;
use Vonage\Voice\NCCO\Action\Stream;
use Vonage\Voice\NCCO\Action\Talk;
use Vonage\Voice\NCCO\NCCO;

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
                'endOnSilence' => 1,
                'saveAudio' => true,
                'context' => ['burger', 'king', 'complaint', 'fries', 'soggy', 'cold', 'burger'],
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

//        $previousDialog = Cache::get($input['conversation_uuid']);
//        Log::info($previousDialog);
        $topResult = $input['speech']['results'][0]['text'] ?? null;
//        Cache::put($input['conversation_uuid'], $topResult);

        Log::info('response: ' . $topResult);
        Log::info('input: ', $input);

        if (!$topResult) {
            Log::info('no top result');
            $talk = Talk::factory('We dont have text?', []);
            $ncco->addAction($talk);
            return response()->json($ncco->toArray());
        }
        Log::info('past top result');

        $response = Prism::text()
            ->using(Provider::Anthropic, 'claude-3-5-haiku-20241022')
            ->withSystemPrompt('someone just complained at burger king, and you are the middle age manager who is payed way less than you deserve. you have to respond in a way that does not lose them as a customer but also lets them know that you resent their complaint and you resent the customer as a person. only return the text that should be spoken to the customer. Be sassy.')
            ->withPrompt($topResult)
            ->asText();

        $elevenLabs = new ElevenLabs();
        $response = $elevenLabs->textToSpeech(config('elevenlabs.colin_voice_id'), $response->text);

        $filename = Str::uuid()->toString() . '_colin_response.mp3';
        Storage::disk('colin_audio')->put($filename, $response->getResponse()->getBody()->getContents());

        $colinSassyRemark = new Stream(Storage::disk('colin_audio')->url($filename));
        $ncco->addAction($colinSassyRemark);
//        $input = Input::factory([
//            'eventUrl' => route('voice.event'),
//            'type' => [
//                'speech',
//            ],
//            'speech' => [
//                'endOnSilence' => 1,
//                'saveAudio' => true,
//                'context' => ['burger', 'king', 'complaint', 'fries', 'soggy', 'cold', 'burger'],
//                'language' => 'en-US',
//            ],
//        ]);

//        $ncco->addAction($input);
//        $stream2 = new Stream(Storage::disk('colin_audio')->url('feedback.wav'));
//        $ncco->addAction($stream2);
        return response()->json($ncco->toArray());
    }
}
