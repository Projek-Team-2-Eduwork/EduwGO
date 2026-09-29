<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary-edu inline-flex items-center justify-center px-4 py-2 text-sm disabled:opacity-50']) }}>
    {{ $slot }}
</button>
