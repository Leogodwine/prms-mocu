<?php

namespace App\Support;

/**
 * Weighted rubric score calculation shared by supervisor evaluation saves.
 */
final class EvaluationScoring
{
    /**
     * @param  list<array{criterion: string, score: numeric, weight: numeric, comments?: ?string}>  $rows
     * @return array{scores: list<array{criterion: string, weight: float, score: float, weighted_score: float, comments: ?string}>, total_score: int}
     */
    public static function fromRows(array $rows): array
    {
        $totalScore = 0.0;
        $cleanScores = [];

        foreach ($rows as $row) {
            $weighted = ((float) $row['score']) * ((float) $row['weight']) / 100.0;
            $totalScore += $weighted;
            $cleanScores[] = [
                'criterion' => $row['criterion'],
                'weight' => (float) $row['weight'],
                'score' => (float) $row['score'],
                'weighted_score' => round($weighted, 2),
                'comments' => $row['comments'] ?? null,
            ];
        }

        return [
            'scores' => $cleanScores,
            'total_score' => (int) round($totalScore),
        ];
    }
}
