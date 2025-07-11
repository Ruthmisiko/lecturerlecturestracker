@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="font-weight-bold">Classes</h1>
                </div>
                @can('class.create')
                <div class="col-sm-6">
                    <a class="btn btn-success float-right"
                       href="{{ route('classses.create') }}">
                        Add New
                    </a>
                </div>
                @endcan
            </div>
        </div>
    </section>

    <div class="content px-3">

        @include('flash::message')

        <div class="clearfix"></div>

        <div class="card">
            @include('classses.table')
        </div>
    </div>

@endsection
