@props([
    'prefix',
    'rubric',
    'existing' => null,
    'title' => null,
    'subtitle' => null,
    'nested' => true,
])

@php
    $criteria = collect($rubric->criteria ?? []);
    $existingScores = collect($existing?->scores ?? [])->keyBy('criterion');
    $tableId = 'criteria-'.preg_replace('/[^a-z0-9_-]/i', '-', $prefix);
    $totalId = $tableId.'-total';
    $scoreNameBase = $nested ? $prefix.'[scores]' : $prefix;
    $commentsName = $nested ? $prefix.'[general_comments]' : 'general_comments';
@endphp

<div class="prms-grading-block mb-4" data-grading-block data-prefix="{{ $prefix }}">
    @if ($title)
        <div class="mb-3">
            <h3 class="h6 fw-bold text-strong mb-1">{{ $title }}</h3>
            @if ($subtitle)
                <p class="small text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 30%;">Criterion</th>
                    <th style="width: 12%;">Weighting (%)</th>
                    <th style="width: 14%;">Score (0–100)</th>
                    <th style="width: 14%;">Weighted</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody id="{{ $tableId }}" data-criteria-body>
                @forelse ($criteria as $idx => $criterion)
                    @php
                        $name = (string) ($criterion['name'] ?? '');
                        $prior = $existingScores->get($name, []);
                    @endphp
                    <tr data-criterion-row>
                        <td>
                            <div class="fw-bold">{{ $name }}</div>
                            @if (! empty($criterion['description']))
                                <small class="text-muted">{{ $criterion['description'] }}</small>
                            @endif
                            <input type="hidden" name="{{ $scoreNameBase }}[{{ $idx }}][criterion]" value="{{ $name }}">
                        </td>
                        <td>
                            <input type="number" min="0" max="100" step="0.1"
                                   class="form-control form-control-sm"
                                   name="{{ $scoreNameBase }}[{{ $idx }}][weight]"
                                   data-role="weight"
                                   value="{{ old(str_replace(['[', ']'], ['.', ''], $scoreNameBase).'.'.$idx.'.weight', $prior['weight'] ?? ($criterion['weight'] ?? 0)) }}">
                        </td>
                        <td>
                            <input type="number" min="0" max="100" step="0.1"
                                   class="form-control form-control-sm"
                                   name="{{ $scoreNameBase }}[{{ $idx }}][score]"
                                   data-role="score"
                                   value="{{ old(str_replace(['[', ']'], ['.', ''], $scoreNameBase).'.'.$idx.'.score', $prior['score'] ?? '') }}"
                                   placeholder="0–100" required>
                        </td>
                        <td><span data-role="weighted">0.00</span></td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                   name="{{ $scoreNameBase }}[{{ $idx }}][comments]" maxlength="1000"
                                   value="{{ old(str_replace(['[', ']'], ['.', ''], $scoreNameBase).'.'.$idx.'.comments', $prior['comments'] ?? '') }}"
                                   placeholder="Optional notes...">
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted text-center">This grading scheme has no criteria configured.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="3" class="text-end">Total weighted score</th>
                    <th><span id="{{ $totalId }}" class="fw-bold text-primary" data-total-score>0.00</span> / 100</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-3">
        <label class="form-label fw-bold" for="{{ $tableId }}-comments">General comments</label>
        <textarea id="{{ $tableId }}-comments"
                  name="{{ $commentsName }}"
                  class="form-control"
                  rows="3"
                  placeholder="Overall feedback...">{{ old(str_replace(['[', ']'], ['.', ''], $commentsName), $existing?->general_comments) }}</textarea>
    </div>
</div>
