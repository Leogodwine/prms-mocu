@include('partials.submission-project-grid-group', [
    'group' => $group,
    'showReview' => false,
    'onlyOfficeConfigured' => $onlyOfficeConfigured ?? false,
])
