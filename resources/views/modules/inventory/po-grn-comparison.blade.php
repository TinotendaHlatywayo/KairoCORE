@php
    /** @var \Modules\Inventory\Models\ProcurementOrder $po */
    $po = $po ?? ($record ?? null);
@endphp
@if (! $po)
    <div class="rounded-xl border border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-400">
        {{ __('No purchase order selected.') }}
    </div>
@else
<div class="space-y-6">
    @php
        /** @var \Modules\Inventory\Models\ProcurementOrder $po */
        $lines = collect([]);

        foreach ($po->items as $poItem) {
            $received = 0;
            $rejected = 0;
            $grnCount = 0;

            foreach ($po->grns as $grn) {
                foreach ($grn->items as $grnItem) {
                    if ((int) $grnItem->inventory_item_id === (int) $poItem->inventory_item_id) {
                        $received += (int) $grnItem->quantity_accepted;
                        $rejected += (int) $grnItem->quantity_rejected;
                        $grnCount++;
                    }
                }
            }

            $ordered = (int) $poItem->quantity_ordered;
            $outstanding = max(0, $ordered - $received);

            if ($ordered === 0) {
                $state = 'pending';
                $stateLabel = __('Pending');
                $stateColor = 'bg-gray-100 text-gray-700';
            } elseif ($received >= $ordered) {
                $state = 'complete';
                $stateLabel = __('Complete');
                $stateColor = 'bg-emerald-100 text-emerald-700';
            } elseif ($received > 0) {
                $state = 'partial';
                $stateLabel = __('Partial');
                $stateColor = 'bg-amber-100 text-amber-700';
            } else {
                $state = 'pending';
                $stateLabel = __('Not Received');
                $stateColor = 'bg-gray-100 text-gray-700';
            }

            $lines->push([
                'item' => $poItem->inventoryItem?->name ?? __('Unlinked item'),
                'ordered' => $ordered,
                'received' => $received,
                'rejected' => $rejected,
                'outstanding' => $outstanding,
                'grn_count' => $grnCount,
                'state' => $state,
                'state_label' => $stateLabel,
                'state_color' => $stateColor,
            ]);
        }
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 bg-gray-50">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">
                    {{ __('Goods Received vs Purchase Order Comparison') }}
                </h3>
                <p class="text-xs text-gray-500">{{ $po->order_number }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold" style="color:#5b4fe9;">
                    {{ $lines->where('state', 'complete')->count() }}/{{ $lines->count() }}
                    {{ __('lines complete') }}
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100">
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="px-5 py-3 font-semibold">{{ __('Item') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('Ordered') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('Received') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('Rejected') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('Outstanding') }}</th>
                        <th class="px-4 py-3 text-center font-semibold">{{ __('GRN Records') }}</th>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($lines as $line)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $line['item'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $line['ordered'] }}</td>
                            <td class="px-4 py-3 text-center font-semibold" style="color:#5b4fe9;">{{ $line['received'] }}</td>
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
                            <td colspan="7" class="px-5 py-8 text-center text-gray-400">
                                {{ __('This purchase order has no ordered items yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold text-gray-700">
                        <td class="px-5 py-3">{{ __('Totals') }}</td>
                        <td class="px-4 py-3 text-center">{{ $lines->sum('ordered') }}</td>
                        <td class="px-4 py-3 text-center" style="color:#5b4fe9;">{{ $lines->sum('received') }}</td>
                        <td class="px-4 py-3 text-center">{{ $lines->sum('rejected') }}</td>
                        <td class="px-4 py-3 text-center">{{ $lines->sum('outstanding') }}</td>
                        <td class="px-4 py-3 text-center">{{ $lines->sum('grn_count') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endif