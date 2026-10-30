{{-- Clickable placeholders: inserts at the cursor in the last focused [data-placeholder-target] field. --}}
<div class="placeholder-picker small">
    @foreach (\App\Models\EmailTemplate::PLACEHOLDERS as $group => $tags)
        <div class="mb-2">
            <div class="text-muted text-uppercase font-weight-bold" style="font-size:11px;">{{ $group }}</div>
            @foreach ($tags as $tag => $label)
                <button type="button" class="btn btn-xs btn-outline-secondary mb-1 insert-placeholder" data-insert="{{ $tag }}" title="Inserts {{ $tag }}">{{ $label }}</button>
            @endforeach
        </div>
    @endforeach
</div>

@once
@push('scripts')
<script>
(function () {
    var last = null;

    document.addEventListener('focusin', function (e) {
        if (e.target.matches('[data-placeholder-target]')) last = e.target;
    });

    // mousedown keeps the field's cursor position (a click would blur it first).
    document.addEventListener('mousedown', function (e) {
        if (e.target.closest('.insert-placeholder')) e.preventDefault();
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.insert-placeholder');
        if (!btn) return;
        var field = last && document.body.contains(last) ? last
            : btn.closest('form, .modal-content').querySelector('textarea[data-placeholder-target]');
        if (!field) return;

        // A page can fill in real values instead of the tag (the Email Payslip window does).
        var text = window.resolvePlaceholder ? window.resolvePlaceholder(btn.dataset.insert) : btn.dataset.insert;
        var start = field.selectionStart, end = field.selectionEnd;
        field.value = field.value.slice(0, start) + text + field.value.slice(end);
        field.focus();
        field.selectionStart = field.selectionEnd = start + text.length;
        field.dispatchEvent(new Event('input', { bubbles: true }));
    });
})();
</script>
@endpush
@endonce
