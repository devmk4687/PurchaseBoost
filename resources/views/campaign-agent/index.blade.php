@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1">Campaign Agent</h3>
        <p class="text-muted mb-0">Describe the campaign request, let the multi-agent flow generate it, and optionally send it immediately.</p>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">New Agent Request</h5>
                <form method="POST" action="{{ route('campaign-agent.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Campaign Goal</label>
                        <textarea name="goal" rows="5" class="form-control" placeholder="Example: Create a re-engagement email campaign for inactive loyalty members with a 10% coupon and send it today." required>{{ old('goal') }}</textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Channel</label>
                            <select name="channel" class="form-select" id="agent-channel-select" required>
                                @foreach (['email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('channel', 'email') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Template</label>
                            <select name="template_id" class="form-select" id="agent-template-select">
                                <option value="">Auto-generate template</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" data-channel="{{ $template->channel }}" @selected((int) old('template_id', 0) === $template->id)>
                                        {{ strtoupper($template->channel) }} - {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Saved Segment</label>
                            <select name="segment_id" class="form-select" id="agent-segment-select">
                                <option value="">Choose saved segment</option>
                                @foreach ($segments as $segment)
                                    <option
                                        value="{{ $segment->id }}"
                                        data-member-ids='@json($segment->members->pluck('id')->map(fn ($id) => (int) $id)->values())'
                                        @selected((int) request('segment_id', old('segment_id', 0)) === $segment->id)
                                    >
                                        {{ $segment->name }} ({{ $segment->members_count }} members)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        @include('partials.customer-picker', [
                            'pickerId' => 'campaign-agent-customer-picker',
                            'fieldName' => 'customer_ids',
                            'label' => 'Select Loyalty Members',
                            'helperText' => 'Search members and add them one by one for this agent request.',
                            'selectedIds' => old('customer_ids', []),
                            'customers' => $customers,
                        ])
                    </div>

                    <div class="form-check mt-3">
                        <input type="checkbox" name="send_now" value="1" class="form-check-input" id="send-now-check" {{ old('send_now') ? 'checked' : '' }}>
                        <label class="form-check-label" for="send-now-check">Send campaign immediately after generation</label>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Run Campaign Agent</button>
                        <a href="{{ route('campaign-agent.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Latest Agent Result</h5>

                @if (!$agentResult)
                    <p class="text-muted mb-0">No agent run yet. Submit a request to create a campaign plan and delivery result.</p>
                @else
                    @php
                        $plan = $agentResult['plan'] ?? [];
                        $result = $agentResult['result'] ?? [];
                        $delivery = $result['delivery'] ?? null;
                    @endphp

                    <div class="mb-3">
                        <div class="small text-muted">Generated Campaign</div>
                        <div class="fw-semibold">{{ $result['campaign_name'] ?? ($plan['campaign']['name'] ?? '-') }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Description</div>
                        <div>{{ $plan['campaign']['description'] ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Channel</div>
                        <div>{{ strtoupper($plan['channel'] ?? '-') }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Audience</div>
                        <div>{{ $result['audience_count'] ?? 0 }} loyalty members</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Subject</div>
                        <div>{{ $plan['campaign']['subject'] ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Message Body</div>
                        <div class="border rounded bg-light p-3" style="white-space: pre-wrap;">{{ $plan['campaign']['body'] ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Created Campaign ID</div>
                        <div>{{ $result['campaign_id'] ?? '-' }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Template ID</div>
                        <div>{{ $result['template_id'] ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="small text-muted">Delivery Status</div>
                        @if ($delivery)
                            <div>Sent: {{ $delivery['sent'] ?? 0 }} / Failed: {{ $delivery['failed'] ?? 0 }}</div>
                        @else
                            <div>Campaign prepared only. Send now was not enabled.</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Agent Run History</h5>
                <p class="text-muted mb-0">Recent campaign-generation requests and delivery outcomes.</p>
            </div>
        </div>

        @if ($agentLogs->isEmpty())
            <p class="text-muted mb-0">No campaign agent history yet.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Goal</th>
                            <th>Campaign</th>
                            <th>Channel</th>
                            <th>Audience</th>
                            <th>Delivery</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agentLogs as $log)
                            @php
                                $plan = $log->plan ?? [];
                                $result = $log->result ?? [];
                                $delivery = $result['delivery'] ?? null;
                            @endphp
                            <tr>
                                <td style="min-width: 260px;">{{ $log->goal }}</td>
                                <td>{{ $result['campaign_name'] ?? ($plan['campaign']['name'] ?? '-') }}</td>
                                <td>{{ strtoupper($plan['channel'] ?? '-') }}</td>
                                <td>{{ $result['audience_count'] ?? 0 }}</td>
                                <td>
                                    @if ($delivery)
                                        <span class="badge bg-success-subtle text-dark">Sent {{ $delivery['sent'] ?? 0 }}</span>
                                        <span class="badge bg-danger-subtle text-dark">Failed {{ $delivery['failed'] ?? 0 }}</span>
                                    @else
                                        <span class="badge bg-secondary">Prepared</span>
                                    @endif
                                </td>
                                <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                <p class="text-muted mb-0">
                    Showing {{ $agentLogs->firstItem() ?? 0 }} to {{ $agentLogs->lastItem() ?? 0 }} of {{ $agentLogs->total() }} runs
                </p>
                <div>
                    {{ $agentLogs->onEachSide(1)->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var channelSelect = document.getElementById('agent-channel-select');
        var templateSelect = document.getElementById('agent-template-select');
        var segmentSelect = document.getElementById('agent-segment-select');
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
@endsection
