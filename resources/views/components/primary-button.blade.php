<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary-edu inline-flex items-center justify-center px-5 py-2.5 text-sm disabled:opacity-50']) }}>
    {{ $slot }}
</button>
