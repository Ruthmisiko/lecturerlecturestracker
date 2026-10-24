@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <h1 class="font-weight-bold">{{ $institution->exists ? 'Rename Institution' : 'Add Institution' }}</h1>
        </div>
    </section>

    <div class="content px-3">
        @include('adminlte-templates::common.errors')

        <div class="card">
            <form method="POST" action="{{ $institution->exists ? route('institutions.update', $institution->id) : route('institutions.store') }}">
                @csrf
                @if ($institution->exists) @method('PUT') @endif

                <div class="card-body">
                    <div class="form-group">
                        <label>Institution Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $institution->name) }}" required maxlength="255" autofocus>
                    </div>

                    @unless ($institution->exists)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="switch" value="1" id="switch" checked>
                            <label class="form-check-label" for="switch">Switch to this institution after creating it</label>
                        </div>
                    @endunless
                </div>

                <div class="card-footer">
                    <button class="btn btn-success">{{ $institution->exists ? 'Save' : 'Create' }}</button>
                    <a href="{{ route('institutions.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
