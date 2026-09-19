<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-clay border border-transparent rounded-lg font-medium text-sm text-white shadow-red hover:bg-[#e03126] focus:outline-none focus:ring-2 focus:ring-clay/40 focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>
