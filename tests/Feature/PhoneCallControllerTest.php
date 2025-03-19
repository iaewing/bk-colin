<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class PhoneCallControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_answer_returns_valid_ncco()
    {
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
    }
}
