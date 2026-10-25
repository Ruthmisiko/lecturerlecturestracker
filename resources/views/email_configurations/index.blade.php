@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Email Settings</h1>
            <small class="text-muted">Accounts payslips are sent from. The <strong>default</strong> one is pre-selected when sending.</small>
        </div>
        <a href="{{ route('email-configurations.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> Add Email Account</a>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Sends as</th><th>Server</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($configs as $account)
                        <tr>
                            <td>
                                <strong>{{ $account->name }}</strong>
                                @if ($account->is_default)<span class="badge badge-success ml-1">Default</span>@endif
                            </td>
                            <td>{{ $account->from_name }} &lt;{{ $account->from_address }}&gt;</td>
                            <td class="text-muted small">{{ $account->host }}:{{ $account->port }} {{ $account->encryption ? strtoupper($account->encryption) : '' }}</td>
                            <td class="text-right text-nowrap">
                                <form method="POST" action="{{ route('email-configurations.test', $account->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success" title="Send a test email to {{ Auth::user()->email }}"><i class="far fa-paper-plane mr-1"></i>Send test</button>
                                </form>
                                @unless ($account->is_default)
                                    <form method="POST" action="{{ route('email-configurations.default', $account->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary">Make default</button>
                                    </form>
                                @endunless
                                <a href="{{ route('email-configurations.edit', $account->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="{{ route('email-configurations.destroy', $account->id) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this email account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted p-5">No email accounts yet. <a href="{{ route('email-configurations.create') }}">Add one</a> to start emailing payslips.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-body small text-muted">
            <strong>Gmail example:</strong> host <code>smtp.gmail.com</code>, port <code>587</code>, TLS, username = the Gmail address,
            password = an <em>App Password</em> (Google Account → Security → 2-Step Verification → App passwords), not the normal Gmail password.
        </div>
    </div>
</div>
@endsection
