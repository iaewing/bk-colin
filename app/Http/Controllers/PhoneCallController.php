<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vonage\Voice\NCCO\NCCO;
use Vonage\Voice\NCCO\Action\Talk;

class PhoneCallController extends Controller
{
    public function answer()
    {
        $ncco = new NCCO();
        
        $talk = new Talk('Hello! This is your first audio message.');
        $ncco->addAction($talk);
        
        // $pause = new Talk('', ['bargeIn' => false, 'loop' => 0, 'level' => 0]);
        // $ncco->addAction($pause);
        
        $talk2 = new Talk('This is your second audio message. Goodbye!');
        $ncco->addAction($talk2);
        
        return response()->json($ncco->toArray());
    }
}
