<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\UnstructuredHeader;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    private string $name;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('CS@api.hris.my.id', 'Jeffrey Way'),
            replyTo: [
                      new Address('mabukngoding@gmail.com', 'Taylor Otwell'),
                  ],
            subject: 'Welcome Mail HRIS',
            // using: [
            //             function (Email $email) {
            //                 // Headers
            //                 $email->getHeaders()
            //                     ->addTextHeader('X-Message-Source', 'gmail.com')
            //                     ->add(new UnstructuredHeader('X-Mailer', 'Mailtrap PHP Client'))
            //                 ;
            //             },
            //         ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome-email',
            with: ['name' => $this->name],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath('https://mailtrap.io/wp-content/uploads/2021/04/mailtrap-new-logo.svg')
                ->as('logo.svg')
                ->withMime('image/svg+xml'),
        ];
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            'custom-message-id@example.com',
            ['previous-message@example.com'],
            [
                'X-Custom-Header' => 'Custom Value',
            ],
        );
    }
}