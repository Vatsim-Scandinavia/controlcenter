@props(['icon', 'title', 'active' => false, 'id'])

<li class="nav-item {{ $active ? 'active' : '' }}">
    <a class="nav-link {{ $active ? '' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="{{ $active ? 'true' : 'false' }}" aria-controls="{{ $id }}">
        <i class="fas fa-fw {{ $icon }}"></i>
        <span>{{ $title }}</span>
    </a>
    <div id="{{ $id }}" class="collapse {{ $active ? 'show' : '' }}" data-bs-parent="#sidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            {{ $slot }}
        </div>
    </div>
</li>
