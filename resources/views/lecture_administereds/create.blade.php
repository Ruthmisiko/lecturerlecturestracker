@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>
                    Create Lecture Administered
                    </h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card">

            {!! Form::open(['route' => 'lecture-administereds.store']) !!}

            <div class="card-body">

                <div class="row">
                  <div class="form-group col-sm-6">
                        {!! Form::label('lecturer_id', 'Lecturer:') !!}
                        {!! Form::select('lecturer_id', $lecturers, null, ['class' => 'form-control', 'placeholder' => 'Select Lecturer']) !!}
                    </div>

                    <div class="form-group col-sm-6">
                        {!! Form::label('classs_id', 'Class:') !!}
                        {!! Form::select('classs_id', $classes, null, ['class' => 'form-control', 'placeholder' => 'Select Class']) !!}
                    </div>
                    <!-- Multiple Lecture Dates -->
                    <div class="form-group col-sm-12">
                        {!! Form::label('lecture_dates', 'Lecture Dates (select multiple):') !!}
                        <input type="date" name="lecture_dates[]" class="form-control mb-2" />
                        <div id="more-dates"></div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addDateField()">Add Another Date</button>
                    </div>

                    <div class="form-group col-sm-6">
                        {!! Form::label('start_time', 'Start Time:') !!}
                        {!! Form::time('start_time', null, ['class' => 'form-control']) !!}
                    </div>

                    <div class="form-group col-sm-6">
                        {!! Form::label('end_time', 'End Time:') !!}
                        {!! Form::time('end_time', null, ['class' => 'form-control']) !!}
                    </div>

<script>
function addDateField() {
    const dateField = `<input type="date" name="lecture_dates[]" class="form-control mb-2" />`;
    document.getElementById('more-dates').insertAdjacentHTML('beforeend', dateField);
}
</script>
    <div class="card-footer">
        {!! Form::submit('Save', ['class' => 'btn btn-success']) !!}
        <a href="{{ route('lecture-administereds.index') }}" class="btn btn-default"> Cancel </a>
    </div>

    {!! Form::close() !!}

</div>
    </div>
@endsection
