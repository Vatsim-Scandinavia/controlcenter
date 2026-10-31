@props(['icon' => '', 'title', 'active' => false, 'id'])

<li @class(['nav-item', 'active' => $active])>
    <a {{ $attributes->class(['nav-link', 'collapsed' => ! $active]) }} href="#" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="{{ $active ? 'true' : 'false' }}" aria-controls="{{ $id }}">
        @if ($icon)
            <i class="fas fa-fw {{ $icon }}"></i>
        @endif
        <span>{{ $title }}</span>
    </a>
    <div id="{{ $id }}" class="collapse {{ $active ? 'show' : '' }}" data-bs-parent="#sidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            {{ $slot }}
        </div>
    </div>
</li>
