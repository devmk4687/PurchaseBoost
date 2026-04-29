@php
    $pickerId = $pickerId ?? 'customer-picker';
    $fieldName = $fieldName ?? 'customer_ids';
    $label = $label ?? 'Select Loyalty Members';
    $helperText = $helperText ?? 'Search members and add them one by one.';
    $selectedIds = collect($selectedIds ?? [])->map(fn ($id) => (int) $id)->all();
    $selectedMap = collect($customers ?? [])
        ->keyBy('id')
        ->map(fn ($customer) => [
            'id' => $customer->id,
            'text' => trim($customer->customerId . ' - ' . $customer->firstName . ' ' . $customer->lastName . ' (' . $customer->email . ')'),
        ])
        ->all();
@endphp

<div class="customer-picker" data-picker-id="{{ $pickerId }}">
    <label class="form-label">{{ $label }}</label>
    <input
        type="text"
        class="form-control customer-picker-search"
        placeholder="Search by customer ID, name, or email"
        autocomplete="off"
    >

    <div class="list-group shadow-sm mt-2 d-none customer-picker-results" style="max-height: 240px; overflow-y: auto;">
        @foreach ($customers as $customer)
            <button
                type="button"
                class="list-group-item list-group-item-action customer-picker-result"
                data-id="{{ $customer->id }}"
                data-text="{{ trim($customer->customerId . ' - ' . $customer->firstName . ' ' . $customer->lastName . ' (' . $customer->email . ')') }}"
            >
                <div class="fw-semibold">{{ $customer->customerId }} - {{ $customer->firstName }} {{ $customer->lastName }}</div>
                <div class="small text-muted">{{ $customer->email }}</div>
            </button>
        @endforeach
    </div>

    <div class="border rounded bg-light p-3 mt-3">
        <div class="small text-muted mb-2">Selected members</div>
        <div class="d-flex flex-wrap gap-2 customer-picker-selected"></div>
        <div class="customer-picker-empty text-muted small {{ count($selectedIds) ? 'd-none' : '' }}">No loyalty members selected yet.</div>
    </div>

    <div class="customer-picker-inputs">
        @foreach ($selectedIds as $selectedId)
            @if (isset($selectedMap[$selectedId]))
                <input type="hidden" name="{{ $fieldName }}[]" value="{{ $selectedId }}">
            @endif
        @endforeach
    </div>

    <div
        class="customer-picker-config d-none"
        data-field-name="{{ $fieldName }}"
        data-selected='@json(array_values(array_filter(array_map(fn ($id) => $selectedMap[$id] ?? null, $selectedIds))))'
    ></div>

    <div class="form-text">{{ $helperText }}</div>
</div>
