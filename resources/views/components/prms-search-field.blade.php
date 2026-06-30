@props([
    'name' => 'q',
    'value' => '',
    'placeholder' => 'Search…',
    'id' => null,
    'size' => 'sm',
    'label' => null,
])

@php
    $fieldId = $id ?? 'prms-search-'.$name;
    $sizeClass = $size === 'lg' ? 'form-control-lg' : ($size === '' || $size === 'md' ? '' : 'form-control-'.$size);
@endphp

<div {{ $attributes->merge(['class' => 'prms-search-field']) }}>
    @if ($label)
        <label for="{{ $fieldId }}" class="form-label small text-muted mb-1">{{ $label }}</label>
    @endif
    <div class="prms-search-field__wrap">
        <i class="fas fa-search prms-search-field__icon" aria-hidden="true"></i>
        <input type="search"
               name="{{ $name }}"
               id="{{ $fieldId }}"
               value="{{ $value }}"
               placeholder="{{ $placeholder }}"
               class="form-control {{ $sizeClass }} prms-search-field__input"
               autocomplete="off">
    </div>
</div>
