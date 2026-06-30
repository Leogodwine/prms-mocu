<div class="col-12">
    <label class="form-label" for="prms-home-page-screenshot">
        <i class="far fa-image me-1 text-primary" aria-hidden="true"></i>
        Home page interface screenshot
        <span class="text-danger">*</span>
    </label>
    <input type="hidden" name="interface_screenshots[0][interface]" value="home_page">
    <div class="prms-dropzone prms-file-dropzone w-100 @error('interface_screenshots') is-invalid border-danger @enderror @error('interface_screenshots.0.image') is-invalid border-danger @enderror">
        <input type="file"
               id="prms-home-page-screenshot"
               name="interface_screenshots[0][image]"
               class="visually-hidden prms-interface-file"
               accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp"
               required
               onchange="prmsHandleHomePageScreenshot(this)">
        <label for="prms-home-page-screenshot" class="mb-0 w-100 cursor-pointer">
            <img class="rounded-3 border d-none mb-2 prms-home-page-preview mx-auto"
                 alt="Home page preview"
                 style="max-width: 100%; max-height: 180px; object-fit: contain; background: #fff;">
            <i class="far fa-image text-primary mb-2 prms-home-page-icon" aria-hidden="true" style="font-size: 1.8rem;"></i>
            <div class="fw-semibold text-strong prms-home-page-filename" data-empty-label="Drop screenshot here or click to browse">
                Drop screenshot here or click to browse
            </div>
            <div class="small text-muted mt-1">PNG, JPG, WEBP · Max 5 MB</div>
        </label>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
        <label for="prms-home-page-screenshot" class="btn btn-sm btn-outline-secondary mb-0">Choose file</label>
        <span class="small text-muted prms-home-page-status">No file chosen</span>
    </div>
    @error('interface_screenshots')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('interface_screenshots.0.image')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
