@php
    $kai = asset(config('prms.kaiadmin_assets', 'vendor/prms-mocu/assets'));
    $full = $full ?? false;
@endphp
<script src="{{ $kai }}/js/core/jquery-3.7.1.min.js"></script>
<script src="{{ $kai }}/js/core/popper.min.js"></script>
<script src="{{ $kai }}/js/core/bootstrap.min.js"></script>
@if ($full)
<script src="{{ $kai }}/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="{{ $kai }}/js/kaiadmin.min.js"></script>
@endif
