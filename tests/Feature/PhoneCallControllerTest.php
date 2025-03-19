<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Http\Controllers\PhoneCallController;
use Vonage\Voice\NCCO\NCCO;
use Vonage\Voice\NCCO\Action\Talk;
use Vonage\Voice\NCCO\Action\Pause;

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
        $response = $this->post('/voice/answer');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            [
                'action' => 'talk',
                'text' => 'Hello! This is your first audio message.'
            ],
            [
                'action' => 'pause',
                'length' => function ($value) {
                    return $value >= 10 && $value <= 20;
                }
            ],
            [
                'action' => 'talk',
                'text' => 'This is your second audio message. Goodbye!'
            ]
        ]);
    }
}
