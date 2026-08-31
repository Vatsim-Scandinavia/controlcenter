{{--
    One pill inside an <x-filter.group>.

    The current query string is carried into $href, so switching filter never
    silently drops unrelated filter state such as a date range. Params already
    present in $href win over the carried ones.
--}}
@props(['href', 'active' => false])

@php
    $base = strstr($href, '?', true) ?: $href;
    parse_str((string) parse_url($href, PHP_URL_QUERY), $ownParams);
    $query = array_merge(request()->query(), $ownParams);
@endphp

<a {{ $attributes->class(['btn', 'btn-sm', $active ? 'btn-primary' : 'btn-outline-primary']) }} href="{{ $base.($query === [] ? '' : '?'.http_build_query($query)) }}">{{ $slot }}</a>
