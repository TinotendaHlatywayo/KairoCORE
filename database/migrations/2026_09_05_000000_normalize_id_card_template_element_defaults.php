<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('card_templates')) {
            return;
        }

        DB::table('card_templates')->orderBy('id')->each(function (object $template): void {
            $config = json_decode((string) $template->layout_config, true);
            $config = is_array($config) ? $config : [];

            // These are the requested baseline positions for every existing
            // template. New templates receive the same values from the form.
            $config['meta_x'] = 35;
            $config['meta_y'] = 50;
            $config['meta_font_size'] = 10;
            $config['name_x'] = 35;
            $config['name_y'] = 30;
            $config['name_font_size'] = 16;
            $config['motto_x'] = 18;
            $config['motto_y'] = 14;
            $config['motto_font_size'] = 10;
            $config['class_x'] = 35;
            $config['class_y'] = 41;
            $config['class_font_size'] = 13;
            $config['school_name_x'] = 14;
            $config['school_name_y'] = 5;
            $config['school_name_font_size'] = 18;
            $config['logo_x'] = 87;
            $config['logo_y'] = 4;
            $config['logo_width'] = 40;
            $config['logo_height'] = 40;
            $config['logo_fit'] = 'cover';
            $config['photo_x'] = 5;
            $config['photo_y'] = 27;
            $config['photo_width'] = 25;
            $config['photo_height'] = 41;
            $config['qr_x'] = 75;
            $config['qr_y'] = 42;
            $config['qr_size'] = 58;
            $config['contact_x'] = 5;
            $config['contact_y'] = 88;
            $config['contact_width'] = 70;
            $config['show_school_logo'] = $config['show_school_logo'] ?? true;
            $config['show_contact_details'] = $config['show_contact_details'] ?? true;

            DB::table('card_templates')->where('id', $template->id)->update([
                'layout_config' => json_encode($config),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // This is an intentional data normalization; previous user-defined
        // positions cannot be reconstructed safely.
    }
};
