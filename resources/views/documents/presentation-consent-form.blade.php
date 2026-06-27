@extends('layouts.print')

@section('title', 'Supervisor confirmation form')

@push('styles')
<style>
    .prms-consent-toolbar {
        max-width: 720px;
        margin: 0 auto;
        padding: 1rem 1.5rem 0;
    }
    .prms-consent-doc {
        max-width: 720px;
        margin: 0 auto;
        padding: 2rem 2.5rem 3rem;
    }
</style>
@endpush

@section('content')
    @unless ($downloadMode ?? false)
        <div class="prms-consent-toolbar no-print d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
               class="btn btn-light border btn-sm">
                <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Back
            </a>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('presentation-consent.download', request()->query()) }}"
                   class="btn btn-primary btn-sm">
                    <i class="fas fa-download me-1" aria-hidden="true"></i> Download PDF
                </a>
            </div>
        </div>
    @endunless

    @include('documents.partials.presentation-consent-body')
@endsection
