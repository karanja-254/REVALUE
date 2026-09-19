@if (session('status'))
    <div class="mb-6 rounded-2xl border border-forest-mid/20 bg-forest-soft px-4 py-3 text-sm text-forest">
        {{ session('status') }}
    </div>
@endif
