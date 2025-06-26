<div class="form-group col-sm-6">
    {!! Form::label('lecturer_id', 'Lecturer:') !!}
    {!! Form::select('lecturer_id', \App\Models\Lecturer::pluck('name', 'id'), null, ['class' => 'form-control', 'placeholder' => 'Select Lecturer']) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('classs_id', 'Class:') !!}
    {!! Form::select('classs_id', \App\Models\Classs::pluck('name', 'id'), null, ['class' => 'form-control', 'placeholder' => 'Select Class']) !!}
</div>

{{-- <!-- Multiple Lecture Dates -->
<div class="form-group col-sm-12">
    {!! Form::label('lecture_dates', 'Lecture Dates (select multiple):') !!}
    <input type="date" name="lecture_dates[]" class="form-control mb-2" />
    <div id="more-dates"></div>
    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addDateField()">Add Another Date</button>
</div> --}}

<!-- Lecture Date Field (Single) -->
<div class="form-group col-sm-6">
    {!! Form::label('lecture_date', 'Lecture Date:') !!}
    {!! Form::date('lecture_date', isset($lectureAdministered) ? $lectureAdministered->lecture_date : null, ['class' => 'form-control']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('lecture_time', 'Lecture Time:') !!}
    {!! Form::time('lecture_time', null, ['class' => 'form-control']) !!}
</div>

<script>
function addDateField() {
    const dateField = `<input type="date" name="lecture_dates[]" class="form-control mb-2" />`;
    document.getElementById('more-dates').insertAdjacentHTML('beforeend', dateField);
}
</script>

