@props(['headers' => []])

{{-- Stable module-facing table. Pass `:headers` for a simple header row, or a
     `head` slot for full control; body rows go in the default slot. --}}
<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'table align-middle']) }}>
        @isset($head)
            <thead>{{ $head }}</thead>
        @elseif(count($headers))
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endisset

        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
