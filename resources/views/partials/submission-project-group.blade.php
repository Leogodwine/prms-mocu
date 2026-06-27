@include('partials.submission-project-grid-group', [
    'group' => $group,
    'showReview' => $showReview ?? false,
    'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
])
