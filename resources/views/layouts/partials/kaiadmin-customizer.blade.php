@php
    $chromeRow1 = ['dark', 'blue', 'purple', 'light-blue', 'green', 'orange', 'red', 'white'];
    $chromeRow2 = ['dark2', 'blue2', 'purple2', 'light-blue2', 'green2', 'orange2', 'red2'];
    $sidebarColors = ['white', 'dark', 'dark2'];
@endphp

<div class="custom-template" id="prms-kaiadmin-customizer" aria-label="Layout settings">
    <div class="title">Settings</div>
    <div class="custom-content">
        <div class="switcher">
            <div class="switch-block">
                <h4>Logo header</h4>
                <div class="btnSwitch">
                    @foreach ($chromeRow1 as $color)
                        <button type="button" class="changeLogoHeaderColor" data-color="{{ $color }}" title="{{ ucfirst(str_replace('-', ' ', $color)) }}" aria-label="Logo {{ $color }}"></button>
                    @endforeach
                    <br>
                    @foreach ($chromeRow2 as $color)
                        <button type="button" class="changeLogoHeaderColor" data-color="{{ $color }}" title="{{ ucfirst(str_replace('-', ' ', $color)) }}" aria-label="Logo {{ $color }}"></button>
                    @endforeach
                </div>
            </div>

            <div class="switch-block">
                <h4>Navbar header</h4>
                <div class="btnSwitch">
                    @foreach ($chromeRow1 as $color)
                        <button type="button" class="changeTopBarColor" data-color="{{ $color }}" title="{{ ucfirst(str_replace('-', ' ', $color)) }}" aria-label="Navbar {{ $color }}"></button>
                    @endforeach
                    <br>
                    @foreach ($chromeRow2 as $color)
                        <button type="button" class="changeTopBarColor" data-color="{{ $color }}" title="{{ ucfirst(str_replace('-', ' ', $color)) }}" aria-label="Navbar {{ $color }}"></button>
                    @endforeach
                </div>
            </div>

            <div class="switch-block">
                <h4>Sidebar</h4>
                <div class="btnSwitch">
                    @foreach ($sidebarColors as $color)
                        <button type="button" class="changeSideBarColor" data-color="{{ $color }}" title="{{ ucfirst($color) }} sidebar" aria-label="Sidebar {{ $color }}"></button>
                    @endforeach
                </div>
            </div>

            <div class="switch-block mb-0">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="prms-chrome-reset">
                    Reset to MoCU defaults
                </button>
            </div>
        </div>
    </div>
    <button type="button" class="custom-toggle" aria-label="Open layout settings" aria-expanded="false">
        <i class="icon-settings" aria-hidden="true"></i>
    </button>
</div>
