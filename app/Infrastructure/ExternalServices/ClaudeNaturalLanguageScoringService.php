<?php

declare(strict_types=1);

namespace App\Infrastructure\ExternalServices;

use App\Domain\Assessment\Services\NaturalLanguageScoringInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ClaudeNaturalLanguageScoringService implements NaturalLanguageScoringInterface
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-haiku-4-5-20251001';

    public function __construct(private readonly string $apiKey) {}

    /**
     * 類似問題の自然言語採点
     *
     * 1点: 単語レベルで概念が合っている（短い単語・フレーズ）
     * 2点: 文章・説明レベルで正確に共通点を捉えている
     * 0点: 見当違い・無回答
     */
    public function scoreSimilarityAnswer(string $question, string $response): int
    {
        $prompt = <<<PROMPT
あなたはWAIS知能検査の類似問題を採点する採点者です。

問題: {$question}
回答: {$response}

以下の基準で採点し、数字（0、1、または2）のみを返してください。他の文字は一切含めないでください。

採点基準:
- 2点: 回答が文章または詳しい説明で、共通点を正確・適切に表現している
- 1点: 回答が単語または短いフレーズで、共通点の概念を捉えているが説明が不十分
- 0点: 共通点とは無関係、または意味をなさない回答

数字のみ返答:
PROMPT;

        try {
            $res = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post(self::API_URL, [
                'model'      => self::MODEL,
                'max_tokens' => 8,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $text = trim($res->json('content.0.text') ?? '');
            $score = (int) $text;

            return in_array($score, [0, 1, 2], true) ? $score : 0;
        } catch (\Throwable $e) {
            Log::error('Claude scoring API error', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
