<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $namaAdmin;

    public function __construct(string $otp, string $namaAdmin)
    {
        $this->otp       = $otp;
        $this->namaAdmin = $namaAdmin;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SIK Binatama] Kode OTP Ganti Password Admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-otp',
        );
    }
}