@extends('layouts.app')

@push('styles')
<style>
    .asset-preview { border: 1px dashed #ced4da; border-radius: 8px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .asset-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .asset-preview.logo { height: 140px; }
    .asset-preview.letterhead { height: 160px; }
    .asset-preview .empty { color: #adb5bd; font-size: 13px; text-align: center; }
    .color-row { display: flex; align-items: center; gap: 10px; }
    .color-row input[type=color] { width: 56px; height: 40px; padding: 2px; border-radius: 6px; cursor: pointer; }
    .theme-preview { border-radius: 10px; overflow: hidden; border: 1px solid #e5e7eb; }
    .theme-preview .bar { padding: 12px 16px; color: #fff; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .theme-preview .bar img { height: 28px; background: #fff; border-radius: 4px; padding: 2px; }
    .theme-preview .body { padding: 16px; background: #fff; }
</style>
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
        <div>
            <h1 class="font-weight-bold mb-0">Institution Settings</h1>
            <small class="text-muted">{{ $institution->name }}</small>
        </div>
        <a href="{{ route('institutions.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Institutions</a>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    @include('adminlte-templates::common.errors')

    <form method="POST" action="{{ route('institutions.settings.update', $institution->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- Logo --}}
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-header font-weight-bold"><i class="fas fa-image mr-1 text-success"></i> Logo</div>
                    <div class="card-body">
                        <div class="asset-preview logo mb-3" id="logoPreview">
                            @if ($url = $institution->assetUrl('logo'))
                                <img src="{{ $url }}" alt="Logo">
                            @else
                                <div class="empty"><i class="fas fa-image fa-2x d-block mb-1"></i>No logo yet</div>
                            @endif
                        </div>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control-file" data-preview="logoPreview">
                        <small class="text-muted d-block mt-1">PNG, JPG or WEBP · up to 2 MB · square works best</small>
                        @if ($institution->logo_path)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                <label class="form-check-label text-danger" for="remove_logo">Remove current logo</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Letterhead --}}
            <div class="col-lg-8 mb-3">
                <div class="card h-100">
                    <div class="card-header font-weight-bold"><i class="fas fa-file-image mr-1 text-success"></i> Letterhead</div>
                    <div class="card-body">
                        <div class="asset-preview letterhead mb-3" id="letterheadPreview">
                            @if ($url = $institution->assetUrl('letterhead'))
                                <img src="{{ $url }}" alt="Letterhead">
                            @else
                                <div class="empty"><i class="fas fa-file-image fa-2x d-block mb-1"></i>No letterhead yet</div>
                            @endif
                        </div>
                        <input type="file" name="letterhead" accept="image/png,image/jpeg" class="form-control-file" data-preview="letterheadPreview">
                        <small class="text-muted d-block mt-1">PNG or JPG · up to 5 MB · a wide banner image (e.g. 2480 × 500 px) prints best</small>
                        @if ($institution->letterhead_path)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_letterhead" value="1" id="remove_letterhead">
                                <label class="form-check-label text-danger" for="remove_letterhead">Remove current letterhead</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Theme colours --}}
        <div class="card mb-3">
            <div class="card-header font-weight-bold"><i class="fas fa-palette mr-1 text-success"></i> Theme Colours</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Primary colour</label>
                            <div class="color-row">
                                <input type="color" id="primaryPicker" value="{{ old('primary_color', $institution->primary_color) }}">
                                <input type="text" name="primary_color" id="primaryText" class="form-control" style="max-width:130px;"
                                       value="{{ old('primary_color', $institution->primary_color) }}" maxlength="7" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Secondary colour</label>
                            <div class="color-row">
                                <input type="color" id="secondaryPicker" value="{{ old('secondary_color', $institution->secondary_color) }}">
                                <input type="text" name="secondary_color" id="secondaryText" class="form-control" style="max-width:130px;"
                                       value="{{ old('secondary_color', $institution->secondary_color) }}" maxlength="7" required>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-link px-0" id="resetColors">Reset to default green</button>
                    </div>

                    <div class="col-md-6">
                        <label class="d-block">Preview</label>
                        <div class="theme-preview">
                            <div class="bar" id="previewBar">
                                @if ($url = $institution->assetUrl('logo'))<img src="{{ $url }}" alt="">@endif
                                {{ $institution->name }}
                            </div>
                            <div class="body">
                                <button type="button" class="btn btn-sm text-white" id="previewBtn">Primary button</button>
                                <span class="ml-2 font-weight-bold" id="previewText">Secondary text</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="btn btn-success"><i class="fas fa-save mr-1"></i> Save Settings</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var hex = /^#[0-9a-fA-F]{6}$/;

    function link(pickerId, textId) {
        var picker = document.getElementById(pickerId), text = document.getElementById(textId);
        picker.addEventListener('input', function () { text.value = picker.value; paint(); });
        text.addEventListener('input', function () { if (hex.test(text.value)) { picker.value = text.value; paint(); } });
    }

    function paint() {
        var p = document.getElementById('primaryPicker').value, s = document.getElementById('secondaryPicker').value;
        document.getElementById('previewBar').style.background = 'linear-gradient(90deg,' + p + ',' + s + ')';
        document.getElementById('previewBtn').style.background = p;
        document.getElementById('previewText').style.color = s;
    }

    link('primaryPicker', 'primaryText');
    link('secondaryPicker', 'secondaryText');

    document.getElementById('resetColors').addEventListener('click', function () {
        document.getElementById('primaryPicker').value = document.getElementById('primaryText').value = '{{ \App\Models\Institution::DEFAULT_PRIMARY_COLOR }}';
        document.getElementById('secondaryPicker').value = document.getElementById('secondaryText').value = '{{ \App\Models\Institution::DEFAULT_SECONDARY_COLOR }}';
        paint();
    });

    // Show the chosen image before saving.
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var box = document.getElementById(input.dataset.preview);
            if (!input.files[0]) return;
            var img = document.createElement('img');
            img.src = URL.createObjectURL(input.files[0]);
            box.innerHTML = '';
            box.appendChild(img);
        });
    });

    paint();
})();
</script>
@endpush
