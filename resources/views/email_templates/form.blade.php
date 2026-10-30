@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="font-weight-bold">{{ $template->exists ? 'Edit Email Template' : 'Add Email Template' }}</h1>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <form method="POST" action="{{ $template->exists ? route('email-templates.update', $template->id) : route('email-templates.store') }}">
                    @csrf
                    @if ($template->exists) @method('PUT') @endif

                    <div class="card-body">
                        <div class="form-group">
                            <label>Template name <span class="text-danger">*</span></label>                            <input type="text" name="name" class="form-control" required maxlength="100" value="{{ old('name', $template->name) }}" placeholder="e.g. Monthly payslip">
                        </div>
                        <div class="form-group">
                            <label>Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" required maxlength="200" value="{{ old('subject', $template->subject) }}" data-placeholder-target>
                        </div>
                        <div class="form-group">
                            <label>Message <span class="text-danger">*</span></label>
                            <textarea name="body" class="form-control" rows="14" required maxlength="10000" data-placeholder-target>{{ old('body', $template->body) }}</textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_default" value="1" id="is_default"
                                   {{ old('is_default', $template->is_default) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_default">Use as the default template</label>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button class="btn btn-success">Save</button>
                        <a href="{{ route('email-templates.index') }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header font-weight-bold">Placeholders</div>
                <div class="card-body">
                    <p class="small text-muted">Click in the subject or message, then click a placeholder to insert it there. When the template is used, each one is replaced with that lecturer's details.</p>
                    @include('email_templates._placeholders')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
