<?php

use Illuminate\Support\Facades\Storage;

test('it records details of the incoming call', function () {

});

test('answer returns valid ncco', function () {
    $response = $this->post('/api/voice/answer');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        [
            'action' => 'stream',
            'streamUrl' => Storage::disk('colin_audio')->url('hello.wav')
        ],
        [
            'action' => 'input',
            'type' => ['speech'],
            'speech' => [
                'endOnSilence' => 2
            ],
            'eventUrl' => 'https://bk-colin.test/voice/event'
        ],
        [
            'action' => 'stream',
            'streamUrl' => Storage::disk('colin_audio')->url('feedback.wav')
        ]
    ]);
});
