@php
    $step3 = $campaign->config['step3'] ?? [];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="mb-1">{{ $campaign->exists ? 'Edit Campaign' : 'Create Campaign' }}</h4>
                <p class="text-muted mb-0">Configure the campaign in 3 steps: customers, message template, and delivery.</p>
            </div>
            @if ($campaign->exists)
                <div class="text-end">
                    <div class="small text-muted">Current status</div>
                    <span class="badge {{ $campaign->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $campaign->is_active ? 'Published' : 'Draft' }}
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Campaign Details</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Campaign Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $campaign->start_date) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $campaign->end_date) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $campaign->description) }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Step 1: Select Customers</h5>
            <span class="badge bg-light text-dark">{{ count($selectedCustomerIds) }} selected</span>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Saved Segment</label>
                <select name="config[step1][segment_id]" class="form-select" id="campaign-segment-select">
                    <option value="">Choose saved segment</option>
                    @foreach ($segments as $segment)
                        <option
                            value="{{ $segment->id }}"
                            data-member-ids='@json($segment->members->pluck('id')->map(fn ($id) => (int) $id)->values())'
                            @selected(($selectedSegmentId ?? 0) === $segment->id)
                        >
                            {{ $segment->name }} ({{ $segment->members_count }} members)
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Selecting a segment will add its customers into this campaign audience.</div>
            </div>
        </div>
        @include('partials.customer-picker', [
            'pickerId' => 'campaign-customer-picker',
            'fieldName' => 'config[step1][customer_ids]',
            'label' => 'Choose one or more customers',
            'helperText' => 'Search loyalty members and add them one by one to this campaign.',
            'selectedIds' => $selectedCustomerIds,
            'customers' => $customers,
        ])
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Step 2: Select Message Template</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Channel</label>
                <select name="config[step2][channel]" class="form-select" required>
                    @foreach (['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $value => $label)
                        <option value="{{ $value }}" @selected($selectedChannel === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Template</label>
                <select name="config[step2][template_id]" class="form-select" id="campaign-template-select" required>
                    <option value="">Select template</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}" data-channel="{{ $template->channel }}" @selected($selectedTemplateId === $template->id)>
                            {{ strtoupper($template->channel) }} - {{ $template->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Templates should match the selected channel. Placeholders supported: `{{ '{firstName}' }}`, `{{ '{lastName}' }}`, `{{ '{email}' }}` and more.</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Step 3: Run and Track Delivery</h5>
            @if (!empty($step3['executed_at']))
                <span class="badge bg-info text-dark">Last run: {{ $step3['executed_at'] }}</span>
            @endif
        </div>

        @if (!empty($step3['summary']))
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-light">
                        <div class="small text-muted">Total</div>
                        <div class="fs-4 fw-semibold">{{ $step3['summary']['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-success-subtle">
                        <div class="small text-muted">Sent</div>
                        <div class="fs-4 fw-semibold">{{ $step3['summary']['sent'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-danger-subtle">
                        <div class="small text-muted">Failed</div>
                        <div class="fs-4 fw-semibold">{{ $step3['summary']['failed'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $campaign->is_active) ? 'checked' : '' }}>
            <label class="form-check-label">Mark campaign as active after saving</label>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary">{{ $campaign->exists ? 'Update Campaign' : 'Save Campaign' }}</button>
            <a href="{{ route('campaigns.index') }}" class="btn btn-outline-secondary">Back</a>
            @if ($campaign->exists)
                <button type="submit" form="run-campaign-form" class="btn btn-success">Run Campaign Now</button>
            @endif
        </div>
    </div>
</div>

@if ($campaign->exists)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Recent Delivery Logs</h5>
            @if ($logs->isEmpty())
                <p class="text-muted mb-0">No delivery logs yet. Save the campaign and run it to start sending messages.</p>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Channel</th>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td>{{ optional($log->customer)->firstName }} {{ optional($log->customer)->lastName }}</td>
                                    <td>{{ strtoupper($log->channel) }}</td>
                                    <td>{{ $log->recipient }}</td>
                                    <td>
                                        <span class="badge {{ $log->status === 'sent' ? 'bg-success' : 'bg-danger' }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                        @if ($log->error_message)
                                            <div class="small text-danger mt-1">{{ $log->error_message }}</div>
                                        @endif
                                    </td>
                                    <td>{{ optional($log->sent_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var channelSelect = document.querySelector('select[name="config[step2][channel]"]');
        var templateSelect = document.getElementById('campaign-template-select');
        var segmentSelect = document.getElementById('campaign-segment-select');
        var customerPickers = document.querySelectorAll('.customer-picker');

        if (!channelSelect || !templateSelect) {
            return;
        }

        function filterTemplates() {
            var selectedChannel = channelSelect.value;
            var hasVisibleSelected = false;

            Array.from(templateSelect.options).forEach(function (option, index) {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                var matches = option.dataset.channel === selectedChannel;
                option.hidden = !matches;

                if (option.selected && matches) {
                    hasVisibleSelected = true;
                }
            });

            if (!hasVisibleSelected) {
                templateSelect.value = '';
            }
        }

        channelSelect.addEventListener('change', filterTemplates);
        filterTemplates();

        customerPickers.forEach(function (picker) {
            var searchInput = picker.querySelector('.customer-picker-search');
            var results = picker.querySelector('.customer-picker-results');
            var resultButtons = picker.querySelectorAll('.customer-picker-result');
            var selectedContainer = picker.querySelector('.customer-picker-selected');
            var inputsContainer = picker.querySelector('.customer-picker-inputs');
            var emptyState = picker.querySelector('.customer-picker-empty');
            var config = picker.querySelector('.customer-picker-config');
            var fieldName = config.dataset.fieldName;
            var selectedItems = {};

            try {
                JSON.parse(config.dataset.selected || '[]').forEach(function (item) {
                    selectedItems[item.id] = item;
                });
            } catch (error) {
                selectedItems = {};
            }

            function renderSelected() {
                selectedContainer.innerHTML = '';
                inputsContainer.innerHTML = '';

                Object.values(selectedItems).forEach(function (item) {
                    var badge = document.createElement('span');
                    badge.className = 'badge bg-primary-subtle text-dark d-inline-flex align-items-center gap-2 px-3 py-2';
                    badge.innerHTML = '<span>' + item.text + '</span>';

                    var removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'btn-close btn-close-sm';
                    removeButton.setAttribute('aria-label', 'Remove');
                    removeButton.addEventListener('click', function () {
                        delete selectedItems[item.id];
                        renderSelected();
                        filterResults();
                    });

                    badge.appendChild(removeButton);
                    selectedContainer.appendChild(badge);

                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = fieldName + '[]';
                    input.value = item.id;
                    inputsContainer.appendChild(input);
                });

                emptyState.classList.toggle('d-none', Object.keys(selectedItems).length > 0);
            }

            function filterResults() {
                var term = searchInput.value.toLowerCase().trim();
                var visibleCount = 0;

                resultButtons.forEach(function (button) {
                    var text = button.dataset.text.toLowerCase();
                    var isSelected = Boolean(selectedItems[button.dataset.id]);
                    var matches = term === '' || text.indexOf(term) !== -1;
                    var show = matches && !isSelected;

                    button.classList.toggle('d-none', !show);

                    if (show) {
                        visibleCount++;
                    }
                });

                results.classList.toggle('d-none', term === '' || visibleCount === 0);
            }

            resultButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    selectedItems[button.dataset.id] = {
                        id: parseInt(button.dataset.id, 10),
                        text: button.dataset.text,
                    };

                    searchInput.value = '';
                    renderSelected();
                    filterResults();
                });
            });

            searchInput.addEventListener('input', filterResults);
            searchInput.addEventListener('focus', filterResults);
            document.addEventListener('click', function (event) {
                if (!picker.contains(event.target)) {
                    results.classList.add('d-none');
                }
            });

            renderSelected();

            picker.customerPickerApi = {
                addByIds: function (ids) {
                    ids.forEach(function (id) {
                        var button = picker.querySelector('.customer-picker-result[data-id="' + id + '"]');

                        if (button) {
                            selectedItems[button.dataset.id] = {
                                id: parseInt(button.dataset.id, 10),
                                text: button.dataset.text,
                            };
                        }
                    });

                    renderSelected();
                    filterResults();
                }
            };
        });

        if (segmentSelect && customerPickers.length > 0) {
            var pickerApi = customerPickers[0].customerPickerApi;

            segmentSelect.addEventListener('change', function () {
                var selectedOption = segmentSelect.options[segmentSelect.selectedIndex];

                if (!selectedOption || !selectedOption.dataset.memberIds || !pickerApi) {
                    return;
                }

                try {
                    pickerApi.addByIds(JSON.parse(selectedOption.dataset.memberIds));
                } catch (error) {
                    console.error(error);
                }
            });

            if (segmentSelect.value) {
                segmentSelect.dispatchEvent(new Event('change'));
            }
        }
    });
</script>
