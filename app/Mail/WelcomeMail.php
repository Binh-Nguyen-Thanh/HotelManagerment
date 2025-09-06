<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $info;

    public function __construct($name, $info)
    {
        $this->name = $name;
        $this->info = $info;
    }

    public function build()
    {
        return $this->subject('Chào mừng đến với ' . $this->info->name)
                    ->view('welcome')
                    ->with([
                        'name' => $this->name,
                        'info' => $this->info,
                    ]);
    }
}