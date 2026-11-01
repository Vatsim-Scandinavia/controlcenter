@props(['title' => null, 'headerClass' => 'bg-primary'])

{{-- Stable module-facing card matching Control Center's Bootstrap styling. --}}
<div {{ $attributes->merge(['class' => 'card shadow mb-4']) }}>
    @if($title !== null || isset($header))
        <div class="card-header {{ $headerClass }} py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-white">{{ $header ?? $title }}</h6>
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="card-body">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
