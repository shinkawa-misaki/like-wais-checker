<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Services;

interface NaturalLanguageScoringInterface
{
    /**
     * 類似問題の自然言語採点
     *
     * @return int 0=不正解, 1=単語レベルの一致, 2=文章・概念レベルの一致
     */
    public function scoreSimilarityAnswer(string $question, string $response): int;
}
