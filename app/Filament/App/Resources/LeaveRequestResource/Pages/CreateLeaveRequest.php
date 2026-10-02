<?php

namespace App\Filament\App\Resources\LeaveRequestResource\Pages;

use App\Filament\App\Resources\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Modules\HR\Models\LeaveRequest;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected static ?string $title = 'New Leave Request';

    /**
     * Attribute the request to whoever is actually filing it.
     *
     * The form hides the choice from anyone who is not HR, but hiding a field is
     * a courtesy to the interface, not a guarantee: a crafted request can still
     * post an arbitrary employee_id. So for everyone else the submitted value is
     * discarded and replaced with their own record. HR and the administrator
     * keep the freedom to file on someone's behalf, which is a real part of the
     * job — someone calls in sick and a secretary has to raise it for them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! LeaveRequestResource::mayFileForOthers()) {
            $data['employee_id'] = LeaveRequestResource::ownEmployeeId();
        }

        return $data;
    }

    /**
     * A request with no employee behind it is not a request.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * A new request is pending, and belongs to this school, whatever was posted.
     *
     * `status` is on the model's fillable list for the benefit of the approve
     * and reject handlers, so it is reachable from a crafted request too. Since
     * nobody may approve their own leave, that matters: the value is pinned here
     * rather than trusted from the form.
     */
    protected function handleRecordCreation(array $data): LeaveRequest
    {
        $data['school_id'] = Auth::user()?->school_id;
        $data['status'] = 'pending';
        unset($data['approved_by_id'], $data['hr_remarks']);

        return LeaveRequest::create($data);
    }
}
