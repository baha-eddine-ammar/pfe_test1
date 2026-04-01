@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'app-status-success']) }}>
        {{ $status }}
    </div>
@endif
