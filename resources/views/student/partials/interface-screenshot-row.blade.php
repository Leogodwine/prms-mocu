@php
    $rowId = 'prms-interface-shot-'.$index;
    $selectId = 'interface-select-'.$index;
    $customId = 'interface-custom-'.$index;
    $fileId = 'interface-file-'.$index;
@endphp

<div class="card border shadow-none prms-interface-shot-row" id="{{ $rowId }}" data-index="{{ $index }}">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <h4 class="h6 fw-bold text-strong mb-0">Interface screenshot</h4>
            @if ($canRemove)
                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none px-0 prms-remove-interface-shot">
                    <i class="fas fa-times me-1" aria-hidden="true"></i>Remove
                </button>
            @endif
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-md-5">
                <label class="form-label small fw-semibold" for="{{ $selectId }}">Interface</label>
                <select id="{{ $selectId }}"
                        name="interface_screenshots[{{ $index }}][interface]"
                        class="form-select form-select-sm prms-interface-select"
                        required>
                    @foreach ($interfaceOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($selectedInterface ?? 'home_page') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <div class="mt-2 prms-interface-custom-wrap {{ ($selectedInterface ?? '') === 'other' ? '' : 'd-none' }}">
                    <label class="form-label small fw-semibold" for="{{ $customId }}">Custom interface name</label>
                    <input type="text"
                           id="{{ $customId }}"
                           name="interface_screenshots[{{ $index }}][custom_label]"
                           class="form-control form-control-sm"
                           maxlength="120"
                           value="{{ $customLabel ?? '' }}"
                           placeholder="e.g. Seller orders page">
                </div>
            </div>

            <div class="col-md-7">
                <label class="form-label small fw-semibold" for="{{ $fileId }}">Screenshot image</label>
                <div class="prms-dropzone prms-file-dropzone w-100">
                    <input type="file"
                           id="{{ $fileId }}"
                           name="interface_screenshots[{{ $index }}][image]"
                           class="visually-hidden prms-interface-file"
                           accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
                           required
                           onchange="prmsHandleInterfaceScreenshot(this)">
                    <label for="{{ $fileId }}" class="mb-0 w-100 cursor-pointer">
                        <img class="rounded-3 border d-none mb-2 prms-interface-preview"
                             alt=""
                             style="max-width: 100%; max-height: 140px; object-fit: contain; background: #fff;">
                        <i class="far fa-image text-primary mb-2 prms-interface-icon" aria-hidden="true" style="font-size: 1.6rem;"></i>
                        <div class="fw-semibold text-strong prms-interface-filename" data-empty-label="Drop screenshot or click to browse">
                            Drop screenshot or click to browse
                        </div>
                        <div class="small text-muted mt-1">PNG, JPG, WEBP · Max 5 MB</div>
                    </label>
                </div>
                <span class="small text-muted d-block mt-1 prms-interface-status">No file chosen</span>
            </div>
        </div>
    </div>
</div>
