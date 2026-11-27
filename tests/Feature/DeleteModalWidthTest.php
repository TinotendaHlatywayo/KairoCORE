<?php

namespace Tests\Feature;

use Filament\Actions\DeleteAction;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\DeleteAction as TableDeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Tests\TestCase;

class DeleteModalWidthTest extends TestCase
{
    public function test_every_filament_delete_action_defaults_to_a_medium_modal(): void
    {
        foreach ([
            DeleteAction::make(),
            TableDeleteAction::make(),
            DeleteBulkAction::make(),
        ] as $action) {
            $this->assertSame(MaxWidth::Medium, $action->getModalWidth());
        }
    }

    public function test_explicit_delete_modal_widths_still_win(): void
    {
        $action = DeleteAction::make()->modalWidth(MaxWidth::Large);

        $this->assertSame(MaxWidth::Large, $action->getModalWidth());
    }

    public function test_theme_does_not_force_filament_modal_windows_wide(): void
    {
        $css = (string) file_get_contents(resource_path('css/filament-custom.css'));

        $this->assertDoesNotMatchRegularExpression(
            '/\.fi-modal-window\s*\{[^}]*max-width\s*:[^;]+!\s*important/s',
            $css,
        );

        $this->assertDoesNotMatchRegularExpression(
            '/\.fi-modal\s*,\s*\.fi-modal-window\s*,[^{}]*\{[^}]*max-width\s*:[^;]+!\s*important/s',
            $css,
        );
    }
}
