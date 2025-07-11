@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold">Lecture Administereds</h1>
                </div>
                <div class="col-sm-6">
                    @can('lecture.create')
                    <div class="float-right">
                        <a class="btn btn-success mr-2" href="{{ route('lecture-administereds.create') }}">
                            Add New
                        </a>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
                            Upload Excel
                        </button>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('flash::message')
        <div class="clearfix"></div>
        <div class="card">
            @include('lecture_administereds.table')
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload Lecture Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <a href="{{ route('lecture-administereds.download-template') }}" class="btn btn-info mb-3">
                            Download Template
                        </a>
                    </div>
                    <form action="{{ route('lecture-administereds.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="excel_file">Upload Excel File</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                        </div>
                        <button type="submit" class="btn btn-success">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
