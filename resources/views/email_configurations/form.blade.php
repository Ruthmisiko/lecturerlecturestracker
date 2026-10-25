@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="font-weight-bold">{{ $account->exists ? 'Edit Email Account' : 'Add Email Account' }}</h1>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <div class="card">
        <form method="POST" action="{{ $account->exists ? route('email-configurations.update', $account->id) : route('email-configurations.store') }}" autocomplete="off">
            @csrf
            @if ($account->exists) @method('PUT') @endif

            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-4">
                        <label>Account name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="100" value="{{ old('name', $account->name) }}" placeholder="e.g. Accounts Office">
                    </div>
                    <div class="form-group col-md-4">
                        <label>From name <span class="text-danger">*</span></label>
                        <input type="text" name="from_name" class="form-control" required maxlength="150" value="{{ old('from_name', $account->from_name) }}" placeholder="e.g. KMTC Siaya Accounts">
                    </div>
                    <div class="form-group col-md-4">
                        <label>From email address <span class="text-danger">*</span></label>
                        <input type="email" name="from_address" class="form-control" required maxlength="190" value="{{ old('from_address', $account->from_address) }}" placeholder="accounts@example.com">
                    </div>

                    <div class="col-12"><hr><h6 class="font-weight-bold">Outgoing mail server (SMTP)</h6></div>

                    <div class="form-group col-md-5">
                        <label>SMTP host <span class="text-danger">*</span></label>
                        <input type="text" name="host" class="form-control" required maxlength="190" value="{{ old('host', $account->host) }}" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Port <span class="text-danger">*</span></label>
                        <input type="number" name="port" id="port" class="form-control" required min="1" max="65535" value="{{ old('port', $account->port) }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Encryption</label>
                        <select name="encryption" id="encryption" class="form-control">
                            @foreach (\App\Models\EmailConfiguration::ENCRYPTIONS as $value => $label)
                                <option value="{{ $value }}" {{ (string) old('encryption', $account->encryption) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" maxlength="190" value="{{ old('username', $account->username) }}" autocomplete="off">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" maxlength="500" autocomplete="new-password"
                               placeholder="{{ $account->exists && $account->password ? 'Leave blank to keep the saved password' : '' }}">
                        <small class="text-muted">Stored encrypted.</small>
                    </div>
                </div>

                @unless ($account->is_default)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_default" value="1" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_default">Make this the default sending account</label>
                    </div>
                @endunless
            </div>

            <div class="card-footer">
                <button class="btn btn-success">Save</button>
                <a href="{{ route('email-configurations.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Suggest the usual port when encryption changes.
    document.getElementById('encryption').addEventListener('change', function () {
        var ports = { tls: 587, ssl: 465, '': 25 };
        document.getElementById('port').value = ports[this.value];
    });
</script>
@endpush
