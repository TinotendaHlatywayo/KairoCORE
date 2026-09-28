@php
    /** @var \Modules\Inventory\Models\ProcurementOrder|null $po */
    $po = $po ?? ($record ?? null);

    /**
     * The comparison rows are built by ProcurementOrder::receivingComparison()
     * so this screen, the printed PDF and the goods received form can never
     * disagree. It is queried again here only when the caller did not supply
     * them, so the table is never silently empty.
     */
    $rows = $rows ?? ($po?->receivingComparison() ?? []);

    $completeCount = collect($rows)->where('state', 'complete')->count();
@endphp
@if (! $po)
    <div class="rounded-xl border border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-400">
        {{ __('No purchase order selected.') }}
    </div>
@else
<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 bg-gray-50">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">
                    {{ __('Ordered Items and Goods Received') }}
                </h3>
                <p class="text-xs text-gray-500">
                    {{ $po->order_number }}
                    @if ($po->supplier)
                        &middot; {{ $po->supplier->name }}
                    @endif
                </p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold" style="color:#5b4fe9;">
                    {{ $completeCount }}/{{ count($rows) }}
                    {{ __('lines complete') }}
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="px-5 py-3 font-semibold">{{ __('Item') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('Unit Cost') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('Line Total') }}</th>
                        <th class="border-l-2 border-gray-200 px-4 py-3 text-center font-semibold">{{ __('Ordered') }}</th>
                        <th class="border-l-2 border-gray-200 px-4 py-3 text-center font-semibold">{{ __('Received') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('Rejected') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('Outstanding') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('GRN Records') }}</th>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($rows as $line)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">
                                {{ $line['item'] }}
                                @if ($line['is_fixed_asset'])
                                    <span class="ml-1 inline-flex rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium uppercase text-indigo-600">
                                        {{ __('Asset') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-600">{{ '$'.number_format($line['unit_cost'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-700">{{ '$'.number_format($line['line_total'], 2) }}</td>
                            <td class="border-l-2 border-gray-200 px-4 py-3 text-center">{{ $line['ordered'] }}</td>
                            <td class="border-l-2 border-gray-200 px-4 py-3 text-center font-semibold" style="color:#5b4fe9;">{{ $line['received'] }}</td>
                            <td class="px-4 py-3 text-center {{ $line['rejected'] > 0 ? 'text-red-600 font-semibold' : 'text-gray-500' }}">{{ $line['rejected'] }}</td>
                            <td class="px-4 py-3 text-center {{ $line['outstanding'] > 0 ? 'text-amber-600 font-semibold' : 'text-gray-500' }}">{{ $line['outstanding'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $line['grn_count'] }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $line['state_color'] }}">
                                    {{ $line['state_label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-gray-400">
                                {{ __('This purchase order has no ordered items yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold text-gray-700">
                        <td class="px-5 py-3">{{ __('Totals') }}</td>
                        <td class="px-4 py-3 text-right"></td>
                        <td class="px-4 py-3 text-right">{{ '$'.number_format(collect($rows)->sum('line_total'), 2) }}</td>
                        <td class="border-l-2 border-gray-200 px-4 py-3 text-center">{{ collect($rows)->sum('ordered') }}</td>
                        <td class="border-l-2 border-gray-200 px-4 py-3 text-center" style="color:#5b4fe9;">{{ collect($rows)->sum('received') }}</td>
                        <td class="px-4 py-3 text-center">{{ collect($rows)->sum('rejected') }}</td>
                        <td class="px-4 py-3 text-center">{{ collect($rows)->sum('outstanding') }}</td>
                        <td class="px-4 py-3 text-center">{{ collect($rows)->sum('grn_count') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif
