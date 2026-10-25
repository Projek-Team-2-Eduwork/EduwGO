<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-danger-edu inline-flex items-center justify-center']) }}>
    {{ $slot }}
</button>
