<?php

namespace App\Http\Controllers;

use App\Models\Response;
use ArdaGnsrn\ElevenLabs\ElevenLabs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $request = request()->all();

        // TODO: We can implement the cacheing again and use the DB as the driver
//        $previousDialog = Cache::get($input['conversation_uuid']);
//        Log::info($previousDialog);
        $topResult = $request['speech']['results'][0]['text'] ?? null;
//        Cache::put($input['conversation_uuid'], $topResult);

        if (!$topResult) {
            $talk = Talk::factory('We dont have text?', []);
            $ncco->addAction($talk);
            return response()->json($ncco->toArray());
        }

        $systemPrompt = 'Persona: Middle-aged Burger King Manager.

        Context: A customer has just made a complaint.

        Goal: Respond in a way that retains the customer (i.e., addresses the complaint adequately, perhaps with a slight concession) while clearly conveying deep resentment toward both the complaint and the customer personally. The tone must be intensely sarcastic and subtly hostile, without using overtly offensive language that would cause the customer to leave immediately. The response should sound like it is delivered by someone severely overworked and underpaid who is barely tolerating the interaction.

        Constraint: Only return the text that the persona would speak to the customer. Omit speaking cues such as *deep sigh*

        Example Response: Oh, for heaven\'s sake. Look, I get it, the pickle is a millimeter off-center, my deepest apologies for the sheer travesty of your $4 sandwich experience. Tell you what, I\'ll personally have Brenda on the grill re-engineer your Whopper with the precision it clearly demands. Just stand over there to the side, and we\'ll have your perfectly adequate replacement out shortly. Try to contain your disappointment until then.';


        $prismResponse = Prism::text()
            ->using(Provider::Anthropic, 'claude-haiku-4-5')
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($topResult)
            ->asText();
        Log::info('Generated response for call: ' . $request['conversation_uuid'] . $prismResponse->text);

        $elevenLabs = new ElevenLabs();
        $response = $elevenLabs->textToSpeech(
            config('elevenlabs.colin_voice_id'),
            $prismResponse->text
        );

        $filename = Str::uuid()->toString() . '_colin_response.mp3';
        Storage::disk('colin_audio')->put($filename, $response->getResponse()->getBody()->getContents());

        $colinSassyRemark = new Stream(Storage::disk('colin_audio')->url($filename));


        $ncco->addAction($colinSassyRemark);
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
        $stream2 = new Stream(Storage::disk('colin_audio')->url('feedback.wav'));
        $ncco->addAction($stream2);
        $this->recordRecord($topResult, $prismResponse, $filename, $request['conversation_uuid']);
        Log::info('returning response');
        return response()->json($ncco->toArray());
    }

    public function recordRecord(mixed $topResult, \Prism\Prism\Text\Response $prismResponse, string $filename, string $conversation_uuid): void
    {
        Response::query()
            ->create([
                'prompt_text' => $topResult,
                'text' => $prismResponse->text,
                'recording_url' => Storage::disk('colin_audio')->url($filename),
                'call_uuid' => $conversation_uuid
            ]);
    }
}
