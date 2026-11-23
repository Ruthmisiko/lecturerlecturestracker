@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="font-weight-bold">{{ $visit->exists ? 'Edit Staff Visit' : 'Log Staff Visit' }}</h1>
        <small class="text-muted">Work a lecturer is sent on besides lecturing (visits, exam supervision, escorting students…). It is paid by per diem, fare and meals, and cannot overlap lectures administered.</small>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <div class="card">
        <form method="POST" action="{{ $visit->exists ? route('staff-visits.update', $visit->id) : route('staff-visits.store') }}">
            @csrf
            @if ($visit->exists) @method('PUT') @endif

            <div class="card-body">
                {{-- Who --}}
                <div class="row">
                    <div class="form-group col-md-2">
                        <label>Date of Entry</label>
                        <input type="date" name="date_of_entry" class="form-control"
                               value="{{ old('date_of_entry', $visit->date_of_entry?->format('Y-m-d') ?? now()->toDateString()) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Lecturer / Staff <span class="text-danger">*</span></label>
                        <select name="lecturer_id" id="lecturer_id" class="form-control" required>
                            <option value="">Select lecturer…</option>
                            @foreach ($lecturers as $lecturer)
                                <option value="{{ $lecturer->id }}"
                                        data-personal="{{ $lecturer->personal_number }}"
                                        data-group="{{ $lecturer->job_group }}"
                                        data-rate="{{ $lecturer->per_diem_rate }}"
                                        data-dept="{{ $lecturer->department_id }}"
                                        {{ old('lecturer_id', $visit->lecturer_id) == $lecturer->id ? 'selected' : '' }}>
                                    {{ $lecturer->name }}{{ $lecturer->id_number ? ' (' . $lecturer->id_number . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Personal No.</label>
                        <input type="text" id="personal_no" class="form-control" readonly tabindex="-1" placeholder="from lecturer">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Job Group</label>
                        <input type="text" id="job_group" class="form-control" readonly tabindex="-1" placeholder="from lecturer">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Voucher No.</label>
                        <input type="text" name="voucher_number" class="form-control" maxlength="100"
                               value="{{ old('voucher_number', $visit->voucher_number) }}" placeholder="e.g. EC73380">
                    </div>
                </div>

                {{-- What --}}
                <div class="row">
                    <div class="form-group col-md-3">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-control" required>
                            @foreach (\App\Models\StaffVisit::TYPES as $key => $label)
                                <option value="{{ $key }}" {{ old('type', $visit->type) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Department (charged to)</label>
                        <select name="department_id" id="department_id" class="form-control">
                            <option value="">Lecturer's main department</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id', $visit->department_id) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Itinerary <small class="text-muted">(places)</small></label>
                        <input type="text" name="itinerary" id="itinerary" class="form-control" maxlength="255"
                               value="{{ old('itinerary', $visit->itinerary) }}" placeholder="e.g. Kisumu, Siaya and Kisii">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Who Sponsored the Activity</label>
                        <input type="text" name="sponsor" id="sponsor" class="form-control" maxlength="255"
                               value="{{ old('sponsor', $visit->sponsor) }}" placeholder="e.g. Campus">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-8">
                        <label>Purpose of Activity <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" id="purpose" class="form-control" required maxlength="255"
                               value="{{ old('purpose', $visit->purpose) }}" placeholder="e.g. Assessment of March 2024 nursing students">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Location <small class="text-muted">(optional)</small></label>
                        <input type="text" name="location" id="location" class="form-control" maxlength="255"
                               value="{{ old('location', $visit->location) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Narration
                        <button type="button" id="narrate_btn" class="btn btn-xs btn-outline-primary ml-2 py-0">
                            <i class="fas fa-magic mr-1"></i> Write with AI
                        </button>
                        <small id="narrate_msg" class="text-muted ml-2"></small>
                    </label>
                    <textarea name="narration" id="narration" rows="2" class="form-control" maxlength="1000"
                              placeholder="Left empty, it is written automatically from the purpose when you save.">{{ old('narration', $visit->narration) }}</textarea>
                </div>

                {{-- When --}}
                <div class="row">
                    <div class="form-group col-md-2">
                        <label>Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" required
                               value="{{ old('start_date', $visit->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control" required
                               value="{{ old('end_date', $visit->end_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Start Time <small class="text-muted">(optional)</small></label>
                        <input type="time" name="start_time" class="form-control"
                               value="{{ old('start_time', $visit->start_time ? substr($visit->start_time, 0, 5) : '') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>End Time <small class="text-muted">(optional)</small></label>
                        <input type="time" name="end_time" class="form-control"
                               value="{{ old('end_time', $visit->end_time ? substr($visit->end_time, 0, 5) : '') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>No. of Days</label>
                        <input type="number" min="1" max="366" name="days" id="days" class="form-control"
                               value="{{ old('days', $visit->days) }}" data-manual="{{ $visit->days_manual ? 1 : 0 }}">
                        <small id="days_hint" class="text-muted"></small>
                    </div>
                </div>
                <p class="text-muted small">Days are counted from the dates; type a number to override. Leave the times empty if the lecturer is away the whole day(s) &mdash; every lecture on those dates will then show as a clash.</p>

                {{-- Money --}}
                <div class="row">
                    <div class="form-group col-md-2">
                        <label>Rate / Day <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="per_diem_rate" id="per_diem_rate" class="form-control" required
                               value="{{ old('per_diem_rate', $visit->per_diem_rate ?? 0) }}">
                        <small class="text-muted">From the lecturer's per diem rate.</small>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Fare</label>
                        <input type="number" step="0.01" min="0" name="fare" id="fare" class="form-control"
                               value="{{ old('fare', $visit->fare ?? 0) }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Meals</label>
                        <input type="number" step="0.01" min="0" name="meals" id="meals" class="form-control"
                               value="{{ old('meals', $visit->meals ?? 0) }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Other</label>
                        <input type="number" step="0.01" min="0" name="remuneration" id="remuneration" class="form-control"
                               value="{{ old('remuneration', $visit->remuneration ?? 0) }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Total Payable</label>
                        <input type="text" id="total_preview" class="form-control font-weight-bold" readonly>
                        <small class="text-muted">days × rate + fare + meals + other</small>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Payment <span class="text-danger">*</span></label>
                        @php($status = old('status', $visit->status ?: 'pending'))
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="status_pending" name="status" value="pending" class="custom-control-input" {{ $status !== 'paid' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="status_pending">Not paid</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="status_paid" name="status" value="paid" class="custom-control-input" {{ $status === 'paid' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="status_paid">Paid</label>
                            </div>
                        </div>
                        <div id="paid_on_wrap" class="mt-1" style="{{ $status === 'paid' ? '' : 'display:none' }}">
                            <small class="text-muted">Date paid (defaults to today)</small>
                            <input type="date" name="paid_on" class="form-control"
                                   value="{{ old('paid_on', $visit->paid_on?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Remarks</label>
                    <textarea name="remarks" rows="2" class="form-control" maxlength="2000">{{ old('remarks', $visit->remarks) }}</textarea>
                </div>
            </div>

            <div class="card-footer">
                <button class="btn btn-success">{{ $visit->exists ? 'Save' : 'Log Visit' }}</button>
                <a href="{{ route('staff-visits.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const $ = id => document.getElementById(id);
        const num = id => parseFloat($(id).value) || 0;

        function autoDays() {
            const s = $('start_date').value, e = $('end_date').value;
            return (s && e) ? Math.max(1, Math.round((new Date(e) - new Date(s)) / 86400000) + 1) : 1;
        }

        function update() {
            const auto = autoDays();
            if ($('days').dataset.manual !== '1') $('days').value = auto;
            const days = parseInt($('days').value) || auto;
            $('days_hint').textContent = $('days').dataset.manual === '1' && days !== auto
                ? 'Typed (dates give ' + auto + ') — clear to use dates' : 'From the dates';
            const total = days * num('per_diem_rate') + num('fare') + num('meals') + num('remuneration');
            $('total_preview').value = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Typing a day count overrides the dates; clearing it goes back to automatic.
        $('days').addEventListener('input', () => { $('days').dataset.manual = $('days').value === '' ? '0' : '1'; update(); });
        ['start_date', 'end_date', 'per_diem_rate', 'fare', 'meals', 'remuneration'].forEach(id => $(id).addEventListener('input', update));
        $('start_date').addEventListener('change', () => { if (!$('end_date').value) $('end_date').value = $('start_date').value; update(); });

        // Lecturer prefill: personal no., job group, per diem rate and department.
        function lecturerChanged(initial) {
            const o = $('lecturer_id').selectedOptions[0];
            $('personal_no').value = o && o.dataset.personal ? o.dataset.personal : '';
            $('job_group').value = o && o.dataset.group ? o.dataset.group : '';
            if (initial || !o || !o.value) return;
            if (o.dataset.rate) $('per_diem_rate').value = o.dataset.rate;
            update();
        }
        $('lecturer_id').addEventListener('change', () => lecturerChanged(false));
        lecturerChanged(true);

        // AI narration.
        $('narrate_btn').addEventListener('click', async function () {
            const msg = $('narrate_msg');
            const form = this.form;
            const body = new FormData();
            ['lecturer_id', 'type', 'purpose', 'location', 'itinerary', 'sponsor', 'start_date', 'end_date'].forEach(n => body.append(n, form.elements[n].value));
            msg.textContent = 'Writing…';
            this.disabled = true;
            try {
                const res = await fetch(@json(route('staff-visits.narrate')), {
                    method: 'POST', body,
                    headers: {'X-CSRF-TOKEN': form.elements['_token'].value, 'Accept': 'application/json'},
                });
                const data = await res.json();
                if (!res.ok) {
                    msg.textContent = (data.errors ? Object.values(data.errors)[0][0] : data.message) || 'Could not write the narration.';
                } else {
                    $('narration').value = data.text;
                    msg.textContent = data.ai ? 'Written by AI — edit if needed.' : 'AI is not available right now; a standard wording was used.';
                }
            } catch (e) {
                msg.textContent = 'Could not reach the server.';
            }
            this.disabled = false;
        });

        document.querySelectorAll('input[name="status"]').forEach(r => r.addEventListener('change', () => {
            $('paid_on_wrap').style.display = $('status_paid').checked ? '' : 'none';
        }));

        update();
    })();
</script>
@endpush
