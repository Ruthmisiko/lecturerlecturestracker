@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="font-weight-bold">{{ $semester->exists ? 'Edit Semester' : 'Add Semester' }}</h1>
    </div>
</section>

<div class="content px-3">
    @include('adminlte-templates::common.errors')

    <div class="card">
        <form method="POST" action="{{ $semester->exists ? route('semesters.update', $semester->id) : route('semesters.store') }}">
            @csrf
            @if ($semester->exists) @method('PUT') @endif

            <div class="card-body">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Semester Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="255" autofocus
                               value="{{ old('name', $semester->name) }}" placeholder="e.g. Jan – Apr 2026">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Start Date <small class="text-muted">(optional)</small></label>
                        <input type="date" name="start_date" class="form-control"
                               value="{{ old('start_date', $semester->start_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label>End Date <small class="text-muted">(optional)</small></label>
                        <input type="date" name="end_date" class="form-control"
                               value="{{ old('end_date', $semester->end_date?->format('Y-m-d')) }}">
                    </div>
                </div>

                @unless ($semester->is_current)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_current" value="1" id="is_current"
                               {{ old('is_current') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_current">Make this the current semester (new lectures will be recorded under it)</label>
                    </div>
                @else
                    <p class="text-success mb-0"><i class="fas fa-check-circle mr-1"></i> This is the current semester.</p>
                @endunless
            </div>

            <div class="card-footer">
                <button class="btn btn-success">{{ $semester->exists ? 'Save' : 'Create' }}</button>
                <a href="{{ route('semesters.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
