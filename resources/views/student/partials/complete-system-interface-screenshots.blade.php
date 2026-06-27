@php
    $interfaceOptions = \App\Support\StudentStageProgress::systemInterfaceOptions();
    $oldShots = old('interface_screenshots', [['interface' => 'home_page', 'custom_label' => '']]);
@endphp

<div class="col-12">
    <label class="form-label d-flex align-items-center justify-content-between gap-2">
        <span>
            <i class="far fa-images me-1 text-primary" aria-hidden="true"></i>
            Interface screenshots
            <span class="text-danger">*</span>
        </span>
        <span class="small text-muted">Select the interface, then upload its screenshot. Add one or more.</span>
    </label>

    <div id="prms-interface-screenshots" class="d-flex flex-column gap-3">
        @foreach ($oldShots as $index => $shot)
            @include('student.partials.interface-screenshot-row', [
                'index' => $index,
                'interfaceOptions' => $interfaceOptions,
                'selectedInterface' => $shot['interface'] ?? 'home_page',
                'customLabel' => $shot['custom_label'] ?? '',
                'canRemove' => $index > 0,
            ])
        @endforeach
    </div>

    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="prms-add-interface-shot">
        <i class="fas fa-plus me-1" aria-hidden="true"></i>Add another interface
    </button>

    @error('interface_screenshots')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('interface_screenshots.*.image')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<template id="prms-interface-shot-template">
    @include('student.partials.interface-screenshot-row', [
        'index' => '__INDEX__',
        'interfaceOptions' => $interfaceOptions,
        'selectedInterface' => 'home_page',
        'customLabel' => '',
        'canRemove' => true,
    ])
</template>
