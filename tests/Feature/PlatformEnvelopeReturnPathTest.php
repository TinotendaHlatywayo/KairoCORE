<?php

namespace Tests\Feature;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Gmail rejects MAIL FROM when it differs from the authenticated transport
 * user (553 "Sender address rejected"). The MessageSending listener in
 * AppServiceProvider must align the envelope with that user while leaving the
 * tenant's display From untouched.
 */
class PlatformEnvelopeReturnPathTest extends TestCase
{
    private function captureReturnPath(?string &$returnPath): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) use (&$returnPath) {
            $returnPath = $event->message->getReturnPath()?->getAddress();
        });
    }

    public function test_envelope_return_path_follows_transport_username(): void
    {
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array.username', 'noreply@kairocore.co.zw');

        $returnPath = null;
        $this->captureReturnPath($returnPath);

        Mail::mailer('array')->send([], [], function ($message) {
            $message->to('ops@school.edu')
                ->from('admissions@school.example')
                ->subject('Envelope test')
                ->html('<p>hi</p>');
        });

        $this->assertSame('noreply@kairocore.co.zw', $returnPath);
    }

    public function test_existing_return_path_is_not_overwritten(): void
    {
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array.username', 'noreply@kairocore.co.zw');

        $returnPath = null;
        $this->captureReturnPath($returnPath);

        Mail::mailer('array')->send([], [], function ($message) {
            $message->to('ops@school.edu')
                ->from('admissions@school.example')
                ->subject('Envelope test')
                ->html('<p>hi</p>')
                ->returnPath('bounce@school.example');
        });

        $this->assertSame('bounce@school.example', $returnPath);
    }

    public function test_transport_without_username_leaves_envelope_untouched(): void
    {
        Config::set('mail.default', 'array');
        Config::set('mail.mailers.array.username', null);

        $returnPath = null;
        $this->captureReturnPath($returnPath);

        Mail::mailer('array')->send([], [], function ($message) {
            $message->to('ops@school.edu')
                ->from('admissions@school.example')
                ->subject('Envelope test')
                ->html('<p>hi</p>');
        });

        $this->assertNull($returnPath);
    }
}
