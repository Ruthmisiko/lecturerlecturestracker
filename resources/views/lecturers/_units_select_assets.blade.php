{{-- Searchable multi-select for lecturer units. Include once per page. --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
<style>
    .select2-container--bootstrap4 .select2-selection--multiple { min-height: 38px; }
    .select2-container--bootstrap4 .select2-selection__choice { background: var(--primary-soft); border-color: var(--primary); color: var(--primary-dark); font-size: 13px; }

    /* Checkbox look for each unit in the open list */
    .select2-container--bootstrap4 .select2-results__option { position: relative; padding-left: 38px; }
    .select2-container--bootstrap4 .select2-results__option::before {
        content: ''; position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        width: 17px; height: 17px; border: 2px solid #adb5bd; border-radius: 3px; background: #fff;
        font-size: 12px; line-height: 13px; text-align: center; color: #fff; font-weight: bold;
    }
    .select2-container--bootstrap4 .select2-results__option[aria-selected=true],
    .select2-container--bootstrap4 .select2-results__option--selected {
        background: var(--primary-soft) !important; color: var(--primary-dark) !important; font-weight: 600;
    }
    .select2-container--bootstrap4 .select2-results__option[aria-selected=true]::before,
    .select2-container--bootstrap4 .select2-results__option--selected::before {
        content: '\2713'; background: var(--primary); border-color: var(--primary);
    }
    /* Hover row: light grey instead of the solid blue bar */
    .select2-container--bootstrap4 .select2-results__option--highlighted {
        background: #f1f3f5 !important; color: #212529 !important;
    }
    .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected=true],
    .select2-container--bootstrap4 .select2-results__option--highlighted.select2-results__option--selected {
        background: var(--secondary-soft) !important; color: #212529 !important;
    }
    /* No-results / searching messages shouldn't get a checkbox */
    .select2-container--bootstrap4 .select2-results__message::before { display: none; }
    .select2-container--bootstrap4 .select2-results__message { padding-left: 12px; }
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
