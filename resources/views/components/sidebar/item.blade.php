@props(['href', 'icon', 'active' => false, 'title'])

<li class="nav-item {{ $active ? 'active' : '' }}">
    <a class="nav-link" href="{{ $href }}" {{ $attributes }}>
        <i class="fas fa-fw {{ $icon }}"></i>
        <span>{{ $title }}</span>
        {{ $slot }}
    </a>
</li>
