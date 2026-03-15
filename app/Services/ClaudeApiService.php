<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class ClaudeApiService
{
    /** 採点・判定プロンプト v3.0 をシステムプロンプトとして埋め込む */
    private const SYSTEM_PROMPT = <<<'PROMPT'
# 認知の手すりチェック Lite 採点・判定プロンプト v3.0
> **位置づけ:** 受検実施の出力（採点用サマリー）を受け取り、各サブテストを採点した上で、生活と仕事の「手すり（対策）」を含む最終レポートを生成する。
> **設計思想:** 「測るより、守る。評価より、運用。」知能を測る簡易検査ではなく、認知資源の使い方を整えるためのチェックである。擬似IQや能力の断定は行わず、強み・負荷・次の一手にフォーカスする。
-----
あなたは「認知の手すりチェック Lite」の専属分析アシスタントである。
受検実施プロンプトで回収した「採点用サマリー」をもとに採点を行い、現在の状態要因（睡眠・疲労など）を加味した上で、生活・仕事で使えるフィードバックレポートを生成すること。
## 大前提（システム指示）
- 本チェックは正式な心理検査ではなく、日常の認知運用ステータスを確認するツールである。
- 診断・医療判断・採用判断・能力断定には絶対に使用しないこと。
- 「低い」「弱い」といったネガティブな断定を避け、「負荷がかかりやすい」「〜の条件下で崩れやすい」という表現を用いること。
- 状態要因（睡眠不足、高疲労、焦り）がある場合は、スコアの低下を「能力」ではなく「現在のコンディション（状態）」と結びつけて解釈すること。
- **ユーザーに提示するレポートの文章は、体言止めを避け、すべて丁寧な「です・ます調」で記述すること。**
-----
## 4資源モデルと解釈指針
- **言語整理（旧VCI）：** 要点をつかむ・言い換える・輪郭を作る。仕事・生活で使える言語化ができているかを評価する。
- **構造理解（旧PRI）：** 規則発見・分類・手順や型を読む。型を先に置けるか、構造を把握できているかを評価する。
- **保持操作（旧WMI）：** 情報を保持しながら並べ替える・条件を扱う。実務に近い軽い保持負荷に耐えられるかを評価する。
- **速度耐性（旧PSI）：** 時間圧の中で処理を進める・焦りに崩れにくい。速度そのものより、時間圧への耐性と慎重さのバランスを評価する。
## 状態チェック（解釈用メタデータ）
- 睡眠（十分 / やや不足 / 不足）
- 疲労（低 / 中 / 高）
- 焦り（低 / 中 / 高）
- 集中の波（安定 / やや波あり / 波あり）
※「睡眠不足＋高疲労」の場合は、レポート内で休養と再測定の提案を強化すること。
※保持操作・速度耐性が低めかつ状態要因が悪い場合は、「焦りや疲労でスコアが落ちている可能性」に言及すること。
PROMPT;

    /**
     * スコアと状態要因を受け取り、Claude API でレポートを生成する。
     *
     * @param  array<string, mixed>  $scores  ['VCI' => ['raw' => 28, 'max' => 40, 'pct' => 70.0], ...]
     * @param  array<string, string> $condition ['sleep' => '十分', 'fatigue' => '低', 'anxiety' => '低', 'focus' => '安定']
     * @param  array<string, mixed>  $strengthOrder  [[type, label, pct], ...] 上位2
     * @param  array<string, mixed>  $weaknessOrder  [[type, label, pct], ...] 下位2
     * @return array<string, mixed>
     */
    public function generateReport(
        array $scores,
        array $condition,
        array $strengthOrder,
        array $weaknessOrder,
    ): array {
        $userMessage = $this->buildUserMessage($scores, $condition, $strengthOrder, $weaknessOrder);

        $response = Http::timeout(30)->withHeaders([
            'x-api-key'         => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => config('services.anthropic.model', 'claude-sonnet-4-6'),
            'max_tokens' => 2000,
            'system'     => self::SYSTEM_PROMPT,
            'messages'   => [['role' => 'user', 'content' => $userMessage]],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Claude API error: ' . $response->status() . ' ' . $response->body());
        }

        $text = $response->json('content.0.text', '');

        return $this->parseJson($text);
    }

    /**
     * @param  array<string, mixed>  $scores
     * @param  array<string, string> $condition
     * @param  array<string, mixed>  $strengthOrder
     * @param  array<string, mixed>  $weaknessOrder
     */
    private function buildUserMessage(
        array $scores,
        array $condition,
        array $strengthOrder,
        array $weaknessOrder,
    ): string {
        $indexNames = [
            'VCI' => '言語整理（旧VCI）',
            'PRI' => '構造理解（旧PRI）',
            'WMI' => '保持操作（旧WMI）',
            'PSI' => '速度耐性（旧PSI）',
        ];

        $conditionText = implode(' / ', [
            "睡眠: {$condition['sleep']}",
            "疲労: {$condition['fatigue']}",
            "焦り: {$condition['anxiety']}",
            "集中の波: {$condition['focus']}",
        ]);

        $scoresText = '';

        foreach ($scores as $type => $s) {
            $name        = $indexNames[$type] ?? $type;
            $scoresText .= "・{$name}: {$s['raw']}/{$s['max']}点（{$s['pct']}%）\n";
        }

        $strengthText = '';

        foreach ($strengthOrder as $i => $s) {
            $no            = $i + 1;
            $name          = $indexNames[$s['type']] ?? $s['type'];
            $strengthText .= "{$no}. {$name}（{$s['pct']}%）\n";
        }

        $weaknessText = '';

        foreach ($weaknessOrder as $i => $w) {
            $no            = $i + 1;
            $name          = $indexNames[$w['type']] ?? $w['type'];
            $weaknessText .= "{$no}. {$name}（{$w['pct']}%）\n";
        }

        return <<<MSG
採点は完了しています。以下の結果をもとにレポートを生成してください。

【状態チェック】
{$conditionText}

【各資源のスコア（正答率）】
{$scoresText}
【強みとして解釈してください（正答率上位2資源）】
{$strengthText}
【負荷ポイントとして解釈してください（正答率下位2資源）】
{$weaknessText}
以下のJSON形式のみで出力してください（前後に余分なテキストやコードブロックを含めないでください）：
{
  "strength_top2": [
    {"resource": "資源名", "description": "強みと仕事・生活での活用方法（2〜3文、です・ます調）"},
    {"resource": "資源名", "description": "強みと仕事・生活での活用方法（2〜3文、です・ます調）"}
  ],
  "weakness_top2": [
    {"resource": "資源名", "description": "負荷がかかりやすい場面と対処ヒント（2〜3文、です・ます調）"},
    {"resource": "資源名", "description": "負荷がかかりやすい場面と対処ヒント（2〜3文、です・ます調）"}
  ],
  "work_strategy": "仕事での具体的な守り方（1〜2文、です・ます調）",
  "life_strategy": "生活での具体的な守り方（1〜2文、です・ます調）",
  "strength_strategy": "強みを伸ばす具体的な提案（1〜2文、です・ます調）",
  "ai_advice": null
}

※ ai_adviceは「睡眠:不足」または「疲労:高」の場合のみ、休養と再測定を促す文章（1〜2文）を設定し、それ以外はnullにしてください。
MSG;
    }

    /** @return array<string, mixed> */
    private function parseJson(string $text): array
    {
        // コードブロック除去
        $text = preg_replace('/```(?:json)?\s*([\s\S]*?)```/', '$1', $text) ?? $text;

        // 最初の { から最後の } を抽出
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new RuntimeException('JSON block not found in Claude response');
        }

        $json   = substr($text, $start, $end - $start + 1);
        $parsed = json_decode($json, true);

        if (! is_array($parsed)) {
            throw new RuntimeException('Failed to decode Claude response as JSON');
        }

        return $parsed;
    }
}
