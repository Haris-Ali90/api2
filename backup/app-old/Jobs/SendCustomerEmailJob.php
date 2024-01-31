<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailer;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCustomerEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;

    protected $subject;

    protected $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($joey,$subject,$message)
    {
        $this->customer = $joey;
        $this->subject = $subject;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Mailer $mailer)
    {
        Mail::send('admin.send_mail.joeyEmail', ['user' => $this->customer], function ($m) {
            $m->to($this->customer->email, $this->customer->name)->subject('Mail From JoeyCo!');
        });
    }
}
