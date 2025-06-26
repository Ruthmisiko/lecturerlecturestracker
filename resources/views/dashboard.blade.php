@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <h1>Dashboard</h1>
        </div>
    </section>

    <div class="content px-3">
        <div class="card">
            <div class="card-body">
                <p>Welcome, {{ Auth::user()->name }}!</p>
                <p>This is your dashboard.</p>
            </div>
        </div>
    </div>
@endsection
