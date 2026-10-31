<div
    class="position-relative"
    x-data="{
        open: false,
        active: -1,
        close() { this.open = false; this.active = -1; },
        items() { return Array.from(this.$refs.list ? this.$refs.list.querySelectorAll('[data-option]') : []); },
        move(dir) {
            this.open = true;
            const items = this.items();
            if (! items.length) { return; }
            this.active = (this.active + dir + items.length) % items.length;
            items[this.active].scrollIntoView({ block: 'nearest' });
        },
        choose() {
            const items = this.items();
            if (this.open && this.active >= 0 && items[this.active]) { items[this.active].click(); }
        },
    }"
    x-on:click.outside="close()"
>
    <input
        type="text"
        class="form-control form-control-sm"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        autocorrect="off"
        autocapitalize="off"
        spellcheck="false"
        role="combobox"
        aria-autocomplete="list"
        x-bind:aria-expanded="open"
        wire:model.live.debounce.300ms="value"
        x-on:focus="open = true"
        x-on:input="open = true; active = -1"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.enter.prevent="choose()"
        x-on:keydown.escape="close()"
    >

    <div
        x-ref="list"
        x-cloak
        x-show="open"
        x-transition.opacity
        class="list-group position-absolute w-100 shadow mt-1"
        style="z-index: 1050; max-height: 16rem; overflow-y: auto;"
    >
        @if(mb_strlen(trim($value)) < $minChars)
            <span class="list-group-item small text-muted">
                Type at least {{ $minChars }} character{{ $minChars === 1 ? '' : 's' }} to search…
            </span>
        @elseif(count($options) === 0)
            <span class="list-group-item small text-muted">No matches</span>
        @else
            @foreach($options as $option)
                <button
                    type="button"
                    data-option
                    class="list-group-item list-group-item-action py-2 text-truncate flex-shrink-0"
                    x-bind:class="{ active: active === {{ $loop->index }} }"
                    x-on:mouseenter="active = {{ $loop->index }}"
                    x-on:click="$wire.select(@js($option['value'])); close()"
                >
                    {{ $option['label'] }}
                </button>
            @endforeach
        @endif
    </div>
</div>
