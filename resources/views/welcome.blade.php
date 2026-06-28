@extends('layouts.landing')

@section('title', 'Welcome')

@section('content')
    {{-- Hero --}}
    <section class="hero prms-landing-hero" aria-labelledby="prms-hero-heading">
        <div class="prms-landing-hero-bg" aria-hidden="true"></div>
        <div class="container px-3 px-sm-4">
            <div class="row align-items-center g-3 g-md-5">
                <div class="col-12">
                    <div class="hero-masthead text-center">
                        <h1 id="prms-hero-heading" class="prms-hero-system-title mb-0">
                            {{ __('MoCU - Project & Research Management System') }}
                        </h1>
                        <p class="lead-text mx-auto px-1 px-sm-0">
                            {{ __('A modern platform for proposals, theses, dissertations, and computer-based projects - supervision, workflow automation, and public repository publishing in one place.') }}
                        </p>

                        <div class="prms-hero-actions d-flex flex-wrap gap-2 gap-sm-3 justify-content-center mt-4">
                            <a href="#prms-landing-features" class="btn btn-primary-modern">
                                <i class="fas fa-circle-info me-2" aria-hidden="true"></i>{{ __('How it works') }}
                            </a>
                            <a href="{{ route('public.research.index') }}" class="btn btn-outline-modern">
                                <i class="fas fa-book-open me-2" aria-hidden="true"></i>{{ __('Open repository') }}
                            </a>
                            @guest
                                <a href="{{ route('login') }}" class="btn btn-primary-modern">
                                    <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i>{{ __('Sign-in') }}
                                </a>
                            @endguest
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="prms-landing-features" class="prms-landing-features py-4 py-md-5">
        <div class="container px-3 px-sm-4">
            <div class="prms-landing-repo-search reveal mx-auto" role="search">
                <form action="{{ route('public.research.index') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_filter_action" value="apply">
                    <label for="prms-landing-repo-search" class="visually-hidden">{{ __('Search theses, projects, dissertations') }}</label>
                    <div class="search-box">
                        <i class="fas fa-search text-muted" aria-hidden="true"></i>
                        <input
                            id="prms-landing-repo-search"
                            name="search"
                            type="search"
                            class="form-control flex-grow-1"
                            placeholder="{{ __('Theses, projects, dissertations…') }}">
                    </div>
                </form>
            </div>

            <div class="row g-3 g-md-4">
                <div class="col-12 col-md-4 reveal">
                    <article class="info-card h-100">
                        <div class="info-icon" aria-hidden="true">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h3 class="h5 fw-bold mb-2">{{ __('Proposal management') }}</h3>
                        <p class="text-muted mb-0">
                            {{ __('Track chapters, feedback, revisions, and stage-gated approvals with clear milestones.') }}
                        </p>
                    </article>
                </div>
                <div class="col-12 col-md-4 reveal">
                    <article class="info-card h-100">
                        <div class="info-icon" aria-hidden="true">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 class="h5 fw-bold mb-2">{{ __('Thesis & dissertation') }}</h3>
                        <p class="text-muted mb-0">
                            {{ __('Guided workflow with scheme-based grading and seamless repository integration.') }}
                        </p>
                    </article>
                </div>
                <div class="col-12 col-md-4 reveal">
                    <article class="info-card h-100">
                        <div class="info-icon" aria-hidden="true">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <h3 class="h5 fw-bold mb-2">{{ __('System projects') }}</h3>
                        <p class="text-muted mb-0">
                            {{ __('Upload reports, source code, and showcase computer-based innovation.') }}
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('active'); });
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });
})();
</script>
@endpush
