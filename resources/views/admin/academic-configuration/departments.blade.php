@extends('layouts.app')

@section('title', 'Department management')

@section('content')
    <x-prms-greeting-banner subtitle="Configure department rules: final-year logic, project/research support.">
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
            <i class="fas fa-plus me-1"></i> Add department
        </button>
    </x-prms-greeting-banner>

    @include('admin.academic-configuration.partials.nav', ['current' => 'departments'])

    <div class="card border-0 shadow-sm">
        <x-prms-table-pagination-toolbar :paginator="$departments" noun="departments" />
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Final-year rule</th>
                        <th>Project</th>
                        <th>Research</th>
                        <th>Programmes</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td><code>{{ $department->department_code }}</code></td>
                            <td>{{ $department->department_name }}</td>
                            <td class="small">{{ $department->finalYearRuleTypeEnum()->label() }}
                                @if ($department->fixed_final_year)
                                    <span class="text-muted">(Y{{ $department->fixed_final_year }})</span>
                                @endif
                            </td>
                            <td>@if($department->supports_project)<i class="fas fa-check text-success"></i>@else<i class="fas fa-times text-muted"></i>@endif</td>
                            <td>@if($department->supports_research)<i class="fas fa-check text-success"></i>@else<i class="fas fa-times text-muted"></i>@endif</td>
                            <td>{{ $department->programmes_count }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editDeptModal-{{ $department->id }}">Edit</button>
                                @if ($department->programmes_count === 0)
                                    <form method="POST" action="{{ route('admin.academic-configuration.departments.destroy', $department) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this department?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No departments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-top py-3">
            <x-prms-table-pagination-footer :paginator="$departments" />
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="addDepartmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <form method="POST" action="{{ route('admin.academic-configuration.departments.store') }}">
                    @csrf
                    <div class="modal-header bg-surface-soft">
                        <h2 class="modal-title h5 fw-bold">Add department</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @include('admin.academic-configuration.partials.department-fields', ['prefix' => 'add', 'department' => null])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @foreach ($departments as $department)
        <div class="modal fade" id="editDeptModal-{{ $department->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0">
                    <form method="POST" action="{{ route('admin.academic-configuration.departments.update', $department) }}">
                        @csrf @method('PUT')
                        <div class="modal-header bg-surface-soft">
                            <h2 class="modal-title h5 fw-bold">Edit {{ $department->department_code }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            @include('admin.academic-configuration.partials.department-fields', ['prefix' => 'd'.$department->id, 'department' => $department])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endpush
