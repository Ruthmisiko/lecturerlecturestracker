@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold">Lecturers</h1>
                </div>
                <div class="col-sm-6 d-flex justify-content-end">
                    <a class="btn btn-outline-success mr-2" href="{{ route('lecturers.list.excel', request()->only('name', 'department_id')) }}">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </a>
                    <a class="btn btn-outline-danger mr-2" href="{{ route('lecturers.list.pdf', request()->only('name', 'department_id')) }}">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </a>
                    @include('bulk._modal', ['id' => 'bulkLecturers', 'title' => 'Bulk Upload Lecturers', 'templateRoute' => route('bulk.lecturers.template'), 'importRoute' => route('bulk.lecturers.import')])
                    <a class="btn btn-success" href="{{ route('lecturers.create') }}">Add New</a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('flash::message')

        <div class="clearfix"></div>

        <div class="card">
            @include('lecturers.table')
        </div>
    </div>

@endsection
