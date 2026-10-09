<?php

namespace App\Filament\Admin\Resources\SchoolResource\Pages;

use App\Filament\Admin\Resources\SchoolResource;
use Filament\Resources\Pages\CreateRecord;
use Modules\SaaS\Services\SubscriptionProvisioner;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    /**
     * Module Visibility switches picked on the create form. They are not a
     * column on schools, so they are held here until the record exists and
     * written to system_settings in afterCreate().
     *
     * @var array<string, bool>
     */
    protected array $pendingModuleToggles = [];

    /**
     * Subscription plan picked on the create form. Held until the school row
     * exists, then applied to the provisioned subscription.
     */
    protected ?int $pendingPlanId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingPlanId = isset($data['subscription_plan_id'])
            ? (int) $data['subscription_plan_id']
            : null;

        unset($data['subscription_plan_id']);

        [$data, $this->pendingModuleToggles] = SchoolResource::splitModuleToggles($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record !== null && $this->pendingModuleToggles !== []) {
            SchoolResource::writeModuleToggles($this->pendingModuleToggles, (int) $this->record->id);
        }

        $this->pendingModuleToggles = [];

        if ($this->record !== null) {
            $provisioner = app(SubscriptionProvisioner::class);

            if ($this->pendingPlanId) {
                $provisioner->assignPlan($this->record, $this->pendingPlanId);
            } else {
                $provisioner->ensureForSchool($this->record);
            }
        }

        $this->pendingPlanId = null;
    }
}
