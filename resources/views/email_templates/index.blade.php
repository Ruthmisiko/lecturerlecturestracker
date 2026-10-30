@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Email Templates</h1>
            <small class="text-muted">Saved messages for emailing payslips. The <strong>default</strong> one is filled in when the Email Payslip window opens.</small>
        </div>
        <a href="{{ route('email-templates.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> Add Template</a>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr><th>Name</th><th>Subject</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        <tr>
                            <td>
                                <strong>{{ $template->name }}</strong>
                                @if ($template->is_default)<span class="badge badge-success ml-1">Default</span>@endif
                            </td>
                            <td class="text-muted">{{ $template->subject }}</td>
                            <td class="text-right text-nowrap">
                                @unless ($template->is_default)
                                    <form method="POST" action="{{ route('email-templates.default', $template->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary">Make default</button>
                                    </form>
                                @endunless
                                <a href="{{ route('email-templates.edit', $template->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="{{ route('email-templates.destroy', $template->id) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this template?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted p-5">No templates yet. <a href="{{ route('email-templates.create') }}">Add one</a> to reuse it when emailing payslips.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
