@php
    $githubAuthor = $githubAuthor ?? 'author';
    $githubRepo = $githubRepo ?? 'project-repository';
    $githubAvatar = $githubAvatar ?? 'U';
    $githubCommitMsg = $githubCommitMsg ?? 'Latest project update';
    $githubCommitSha = $githubCommitSha ?? 'main';
    $readmeTitle = $readmeTitle ?? 'Project title';
    $readmeBody = $readmeBody ?? null;
    $archiveTree = $archiveTree ?? [];
    $analysisPending = $analysisPending ?? false;
@endphp

<div class="prms-github-preview border rounded-3 overflow-hidden bg-white" id="prms-github-preview">
    <div class="prms-github-repo-bar px-3 py-2 border-bottom d-flex flex-wrap align-items-center gap-2">
        <span class="prms-github-avatar rounded-circle d-inline-flex align-items-center justify-content-center fw-bold"
              id="prms-github-avatar" aria-hidden="true">{{ $githubAvatar }}</span>
        <span class="small">
            <span class="text-primary fw-semibold" id="prms-github-author">{{ $githubAuthor }}</span>
            <span class="text-muted">/</span>
            <span class="fw-semibold text-strong" id="prms-github-repo">{{ $githubRepo }}</span>
        </span>
        <span class="badge bg-light text-muted border ms-auto d-none d-sm-inline">Public</span>
    </div>

    <div class="prms-github-commit px-3 py-2 border-bottom small d-flex flex-wrap align-items-center gap-2 text-muted">
        <i class="fas fa-code-branch" aria-hidden="true"></i>
        <span class="text-strong" id="prms-github-commit-msg">{{ $githubCommitMsg }}</span>
        <span class="text-muted">·</span>
        <code class="small" id="prms-github-commit-sha">{{ $githubCommitSha }}</code>
        <span class="text-muted">·</span>
        <span id="prms-github-commit-time">Recently</span>
    </div>

    <div class="row g-0">
        <div class="col-md-5 col-lg-4 border-end">
            <div class="px-3 py-2 border-bottom small fw-semibold text-muted">Repository files</div>
            <ul class="list-unstyled mb-0 prms-github-tree small" id="prms-github-tree">
                @forelse ($archiveTree as $item)
                    <li>
                        @if (($item['type'] ?? 'file') === 'dir')
                            <i class="fas fa-folder text-muted me-2" aria-hidden="true"></i>{{ $item['name'] }}
                        @else
                            <i class="far fa-file-alt text-muted me-2" aria-hidden="true"></i>{{ $item['name'] }}
                        @endif
                    </li>
                @empty
                    <li class="px-3 py-2 text-muted fst-italic" id="prms-github-tree-placeholder">
                        @if ($analysisPending)
                            Reading uploaded system archive…
                        @else
                            Upload a system archive to list repository files.
                        @endif
                    </li>
                @endforelse
            </ul>
        </div>
        <div class="col-md-7 col-lg-8">
            <div class="px-3 py-2 border-bottom small fw-semibold text-muted d-flex align-items-center gap-2">
                <i class="far fa-file-alt" aria-hidden="true"></i>
                README.md
            </div>
            <div class="p-3 prms-github-readme small" id="prms-github-readme">
                <h1 class="h5 fw-bold border-bottom pb-2 mb-3" id="prms-github-readme-title">{{ $readmeTitle }}</h1>
                <div id="prms-github-readme-body" class="text-muted" style="line-height: 1.6; white-space: pre-line;">
                    @if ($readmeBody)
                        {{ $readmeBody }}
                    @elseif ($analysisPending)
                        Generating README preview from uploaded files…
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
    <style>
        .prms-github-preview {
            font-size: 0.85rem;
        }

        .prms-github-avatar {
            width: 1.75rem;
            height: 1.75rem;
            background: var(--prms-primary-soft, #dbeafe);
            color: var(--prms-primary, #1572E8);
            font-size: 0.7rem;
        }

        .prms-github-tree li {
            padding: 0.35rem 1rem;
            border-bottom: 1px solid var(--prms-border-soft, #f1f5f9);
        }

        .prms-github-tree li:last-child {
            border-bottom: 0;
        }

        .prms-github-readme h1 {
            font-size: 1.15rem;
        }

        .prms-doc-significance {
            line-height: 1.55;
        }
    </style>
    @endpush
@endonce
