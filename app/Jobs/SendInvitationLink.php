<?php

namespace App\Jobs;

use App\Mail\InvitationEmail;
use App\Notifications\InvitationLinkNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvitationLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $invitation;

    /**
     * @param $invitation
     */
    public function __construct($invitation)
    {
        $this->invitation = $invitation;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invitation = $this->invitation;
        Mail::to($invitation->email)->send(new InvitationEmail($invitation, 'JotPhone Invitation'));
    }
}
