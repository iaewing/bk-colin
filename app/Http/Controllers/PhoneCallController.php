<?php

namespace App\Http\Controllers;

use Vonage\Voice\NCCO\NCCO;
use Illuminate\Support\Facades\Log;
use Vonage\Voice\NCCO\Action\Stream;
use Illuminate\Support\Facades\Storage;
use Vonage\Voice\Endpoint\Websocket;
use Vonage\Voice\NCCO\Action\Connect;
use Illuminate\Support\Facades\Cache;

class PhoneCallController extends Controller
{
    public function answer()
    {
        $ncco = new NCCO();

        // First action: Play the greeting
        $stream = new Stream(Storage::disk('colin_audio')->path('hello.wav'));
        $ncco->addAction($stream);

        // Create WebSocket endpoint following Vonage's format exactly
        $websocket = new Websocket(
            'wss://api.deepgram.com/v1/listen',  // Base URL without query params
            'audio/l16;rate=8000',
            [
                'Authorization' => 'Token ' . env('DEEPGRAM_API_KEY')
            ]
        );

        $connect = new Connect($websocket);
        $ncco->addAction($connect);

        // Detailed logging
        Log::info('NCCO Configuration:', [
            'ncco' => $ncco->toArray(),
            'connect_action' => array_filter([
                'action' => 'connect',
                'endpoint' => [[
                    'type' => 'websocket',
                    'uri' => 'wss://api.deepgram.com/v1/listen',
                    'content-type' => 'audio/l16;rate=8000',
                    'headers' => [
                        'Authorization' => 'Token ' . env('DEEPGRAM_API_KEY')
                    ]
                ]]
            ])
        ]);

        return response()->json($ncco->toArray());
    }

    public function event()
    {
        $data = request()->all();
        $headers = request()->headers->all();
        
        Log::info('Event Received:', [
            'type' => isset($data['channel']) ? 'Deepgram Event' : 'Vonage Event',
            'headers' => $headers,
            'data' => $data,
            'conversation_uuid' => request()->header('Vonage-Conversation-Uuid'),
            'timestamp' => now()->toIso8601String()
        ]);

        return response()->json(['status' => 'ok']);
    }

    protected function processFinalTranscript(string $transcript)
    {
        Log::info('Final Transcript', ['transcript' => $transcript]);

        // Store the transcript in cache for the current call
        $callId = request()->header('Vonage-Conversation-Uuid');
        if ($callId) {
            Cache::put("call_transcript:{$callId}", $transcript, now()->addHours(1));
        }
                   //   $websocket = new Websocket(
        //     'wss://api.deepgram.com/v1/listen?encoding=linear16&sample_rate=8000&channels=1&model=nova-2&interim_results=true&utterance_end_ms=1000&callback=' . route('api.voice.event'),
        //     'audio/l16;rate=8000',
        //     ['Authorization' => 'Token ' . env('DEEPGRAM_API_KEY')]
        // );
    }
}
