{{-- Invoice cards – card-based rendering of the Student Invoices table,
     matching the card design language used across the system. --}}
@include('components.table-card-styles')

@if ($records->isEmpty())
    <div class="fi-ta-empty-state px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('No invoices found.') }}
    </div>
@else
    <div class="p-4">
        <div class="sc-card-grid">
            @foreach ($records as $invoice)
                @php
                    $statusColor = match ($invoice->status) {
                        'paid' => '#10b981',
                        'partially_paid' => '#f59e0b',
                        'unpaid' => '#ef4444',
                        default => '#64748b',
                    };

                    $student = $invoice->student;
                    $studentName = $student
                        ? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))
                        : 'Unknown Student';

                    $className = $invoice->student?->currentEnrollment
                        ? trim(($invoice->student->currentEnrollment->course?->name ?? '') . ' ' . ($invoice->student->currentEnrollment->section?->name ?? ''))
                        : '';
                    $className = $className ?: 'Unassigned';

                    $termName = $invoice->term?->name ? ucwords(strtolower($invoice->term->name)) : '—';

                    $statusLabel = ucwords(str_replace('_', ' ', $invoice->status));
                    $recordKey = $invoice->getKey();

                    $studentPhoto = resolve_public_asset_path($student->photo_path ?? null);
                @endphp
                <div class="sc-card" style="--sc-card-color: {{ $statusColor }};">
                    <span class="sc-card-status">{{ $statusLabel }}</span>

                    <div class="sc-card-avatar" style="background: conic-gradient(from 180deg, {{ $statusColor }}, #2dd4bf, {{ $statusColor }});">
                        @if($studentPhoto)
                            <img src="{{ asset($studentPhoto) }}" alt="{{ $studentName }}" class="sc-avatar-inner" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        @else
                            <span class="sc-avatar-inner" style="color: {{ $statusColor }};">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="34" height="34"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4M10 12h4M10 16h4"/></svg>
                            </span>
                        @endif
                    </div>

                    <div class="sc-card-name">{{ $studentName }}</div>
                    <div class="sc-card-sub">{{ $className }}</div>

                    <div class="sc-card-meta">
                        <div>
                            <span class="sc-m-label">{{ __('Invoice No.') }}</span>
                            <span class="sc-m-value">{{ $invoice->invoice_number }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Term') }}</span>
                            <span class="sc-m-value">{{ $termName }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Total') }}</span>
                            <span class="sc-m-value">{{ '$' . number_format((float) $invoice->total_amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Paid') }}</span>
                            <span class="sc-m-value" style="color: #059669;">{{ '$' . number_format((float) $invoice->paid_amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Balance') }}</span>
                            <span class="sc-m-value" style="color: {{ $invoice->balance_amount > 0 ? '#dc2626' : '#334155' }};">{{ '$' . number_format((float) $invoice->balance_amount, 2) }}</span>
                        </div>
                        <div>
                            <span class="sc-m-label">{{ __('Billed') }}</span>
                            <span class="sc-m-value">{{ $invoice->created_at?->format('d M Y') ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="sc-card-actions">
                        <button type="button" class="sc-btn-enroll" wire:click="mountTableAction('recordPayment', '{{ $recordKey }}')">{{ __('Pay') }}</button>
                        <a href="{{ \App\Filament\App\Resources\InvoiceResource::getUrl('edit', ['record' => $invoice]) }}" class="sc-btn-edit">{{ __('Edit') }}</a>
                        <a href="{{ route('invoice.pdf', ['record' => $invoice->id], false) }}" target="_blank" class="sc-btn-view">{{ __('Invoice') }}</a>
                        @if($invoice->paid_amount > 0)
                            <a href="{{ route('receipt.pdf', ['record' => $invoice->id], false) }}" target="_blank" class="sc-btn-view">{{ __('Receipt') }}</a>
                        @endif
                        <a href="{{ route('statement.pdf', ['record' => $invoice->id], false) }}" target="_blank" class="sc-btn-view">{{ __('Statement') }}</a>
                        @if($invoice->discount_amount > 0)
                            <span class="sc-btn-view" style="background: #10b981; color: white; cursor: default; font-size: 10px;" title="{{ $invoice->waiver_details ?? 'Waiver applied' }}">{{ __('Waiver') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
