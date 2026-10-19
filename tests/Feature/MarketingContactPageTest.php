<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketingContactPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    public function test_contact_page_renders_with_map_and_address(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        // Map iframe below the form (Google Maps embed, no API key needed).
        $response->assertSee('maps.google.com/maps?q=', false);
        $response->assertSee('output=embed', false);
        // Address links out to a Google Maps search (& is HTML-escaped in attributes).
        $response->assertSee('google.com/maps/search/?api=1&amp;query=', false);
        // Office hours + location content exists.
        $response->assertSee('08:00', false);
    }

    public function test_contact_page_counters_are_present(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('id="name-counter"', false);
        $response->assertSee('id="message-char-limit"', false);
        // Counters initialise from pre-filled old() values.
        $response->assertSee("nameCounter.textContent = (nameInput.value || '').trim().length + '/100 letters'", false);
    }

    public function test_contact_page_form_and_channels_render(): void
    {
        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('id="contact-form"', false);
        $response->assertSee('website_hp', false); // honeypot
        $response->assertSee('wa.me', false);      // WhatsApp channel
    }
}
