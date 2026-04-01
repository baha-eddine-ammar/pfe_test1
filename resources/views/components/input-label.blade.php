@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200']) }}>
    {{ $value ?? $slot }}
</label>
