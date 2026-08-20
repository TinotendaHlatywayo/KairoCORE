<?php

namespace Modules\Reports\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataExtractionService
{
    /**
     * Map out available reporting endpoints across every functional domain.
     */
    public function getModuleRegistry(): array
    {
        return [
            'students' => [
                'label' => __('Student Information'),
                'icon' => 'heroicon-o-academic-cap',
                'types' => [
                    'student_register' => [
                        'label' => __('Comprehensive Student Register'),
                        'default_fields' => ['admission_number', 'first_name', 'last_name', 'gender', 'class_name', 'status'],
                        'available_fields' => [
                            'admission_number' => 'Admission Number',
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'gender' => 'Gender',
                            'date_of_birth' => 'Date of Birth',
                            'class_name' => 'Current Class Stream',
                            'boarding_status' => 'Boarding / Day Scholar',
                            'status' => 'Status',
                            'admission_date' => 'Admission Date',
                            'national_id' => 'National ID / Passport Number',
                        ],
                    ],
                ],
            ],
            'finance' => [
                'label' => __('Finance & Fees Ledgers'),
                'icon' => 'heroicon-o-banknotes',
                'types' => [
                    'fee_balances' => [
                        'label' => __('Fee Outstanding Balances Directory'),
                        'default_fields' => ['student_name', 'class_name', 'invoice_amount', 'paid_amount', 'balance_due'],
                        'available_fields' => [
                            'student_name' => 'Student Full Name',
                            'class_name' => 'Class Stream',
                            'invoice_number' => 'Invoice Reference',
                            'invoice_amount' => 'Invoiced Value ($)',
                            'paid_amount' => 'Amount Paid ($)',
                            'balance_due' => 'Balance Outstanding ($)',
                            'due_date' => 'Due Date',
                        ],
                    ],
                ],
            ],
            'inventory' => [
                'label' => __('Inventory & Fixed Assets'),
                'icon' => 'heroicon-o-archive-box',
                'types' => [
                    'stock_levels' => [
                        'label' => __('Current Stock Status & Reorders'),
                        'default_fields' => ['sku', 'name', 'current_quantity', 'reorder_level', 'average_unit_cost'],
                        'available_fields' => [
                            'sku' => 'SKU Code',
                            'name' => 'Item Name',
                            'item_type' => 'Classification Type',
                            'unit_of_measure' => 'UOM',
                            'current_quantity' => 'Quantity on Hand',
                            'reorder_level' => 'Safety Reorder Threshold',
                            'average_unit_cost' => 'Moving Avg Cost ($)',
                        ],
                    ],
                ],
            ],
            'hostels' => [
                'label' => __('Hostels & Boarding Lifecycle'),
                'icon' => 'heroicon-o-home',
                'types' => [
                    'hostel_occupancy' => [
                        'label' => __('Hostel Bed Space Allocation Register'),
                        'default_fields' => ['student_name', 'hostel_name', 'building_name', 'room_number', 'bed_number'],
                        'available_fields' => [
                            'student_name' => 'Student Name',
                            'hostel_name' => 'Hostel Name',
                            'building_name' => 'Building Block',
                            'room_number' => 'Room Number',
                            'bed_number' => 'Bed Space Designation',
                            'allocation_status' => 'Occupancy State',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build and run scoped database query builders based on target selections and filter arrays.
     */
    public function extract(string $module, string $type, array $fields, array $filters = []): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            return [];
        }

        switch ("{$module}.{$type}") {
            case 'students.student_register':
                return DB::table('students')
                    ->leftJoin('enrollments', function ($join) {
                        $join->on('students.id', '=', 'enrollments.student_id')
                            ->whereRaw('enrollments.id = (SELECT id FROM enrollments WHERE student_id = students.id ORDER BY id DESC LIMIT 1)');
                    })
                    ->leftJoin('courses', 'enrollments.course_id', '=', 'courses.id')
                    ->leftJoin('sections', 'enrollments.section_id', '=', 'sections.id')
                    ->select([
                        'students.admission_number',
                        'students.first_name',
                        'students.last_name',
                        'students.gender',
                        'students.date_of_birth',
                        'students.boarding_status',
                        'students.status',
                        'students.admission_date',
                        'students.national_id',
                        DB::raw("CONCAT(courses.name, ' ', sections.name) as class_name"),
                    ])
                    ->where('students.school_id', $schoolId)
                    ->when(! empty($filters['gender']), fn ($q) => $q->where('students.gender', $filters['gender']))
                    ->when(! empty($filters['status']), fn ($q) => $q->where('students.status', $filters['status']))
                    ->get()
                    ->toArray();

            case 'finance.fee_balances':
                // 1. Inspect the 'invoices' table columns at runtime
                $columns = Schema::getColumnListing('invoices');

                // 2. Dynamically resolve total/amount column name
                $amountCol = 'net_total';
                if (in_array('net_total', $columns)) {
                    $amountCol = 'net_total';
                } elseif (in_array('total_amount', $columns)) {
                    $amountCol = 'total_amount';
                } elseif (in_array('total', $columns)) {
                    $amountCol = 'total';
                } elseif (in_array('amount', $columns)) {
                    $amountCol = 'amount';
                } elseif (in_array('invoice_amount', $columns)) {
                    $amountCol = 'invoice_amount';
                }

                // 3. Dynamically resolve paid column name
                $paidCol = 'paid_amount';
                if (in_array('paid_amount', $columns)) {
                    $paidCol = 'paid_amount';
                } elseif (in_array('amount_paid', $columns)) {
                    $paidCol = 'amount_paid';
                } elseif (in_array('paid', $columns)) {
                    $paidCol = 'paid';
                }

                // 4. Dynamically resolve balance column name
                $balanceExpr = "({$amountCol} - {$paidCol})";
                if (in_array('balance', $columns)) {
                    $balanceExpr = 'balance';
                } elseif (in_array('balance_due', $columns)) {
                    $balanceExpr = 'balance_due';
                } elseif (in_array('outstanding_balance', $columns)) {
                    $balanceExpr = 'outstanding_balance';
                }

                return DB::table('invoices')
                    ->join('students', 'invoices.student_id', '=', 'students.id')
                    ->leftJoin('enrollments', 'students.id', '=', 'enrollments.student_id')
                    ->leftJoin('courses', 'enrollments.course_id', '=', 'courses.id')
                    ->leftJoin('sections', 'enrollments.section_id', '=', 'sections.id')
                    ->select([
                        DB::raw("CONCAT(students.first_name, ' ', students.last_name) as student_name"),
                        DB::raw("CONCAT(courses.name, ' ', sections.name) as class_name"),
                        'invoices.invoice_number',
                        "invoices.{$amountCol} as invoice_amount",
                        "invoices.{$paidCol} as paid_amount",
                        DB::raw("{$balanceExpr} as balance_due"),
                        'invoices.due_date',
                    ])
                    ->where('invoices.school_id', $schoolId)
                    ->when(! empty($filters['defaulters_only']), fn ($q) => $q->whereRaw("{$balanceExpr} > 0"))
                    ->get()
                    ->toArray();

            case 'inventory.stock_levels':
                return DB::table('inventory_items')
                    ->select([
                        'sku',
                        'name',
                        'item_type',
                        'unit_of_measure',
                        'current_quantity',
                        'reorder_level',
                        'average_unit_cost',
                    ])
                    ->where('school_id', $schoolId)
                    ->when(! empty($filters['low_stock']), fn ($q) => $q->whereRaw('current_quantity <= reorder_level'))
                    ->get()
                    ->toArray();

            case 'hostels.hostel_occupancy':
                return DB::table('hostel_allocations')
                    ->join('students', 'hostel_allocations.student_id', '=', 'students.id')
                    ->join('hostel_beds', 'hostel_allocations.bed_id', '=', 'hostel_beds.id')
                    ->join('hostel_rooms', 'hostel_beds.room_id', '=', 'hostel_rooms.id')
                    ->join('hostel_floors', 'hostel_rooms.floor_id', '=', 'hostel_floors.id')
                    ->join('hostel_wings', 'hostel_rooms.wing_id', '=', 'hostel_wings.id')
                    ->join('hostel_buildings', 'hostel_floors.building_id', '=', 'hostel_buildings.id')
                    ->join('hostels', 'hostel_buildings.hostel_id', '=', 'hostels.id')
                    ->select([
                        DB::raw("CONCAT(students.first_name, ' ', students.last_name) as student_name"),
                        'hostels.name as hostel_name',
                        'hostel_buildings.name as building_name',
                        'hostel_rooms.room_number',
                        'hostel_beds.bed_number',
                        'hostel_allocations.status as allocation_status',
                    ])
                    ->where('hostel_allocations.school_id', $schoolId)
                    ->get()
                    ->toArray();
        }

        return [];
    }
}
