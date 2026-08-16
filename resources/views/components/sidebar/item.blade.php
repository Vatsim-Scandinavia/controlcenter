@props(['href', 'icon' => '', 'active' => false, 'title', 'collapse' => false])

@if ($collapse)
    {{-- Rendered inside an <x-sidebar.section> collapse menu; collapse items carry no icon --}}
    <a {{ $attributes->class(['collapse-item', 'active' => $active]) }} href="{{ $href }}">
        {{ $title }}
        {{ $slot }}
    </a>
@else
    {{-- Rendered as a top-level sidebar entry --}}
    <li @class(['nav-item', 'active' => $active])>
        <a {{ $attributes->class(['nav-link']) }} href="{{ $href }}">
            @if ($icon)
                <i @class(['fas', 'fa-fw', $icon])></i>
            @endif
            <span>{{ $title }}</span>
            {{ $slot }}
        </a>
    </li>
@endif
