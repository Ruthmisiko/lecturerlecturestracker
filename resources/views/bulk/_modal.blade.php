{{-- Bulk upload button + window. Params: $id, $title, $templateRoute, $importRoute. --}}
<button type="button" class="btn btn-outline-success mr-2" data-toggle="modal" data-target="#{{ $id }}">
    <i class="fas fa-file-excel mr-1"></i> Bulk Upload
</button>

<div class="modal fade text-left" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" method="POST" action="{{ $importRoute }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">1. Download the template and fill it in (see its Instructions sheet).</p>
                <a href="{{ $templateRoute }}" class="btn btn-info mb-3"><i class="fas fa-download mr-1"></i> Download Template</a>

                <p class="small text-muted mb-2">2. Upload the filled-in file.</p>
                <input type="file" name="file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                @error('file')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-upload mr-1"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

@if ($errors->has('file'))
    @push('scripts')
        <script>$(function () { $('#{{ $id }}').modal('show'); });</script>
    @endpush
@endif
