@php
    $current = $current ?? '';
    $tabs = [
        ['route' => 'admin.academic-configuration.index', 'label' => 'Overview', 'key' => 'index'],
        ['route' => 'admin.academic-configuration.departments.index', 'label' => 'Departments', 'key' => 'departments'],
        ['route' => 'admin.academic-configuration.programmes.index', 'label' => 'Programmes', 'key' => 'programmes'],
        ['route' => 'admin.academic-configuration.levels.index', 'label' => 'Academic levels', 'key' => 'levels'],
        ['route' => 'admin.academic-configuration.preview.index', 'label' => 'Eligibility preview', 'key' => 'preview'],
    ];
@endphp
<ul class="nav nav-pills flex-wrap gap-2 mb-4">
    @foreach ($tabs as $tab)
        <li class="nav-item">
            <a href="{{ route($tab['route']) }}"
               class="nav-link rounded-pill px-3 @if ($current === $tab['key']) active @endif">
                {{ $tab['label'] }}
            </a>
        </li>
    @endforeach
    <li class="nav-item ms-auto">
        <form method="POST" action="{{ route('admin.academic-configuration.reevaluate') }}" class="d-inline"
              onsubmit="return confirm('Apply current rules to all students now?');">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fas fa-sync-alt me-1"></i> Apply to all students
            </button>
        </form>
    </li>
</ul>
