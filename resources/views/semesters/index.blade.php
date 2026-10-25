@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Semesters</h1>
            <small class="text-muted">New lecture records go into the <strong>current</strong> semester unless another is chosen.</small>
        </div>
        <a href="{{ route('semesters.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i> Add Semester</a>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Name</th>
                        <th>Dates</th>
                        <th class="text-center">Lecture records</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($semesters as $semester)
                        <tr>
                            <td>
                                <strong>{{ $semester->name }}</strong>
                                @if ($semester->is_current)
                                    <span class="badge badge-success ml-1">Current</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $semester->periodLabel() ?: '—' }}</td>
                            <td class="text-center">{{ $semester->lecture_administereds_count }}</td>
                            <td class="text-right text-nowrap">
                                @unless ($semester->is_current)
                                    <form method="POST" action="{{ route('semesters.current', $semester->id) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Make current</button>
                                    </form>
                                @endunless
                                <a href="{{ route('semesters.edit', $semester->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="{{ route('semesters.destroy', $semester->id) }}" class="d-inline"
                                      onsubmit="return confirm('Delete {{ addslashes($semester->name) }}? Only semesters without lecture records can be deleted.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted p-5">
                                No semesters yet. <a href="{{ route('semesters.create') }}">Add the first one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
