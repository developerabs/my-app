<?php

namespace App\Mail\Landlord;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $password;
    /**
     * Create a new message instance.
     */
    public function __construct($tenant, $password)
    {
        $this->tenant = $tenant;
        $this->password = $password;
    }

       public function build()
    {
        return $this
            ->subject('Tenant Created Successfully')
            ->view('landlord.mail.tenant-create')
            ->with([
                'tenant' => $this->tenant,
                'password' => $this->password
            ]);
    }
}
