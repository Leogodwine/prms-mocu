@php
    $preserve = [
        'search' => $filters['search'] ?? '',
        'type' => $filters['type'] ?? '',
        'department_id' => $filters['department_id'] ?? '',
        'since_year' => $filters['since_year'] ?? '',
        'year_from' => $filters['year_from'] ?? '',
        'year_to' => $filters['year_to'] ?? '',
        'author' => $filters['author'] ?? '',
        'sort' => $filters['sort'] ?? 'recent',
    ];
    $merged = array_merge($preserve, $override ?? []);
    $skip = $except ?? [];
@endphp
@foreach ($merged as $name => $value)
    @if (! in_array($name, $skip, true))
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endif
@endforeach
