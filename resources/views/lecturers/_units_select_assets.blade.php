{{-- Searchable multi-select for lecturer units. Include once per page. --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
<style>
    .select2-container--bootstrap4 .select2-selection--multiple { min-height: 38px; }
    .select2-container--bootstrap4 .select2-selection__choice { background: #e9f6ec; border-color: #28a745; color: #1e7e34; font-size: 13px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function initUnitsSelect(el) {
        $(el).select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Search and select units…',
            closeOnSelect: false,
            allowClear: true,
        });
    }
    $(function () {
        $('.units-select').each(function () { initUnitsSelect(this); });
    });
</script>
@endpush
