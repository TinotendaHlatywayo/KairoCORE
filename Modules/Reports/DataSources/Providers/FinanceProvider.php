<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class FinanceProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return __('Finance & Fees');
    }

    public function datasets(): array
    {
        $inv = 'finance_invoice';

        return [
            $this->d('finance.invoice', __('Invoices'), 'invoices', [
                $this->f('invoice_number', __('Invoice Reference')),
                $this->f('currency', __('Currency')),
                $this->f('subtotal_amount', __('Subtotal'), 'currency'),
                $this->f('discount_amount', __('Discount'), 'currency'),
                $this->f('total_amount', __('Total Amount'), 'currency'),
                $this->f('paid_amount', __('Amount Paid'), 'currency'),
                $this->f('balance_amount', __('Balance Outstanding'), 'currency'),
                $this->f('status', __('Status')),
                $this->f('due_date', __('Due Date'), 'date'),
                $this->f('created_at', __('Invoice Date'), 'datetime'),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT({$inv}_st.first_name, ' ', {$inv}_st.last_name)"),
                $this->f('class_name', __('Class Stream'), 'string', "CONCAT({$inv}_course.name, ' ', {$inv}_section.name)"),
                $this->f('term_name', __('Term'), 'string', "{$inv}_term.name"),
                $this->f('is_overdue', __('Is Overdue'), 'boolean', "CASE WHEN {$inv}.balance_amount > 0 AND {$inv}.due_date < CURRENT_DATE THEN 1 ELSE 0 END"),
            ], [
                'description' => __('Fee invoices with student, class and term context.'),
                'autoJoins' => [
                    ['alias' => "{$inv}_st", 'table' => 'students', 'type' => 'left', 'on' => [["{$inv}_st.id", "{$inv}.student_id"]]],
                    ['alias' => "{$inv}_enr", 'table' => 'enrollments', 'type' => 'left', 'on' => [["{$inv}_enr.student_id", "{$inv}_st.id"]], 'latest' => true],
                    ['alias' => "{$inv}_course", 'table' => 'courses', 'type' => 'left', 'on' => [["{$inv}_course.id", "{$inv}_enr.course_id"]]],
                    ['alias' => "{$inv}_section", 'table' => 'sections', 'type' => 'left', 'on' => [["{$inv}_section.id", "{$inv}_enr.section_id"]]],
                    ['alias' => "{$inv}_term", 'table' => 'terms', 'type' => 'left', 'on' => [["{$inv}_term.id", "{$inv}.term_id"]]],
                ],
                'connections' => [
                    $this->connect('students.register', "{$inv}.student_id", 'students_register.id'),
                    $this->connect('finance.payment', "{$inv}.id", 'finance_payment.invoice_id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Invoice Status'), 'type' => 'select', 'options' => ['paid', 'partial', 'unpaid', 'cancelled']],
                    ['key' => 'is_overdue', 'label' => __('Overdue Only'), 'type' => 'select', 'options' => [1 => 'Overdue', 0 => 'Not overdue']],
                ],
            ]),

            $this->d('finance.balance', __('Fee Balance Summary (per student)'), 'SELECT
                    student_id AS student_id,
                    COUNT(*) AS invoice_count,
                    SUM(total_amount) AS total_invoiced,
                    SUM(paid_amount) AS total_paid,
                    SUM(balance_amount) AS outstanding_balance,
                    SUM(CASE WHEN balance_amount > 0 THEN 1 ELSE 0 END) AS unpaid_invoice_count
                FROM invoices
                WHERE school_id = {school_id}
                GROUP BY student_id', [
                $this->f('invoice_count', __('Invoice Count'), 'integer'),
                $this->money('total_invoiced', __('Total Invoiced'), 'finance_balance.total_invoiced'),
                $this->money('total_paid', __('Total Paid'), 'finance_balance.total_paid'),
                $this->money('outstanding_balance', __('Outstanding Balance'), 'finance_balance.outstanding_balance'),
                $this->f('unpaid_invoice_count', __('Unpaid Invoices'), 'integer'),
                $this->f('has_outstanding', __('Has Outstanding Balance'), 'boolean', 'CASE WHEN finance_balance.outstanding_balance > 0 THEN 1 ELSE 0 END'),
            ], [
                'description' => __('Aggregated fee position per student — the backbone of defaulter and revenue reports.'),
                'connections' => [
                    $this->connect('students.register', 'finance_balance.student_id', 'students_register.id'),
                    $this->connect('attendance.summary', 'finance_balance.student_id', 'attendance_summary.student_id'),
                ],
            ]),

            $this->d('finance.payment', __('Payments'), 'payments', [
                $this->f('receipt_number', __('Receipt Number')),
                $this->f('reference_number', __('Reference Number')),
                $this->money('amount', __('Payment Amount'), 'finance_payment.amount'),
                $this->f('currency', __('Currency')),
                $this->f('payment_method', __('Payment Method')),
                $this->f('payment_date', __('Payment Date'), 'date'),
                $this->f('is_reversed', __('Reversed'), 'boolean'),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(finance_payment_st.first_name, ' ', finance_payment_st.last_name)"),
                $this->f('invoice_number', __('Invoice Reference'), 'string', 'finance_payment_inv.invoice_number'),
            ], [
                'description' => __('Payment receipts with student and invoice context.'),
                'autoJoins' => [
                    ['alias' => 'finance_payment_inv', 'table' => 'invoices', 'type' => 'left', 'on' => [['finance_payment_inv.id', 'finance_payment.invoice_id']]],
                    ['alias' => 'finance_payment_st', 'table' => 'students', 'type' => 'left', 'on' => [['finance_payment_st.id', 'finance_payment_inv.student_id']]],
                ],
                'connections' => [
                    $this->connect('finance.invoice', 'finance_payment.invoice_id', 'finance_invoice.id'),
                ],
            ]),

            $this->d('finance.fee_structure', __('Fee Structures'), 'fee_structures', [
                $this->f('scope_type', __('Scope Type')),
                $this->money('amount', __('Fee Amount'), 'finance_fee_structure.amount'),
                $this->f('currency', __('Currency')),
                $this->f('category_name', __('Fee Category'), 'string', 'finance_fee_cat.name'),
                $this->f('course_name', __('Course'), 'string', 'finance_fee_course.name'),
                $this->f('term_name', __('Term'), 'string', 'finance_fee_term.name'),
            ], [
                'description' => __('Configured fee structures per category, course and term.'),
                'autoJoins' => [
                    ['alias' => 'finance_fee_cat', 'table' => 'fee_categories', 'type' => 'left', 'on' => [['finance_fee_cat.id', 'finance_fee_structure.fee_category_id']]],
                    ['alias' => 'finance_fee_course', 'table' => 'courses', 'type' => 'left', 'on' => [['finance_fee_course.id', 'finance_fee_structure.course_id']]],
                    ['alias' => 'finance_fee_term', 'table' => 'terms', 'type' => 'left', 'on' => [['finance_fee_term.id', 'finance_fee_structure.term_id']]],
                ],
            ]),

            $this->d('finance.payment_plan', __('Payment Plans'), 'payment_plans', [
                $this->f('proposed_installments_count', __('Installments'), 'integer'),
                $this->f('installment_amount', __('Installment Amount'), 'currency'),
                $this->f('status', __('Status')),
                $this->f('parent_notes', __('Parent Notes')),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(finance_payment_plan_st.first_name, ' ', finance_payment_plan_st.last_name)"),
            ], [
                'description' => __('Student payment plans with approval status.'),
                'autoJoins' => [
                    ['alias' => 'finance_payment_plan_st', 'table' => 'students', 'type' => 'left', 'on' => [['finance_payment_plan_st.id', 'finance_payment_plan.student_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'finance_payment_plan.student_id', 'students_register.id'),
                ],
            ]),

            $this->d('finance.revenue_summary', __('Revenue Summary (per month)'), 'SELECT
                    DATE_FORMAT(payment_date, \'%Y-%m\') AS month,
                    COUNT(*) AS payment_count,
                    SUM(CASE WHEN is_reversed = 0 THEN amount ELSE 0 END) AS net_collected,
                    SUM(CASE WHEN payment_method = \'cash\' AND is_reversed = 0 THEN amount ELSE 0 END) AS cash_collected,
                    SUM(CASE WHEN payment_method <> \'cash\' AND is_reversed = 0 THEN amount ELSE 0 END) AS other_collected,
                    SUM(CASE WHEN is_reversed = 1 THEN amount ELSE 0 END) AS reversed_amount,
                    COUNT(CASE WHEN is_reversed = 1 THEN 1 END) AS reversed_count
                FROM payments
                WHERE school_id = {school_id}
                GROUP BY DATE_FORMAT(payment_date, \'%Y-%m\')', [
                $this->f('month', __('Month'), 'string'),
                $this->f('payment_count', __('Payment Count'), 'integer'),
                $this->money('net_collected', __('Net Collected'), 'finance_revenue_summary.net_collected'),
                $this->money('cash_collected', __('Cash Collected'), 'finance_revenue_summary.cash_collected'),
                $this->money('other_collected', __('Non-Cash Collected'), 'finance_revenue_summary.other_collected'),
                $this->money('reversed_amount', __('Reversed Amount'), 'finance_revenue_summary.reversed_amount'),
                $this->f('reversed_count', __('Reversed Transactions'), 'integer'),
            ], [
                'description' => __('Monthly collections net of reversals — the basis of cash-flow and bursary reporting.'),
                'default_order' => 'month|asc',
            ]),
        ];
    }
}
