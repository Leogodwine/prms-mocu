@php
    $kai = asset(config('prms.kaiadmin_assets', 'vendor/prms-mocu/assets'));
    $full = $full ?? false;
@endphp
<script src="{{ $kai }}/js/core/jquery-3.7.1.min.js"></script>
<script src="{{ $kai }}/js/core/popper.min.js"></script>
<script src="{{ $kai }}/js/core/bootstrap.min.js"></script>
@if ($full)
<script src="{{ $kai }}/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="{{ $kai }}/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
<script src="{{ $kai }}/js/plugin/chart-circle/circles.min.js"></script>
<script src="{{ $kai }}/js/plugin/datatables/datatables.min.js"></script>
<script src="{{ $kai }}/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="{{ $kai }}/js/plugin/jsvectormap/jsvectormap.min.js"></script>
<script src="{{ $kai }}/js/plugin/jsvectormap/world.js"></script>
<script src="{{ $kai }}/js/plugin/gmaps/gmaps.js"></script>
<script src="{{ $kai }}/js/plugin/sweetalert/sweetalert.min.js"></script>
<script src="{{ $kai }}/js/kaiadmin.min.js"></script>
@endif
