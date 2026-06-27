<?php

namespace App\Services\Ollama;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OllamaClient
{
    public function isEnabled(): bool
    {
        return (bool) config('ollama.enabled', false);
    }

    public function isReachable(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get($this->baseUrl().'/api/tags');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     */
    public function chat(array $messages, ?string $model = null, bool $jsonFormat = true): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('Ollama integration is disabled.');
        }

        $payload = [
            'model' => $model ?? config('ollama.chat_model', 'mistral'),
            'messages' => $messages,
            'stream' => false,
        ];

        if ($jsonFormat) {
            $payload['format'] = 'json';
        }

        try {
            $response = Http::timeout((int) config('ollama.timeout', 120))
                ->post($this->baseUrl().'/api/chat', $payload);

            $response->throw();
        } catch (ConnectionException $e) {
            Log::warning('Ollama connection failed', ['message' => $e->getMessage()]);

            throw new RuntimeException('Could not connect to Ollama. Ensure it is running (ollama serve).', 0, $e);
        } catch (RequestException $e) {
            Log::warning('Ollama request failed', [
                'status' => $e->response?->status(),
                'body' => $e->response?->body(),
            ]);

            throw new RuntimeException('Ollama returned an error. Is the model pulled? Try: ollama pull mistral', 0, $e);
        }

        return $response->json();
    }

    /**
     * @return array{similarity_score: float, risk_level: string, summary: string, overlap_areas: array<int, string>}|null
     */
    public function compareResearchTexts(string $textA, string $textB): ?array
    {
        $system = <<<'PROMPT'
You are an academic integrity assistant for a university project and research management system.
Compare two student submissions (title, keywords, problem statement/abstract) and detect conceptual overlap.
Respond with JSON only using this exact schema:
{
  "similarity_score": <number 0-100>,
  "risk_level": "low" | "medium" | "high",
  "summary": "<one concise sentence explaining overlap or why they differ>",
  "overlap_areas": ["<short label>", "..."]
}
Scoring guide: 0-25 unrelated topics; 26-50 shared domain only; 51-75 similar questions/methods; 76-100 likely duplicate or near-duplicate focus.
PROMPT;

        $user = "PROJECT A:\n{$textA}\n\n---\n\nPROJECT B:\n{$textB}";

        $result = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ]);

        $content = (string) data_get($result, 'message.content', '');

        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded) || ! isset($decoded['similarity_score'])) {
            $decoded = json_decode($this->extractJson($content), true);
        }

        if (! is_array($decoded) || ! is_numeric($decoded['similarity_score'] ?? null)) {
            Log::warning('Ollama similarity JSON parse failed', ['content' => $content]);

            return null;
        }

        $score = max(0, min(100, (float) $decoded['similarity_score']));
        $risk = in_array($decoded['risk_level'] ?? '', ['low', 'medium', 'high'], true)
            ? $decoded['risk_level']
            : \App\Models\ProjectSimilarity::riskLevelForScore($score);

        $overlap = $decoded['overlap_areas'] ?? [];
        if (! is_array($overlap)) {
            $overlap = [];
        }

        return [
            'similarity_score' => $score,
            'risk_level' => $risk,
            'summary' => (string) ($decoded['summary'] ?? 'Similarity assessed by AI.'),
            'overlap_areas' => array_values(array_filter(array_map('strval', $overlap))),
        ];
    }

    /**
     * @return array{overview: string, significance: string, readme_body: string}|null
     */
    public function summarizeShowcaseDocumentation(string $title, string $sourceText): ?array
    {
        $system = <<<'PROMPT'
You summarize student project documentation for a university repository showcase.
Respond with JSON only:
{
  "overview": "<2-3 concise sentences. If the document describes UI/layout/components, mention structured layout and reusable parts. Otherwise summarize what the system does.>",
  "significance": "<2-4 sentences on assignment purpose, real-world problem, and why the project matters>",
  "readme_body": "<markdown excerpt: title as H1 line, short intro, ## Features bullet list, ## Repository structure bullets if inferable, ## Version line>"
}
Use only facts from the provided text. Do not invent technologies not mentioned.
PROMPT;

        $user = "Project title: {$title}\n\nDocumentation excerpt:\n{$sourceText}";

        $result = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ]);

        $content = (string) data_get($result, 'message.content', '');
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            $decoded = json_decode($this->extractJson($content), true);
        }

        if (! is_array($decoded)) {
            Log::warning('Ollama showcase summary JSON parse failed', ['content' => $content]);

            return null;
        }

        return [
            'overview' => trim((string) ($decoded['overview'] ?? '')),
            'significance' => trim((string) ($decoded['significance'] ?? '')),
            'readme_body' => trim((string) ($decoded['readme_body'] ?? '')),
        ];
    }

    private function baseUrl(): string
    {
        return (string) config('ollama.base_url', 'http://127.0.0.1.11434');
    }

    private function extractJson(string $content): string
    {
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            return $matches[0];
        }

        return $content;
    }
}
