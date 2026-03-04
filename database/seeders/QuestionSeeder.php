<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\QuestionModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のデータをすべて削除
        QuestionModel::query()->delete();

        $this->seedSimilarities();
        $this->seedVocabulary();
        $this->seedPatternRecognition();
        $this->seedMatrixReasoning();
        $this->seedDigitSpan();
        $this->seedArithmetic();
        $this->seedSymbolSearch();
        $this->seedCoding();
    }

    /**
     * A: 類似（Similarities）- 30問のプールから10問ランダム選択
     */
    private function seedSimilarities(): void
    {
        $questionPool = [
            ['content' => 'リンゴとバナナの共通点は何ですか？', 'hint' => '食べ物の種類を考えてください'],
            ['content' => '犬と猫の共通点は何ですか？', 'hint' => '生き物の分類を考えてください'],
            ['content' => '椅子とテーブルの共通点は何ですか？', 'hint' => '用途を考えてください'],
            ['content' => '鉛筆とペンの共通点は何ですか？', 'hint' => '何に使うものか考えてください'],
            ['content' => '太陽と月の共通点は何ですか？', 'hint' => '空にあるものとして'],
            ['content' => '車と自転車の共通点は何ですか？', 'hint' => '移動手段として'],
            ['content' => '本と新聞の共通点は何ですか？', 'hint' => '情報を得る手段'],
            ['content' => '冬と夏の共通点は何ですか？', 'hint' => '季節として'],
            ['content' => '山と谷の共通点は何ですか？', 'hint' => '地形として'],
            ['content' => '赤と青の共通点は何ですか？', 'hint' => '色として'],
            ['content' => '笑いと涙の共通点は何ですか？', 'hint' => '感情表現'],
            ['content' => '音楽と絵画の共通点は何ですか？', 'hint' => '芸術として'],
            ['content' => '朝と夜の共通点は何ですか？', 'hint' => '一日の時間帯'],
            ['content' => '医師と看護師の共通点は何ですか？', 'hint' => '職業として'],
            ['content' => '電話とメールの共通点は何ですか？', 'hint' => '通信手段'],
            ['content' => '川と海の共通点は何ですか？', 'hint' => '水のある場所'],
            ['content' => '鳥と飛行機の共通点は何ですか？', 'hint' => '空を飛ぶもの'],
            ['content' => '靴と帽子の共通点は何ですか？', 'hint' => '身につけるもの'],
            ['content' => '春と秋の共通点は何ですか？', 'hint' => '季節の特徴'],
            ['content' => '鏡とカメラの共通点は何ですか？', 'hint' => '映すもの'],
            ['content' => '時計とカレンダーの共通点は何ですか？', 'hint' => '時間を表すもの'],
            ['content' => '橋とトンネルの共通点は何ですか？', 'hint' => '交通インフラ'],
            ['content' => '雨と雪の共通点は何ですか？', 'hint' => '天気現象'],
            ['content' => 'ドアと窓の共通点は何ですか？', 'hint' => '建物の一部'],
            ['content' => '鍵と暗証番号の共通点は何ですか？', 'hint' => 'セキュリティ'],
            ['content' => '砂糖と塩の共通点は何ですか？', 'hint' => '調味料'],
            ['content' => '教師と生徒の共通点は何ですか？', 'hint' => '学習に関わる'],
            ['content' => '誕生日と結婚記念日の共通点は何ですか？', 'hint' => '記念日'],
            ['content' => '野球とサッカーの共通点は何ですか？', 'hint' => 'スポーツ'],
            ['content' => '詩と小説の共通点は何ですか？', 'hint' => '文学作品'],
        ];

        // ランダムに10問選択
        $selectedQuestions = $this->selectRandom($questionPool, 10);

        foreach ($selectedQuestions as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'A',
                'sequence_number' => $index + 1,
                'content' => $question['content'],
                'question_type' => 'free_text',
                'correct_answer' => null,
                'options' => null,
                'max_points' => 2,
                'hint' => $question['hint'],
            ]);
        }
    }

    /**
     * B: 語彙（Vocabulary）- 30問のプールから10問ランダム選択
     */
    private function seedVocabulary(): void
    {
        $questionPool = [
            ['word' => '勇気', 'hint' => '恐怖に打ち勝つ心'],
            ['word' => '寛容', 'hint' => '他人を受け入れる心'],
            ['word' => '誠実', 'hint' => '嘘をつかず正直であること'],
            ['word' => '慎重', 'hint' => '注意深く行動すること'],
            ['word' => '楽観的', 'hint' => '物事を明るく考える'],
            ['word' => '協力', 'hint' => '力を合わせること'],
            ['word' => '創造', 'hint' => '新しいものを作り出すこと'],
            ['word' => '独立', 'hint' => '自分で決めて行動すること'],
            ['word' => '調和', 'hint' => 'バランスが取れている状態'],
            ['word' => '尊重', 'hint' => '相手を大切にすること'],
            ['word' => '効率', 'hint' => '無駄なく物事を進めること'],
            ['word' => '柔軟', 'hint' => '状況に応じて対応できること'],
            ['word' => '継続', 'hint' => '途中でやめずに続けること'],
            ['word' => '革新', 'hint' => '新しいやり方を取り入れること'],
            ['word' => '公平', 'hint' => 'えこひいきせず平等に扱うこと'],
            ['word' => '謙虚', 'hint' => '控えめで偉ぶらないこと'],
            ['word' => '献身', 'hint' => '自分を犠牲にして尽くすこと'],
            ['word' => '寛大', 'hint' => '心が広く許すこと'],
            ['word' => '洞察', 'hint' => '物事の本質を見抜くこと'],
            ['word' => '忍耐', 'hint' => '苦しみに耐えること'],
            ['word' => '積極的', 'hint' => '自ら進んで行動すること'],
            ['word' => '慈悲', 'hint' => '思いやりの心'],
            ['word' => '正義', 'hint' => '正しいことを貫くこと'],
            ['word' => '責任', 'hint' => '自分の役割を果たすこと'],
            ['word' => '信頼', 'hint' => '相手を信じること'],
            ['word' => '感謝', 'hint' => 'ありがたく思う気持ち'],
            ['word' => '誇り', 'hint' => '自分を価値あると思う気持ち'],
            ['word' => '思慮深い', 'hint' => 'よく考えて行動すること'],
            ['word' => '勤勉', 'hint' => 'まじめに働くこと'],
            ['word' => '友情', 'hint' => '友達との絆'],
        ];

        $selectedQuestions = $this->selectRandom($questionPool, 10);

        foreach ($selectedQuestions as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'B',
                'sequence_number' => $index + 1,
                'content' => "「{$question['word']}」という言葉の意味を説明してください。",
                'question_type' => 'free_text',
                'correct_answer' => null,
                'options' => null,
                'max_points' => 2,
                'hint' => $question['hint'],
            ]);
        }
    }

    /**
     * C: 規則発見（Pattern Recognition）- 24問のプールから12問ランダム選択
     */
    private function seedPatternRecognition(): void
    {
        $questionPool = [
            ['pattern' => '2, 4, 6, 8, ?', 'options' => ['A' => '9', 'B' => '10', 'C' => '11', 'D' => '12'], 'correct' => 'B'],
            ['pattern' => '1, 3, 5, 7, ?', 'options' => ['A' => '8', 'B' => '9', 'C' => '10', 'D' => '11'], 'correct' => 'B'],
            ['pattern' => '10, 20, 30, 40, ?', 'options' => ['A' => '45', 'B' => '50', 'C' => '55', 'D' => '60'], 'correct' => 'B'],
            ['pattern' => '1, 4, 9, 16, ?', 'options' => ['A' => '20', 'B' => '25', 'C' => '30', 'D' => '36'], 'correct' => 'B'],
            ['pattern' => '5, 10, 15, 20, ?', 'options' => ['A' => '22', 'B' => '25', 'C' => '27', 'D' => '30'], 'correct' => 'B'],
            ['pattern' => '100, 90, 80, 70, ?', 'options' => ['A' => '50', 'B' => '60', 'C' => '65', 'D' => '55'], 'correct' => 'B'],
            ['pattern' => '3, 6, 12, 24, ?', 'options' => ['A' => '36', 'B' => '48', 'C' => '60', 'D' => '72'], 'correct' => 'B'],
            ['pattern' => '1, 2, 4, 8, ?', 'options' => ['A' => '12', 'B' => '16', 'C' => '20', 'D' => '24'], 'correct' => 'B'],
            ['pattern' => '50, 45, 40, 35, ?', 'options' => ['A' => '25', 'B' => '30', 'C' => '32', 'D' => '28'], 'correct' => 'B'],
            ['pattern' => '7, 14, 21, 28, ?', 'options' => ['A' => '32', 'B' => '35', 'C' => '38', 'D' => '42'], 'correct' => 'B'],
            ['pattern' => '2, 6, 18, 54, ?', 'options' => ['A' => '108', 'B' => '162', 'C' => '216', 'D' => '270'], 'correct' => 'B'],
            ['pattern' => '81, 27, 9, 3, ?', 'options' => ['A' => '0', 'B' => '1', 'C' => '2', 'D' => '3'], 'correct' => 'B'],
            ['pattern' => '11, 22, 33, 44, ?', 'options' => ['A' => '50', 'B' => '55', 'C' => '60', 'D' => '66'], 'correct' => 'B'],
            ['pattern' => '4, 8, 16, 32, ?', 'options' => ['A' => '48', 'B' => '64', 'C' => '80', 'D' => '96'], 'correct' => 'B'],
            ['pattern' => '15, 30, 45, 60, ?', 'options' => ['A' => '70', 'B' => '75', 'C' => '80', 'D' => '85'], 'correct' => 'B'],
            ['pattern' => '1, 1, 2, 3, 5, 8, ?', 'options' => ['A' => '11', 'B' => '13', 'C' => '15', 'D' => '17'], 'correct' => 'B'],
            ['pattern' => '64, 32, 16, 8, ?', 'options' => ['A' => '2', 'B' => '4', 'C' => '6', 'D' => '8'], 'correct' => 'B'],
            ['pattern' => '6, 12, 24, 48, ?', 'options' => ['A' => '72', 'B' => '96', 'C' => '120', 'D' => '144'], 'correct' => 'B'],
            ['pattern' => '99, 88, 77, 66, ?', 'options' => ['A' => '44', 'B' => '55', 'C' => '60', 'D' => '50'], 'correct' => 'B'],
            ['pattern' => '13, 26, 39, 52, ?', 'options' => ['A' => '60', 'B' => '65', 'C' => '70', 'D' => '75'], 'correct' => 'B'],
            ['pattern' => '3, 9, 27, 81, ?', 'options' => ['A' => '162', 'B' => '243', 'C' => '324', 'D' => '405'], 'correct' => 'B'],
            ['pattern' => '125, 100, 75, 50, ?', 'options' => ['A' => '20', 'B' => '25', 'C' => '30', 'D' => '35'], 'correct' => 'B'],
            ['pattern' => '8, 16, 32, 64, ?', 'options' => ['A' => '96', 'B' => '128', 'C' => '160', 'D' => '192'], 'correct' => 'B'],
            ['pattern' => '17, 34, 51, 68, ?', 'options' => ['A' => '80', 'B' => '85', 'C' => '90', 'D' => '95'], 'correct' => 'B'],
        ];

        $selectedQuestions = $this->selectRandom($questionPool, 12);

        foreach ($selectedQuestions as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'C',
                'sequence_number' => $index + 1,
                'content' => "次の数列の規則性を見つけて、?に入る数を選んでください：{$question['pattern']}",
                'question_type' => 'multiple_choice',
                'correct_answer' => $question['correct'],
                'options' => $question['options'],
                'max_points' => 1,
                'hint' => null,
            ]);
        }
    }

    /**
     * D: 簡易マトリクス（Matrix Reasoning）- 20問のプールから10問ランダム選択
     */
    private function seedMatrixReasoning(): void
    {
        $questionPool = [];

        // パターン1: 図形の数が増えるパターン
        for ($i = 1; $i <= 5; $i++) {
            $questionPool[] = [
                'description' => "図形が{$i}個から" . ($i + 1) . "個に増える規則",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // パターン2: 図形が回転するパターン
        for ($i = 1; $i <= 5; $i++) {
            $questionPool[] = [
                'description' => "図形が" . ($i * 45) . "度回転する規則",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // パターン3: 色が変わるパターン
        for ($i = 1; $i <= 5; $i++) {
            $questionPool[] = [
                'description' => "図形の色が段階的に変わる規則",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // パターン4: サイズが変わるパターン
        for ($i = 1; $i <= 5; $i++) {
            $questionPool[] = [
                'description' => "図形のサイズが変化する規則",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        $selectedQuestions = $this->selectRandom($questionPool, 10);

        foreach ($selectedQuestions as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'D',
                'sequence_number' => $index + 1,
                'content' => "マトリクスの規則を見つけて、空欄に入る図形を選んでください。{$question['description']}",
                'question_type' => 'multiple_choice',
                'correct_answer' => $question['correct'],
                'options' => $question['options'],
                'max_points' => 1,
                'hint' => null,
            ]);
        }
    }

    /**
     * E: 数唱（Digit Span）- 27系列のプールから18系列ランダム選択
     */
    private function seedDigitSpan(): void
    {
        $sequencePool = [
            // 3桁
            '258', '714', '639', '482', '157', '926', '341', '785', '692',
            // 4桁
            '5827', '9146', '3682', '7459', '1834', '6291', '4753', '8126', '2947',
            // 5桁
            '39825', '74162', '58394', '91647', '26183', '47925', '83516', '65829', '12749',
            // 6桁
            '582749', '914637', '368259', '745182', '183649', '629174', '475386', '812693', '294751',
        ];

        $selectedSequences = $this->selectRandom($sequencePool, 18);

        foreach ($selectedSequences as $index => $sequence) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'E',
                'sequence_number' => $index + 1,
                'content' => "次の数字を覚えて、そのまま入力してください：{$sequence}",
                'question_type' => 'sequence',
                'correct_answer' => $sequence,
                'options' => null,
                'max_points' => 1,
                'hint' => '数字を一つずつ確認してください',
            ]);
        }
    }

    /**
     * F: 暗算（Arithmetic）- 20問のプールから10問ランダム選択
     */
    private function seedArithmetic(): void
    {
        $questionPool = [
            ['problem' => '15 + 27 = ?', 'options' => ['A' => '40', 'B' => '42', 'C' => '44', 'D' => '46'], 'correct' => 'B'],
            ['problem' => '48 - 19 = ?', 'options' => ['A' => '27', 'B' => '29', 'C' => '31', 'D' => '33'], 'correct' => 'B'],
            ['problem' => '12 × 6 = ?', 'options' => ['A' => '68', 'B' => '72', 'C' => '76', 'D' => '80'], 'correct' => 'B'],
            ['problem' => '144 ÷ 12 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '14', 'D' => '16'], 'correct' => 'B'],
            ['problem' => '25 + 38 = ?', 'options' => ['A' => '61', 'B' => '63', 'C' => '65', 'D' => '67'], 'correct' => 'B'],
            ['problem' => '82 - 45 = ?', 'options' => ['A' => '35', 'B' => '37', 'C' => '39', 'D' => '41'], 'correct' => 'B'],
            ['problem' => '15 × 4 = ?', 'options' => ['A' => '56', 'B' => '60', 'C' => '64', 'D' => '68'], 'correct' => 'B'],
            ['problem' => '96 ÷ 8 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '14', 'D' => '16'], 'correct' => 'B'],
            ['problem' => '34 + 29 = ?', 'options' => ['A' => '61', 'B' => '63', 'C' => '65', 'D' => '67'], 'correct' => 'B'],
            ['problem' => '75 - 38 = ?', 'options' => ['A' => '35', 'B' => '37', 'C' => '39', 'D' => '41'], 'correct' => 'B'],
            ['problem' => '18 × 5 = ?', 'options' => ['A' => '85', 'B' => '90', 'C' => '95', 'D' => '100'], 'correct' => 'B'],
            ['problem' => '108 ÷ 9 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '14', 'D' => '16'], 'correct' => 'B'],
            ['problem' => '46 + 57 = ?', 'options' => ['A' => '101', 'B' => '103', 'C' => '105', 'D' => '107'], 'correct' => 'B'],
            ['problem' => '91 - 48 = ?', 'options' => ['A' => '41', 'B' => '43', 'C' => '45', 'D' => '47'], 'correct' => 'B'],
            ['problem' => '22 × 3 = ?', 'options' => ['A' => '62', 'B' => '66', 'C' => '70', 'D' => '74'], 'correct' => 'B'],
            ['problem' => '135 ÷ 15 = ?', 'options' => ['A' => '7', 'B' => '9', 'C' => '11', 'D' => '13'], 'correct' => 'B'],
            ['problem' => '53 + 68 = ?', 'options' => ['A' => '119', 'B' => '121', 'C' => '123', 'D' => '125'], 'correct' => 'B'],
            ['problem' => '84 - 29 = ?', 'options' => ['A' => '53', 'B' => '55', 'C' => '57', 'D' => '59'], 'correct' => 'B'],
            ['problem' => '16 × 7 = ?', 'options' => ['A' => '108', 'B' => '112', 'C' => '116', 'D' => '120'], 'correct' => 'B'],
            ['problem' => '120 ÷ 10 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '14', 'D' => '16'], 'correct' => 'B'],
        ];

        $selectedQuestions = $this->selectRandom($questionPool, 10);

        foreach ($selectedQuestions as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'F',
                'sequence_number' => $index + 1,
                'content' => "暗算で計算してください：{$question['problem']}",
                'question_type' => 'multiple_choice',
                'correct_answer' => $question['correct'],
                'options' => $question['options'],
                'max_points' => 1,
                'hint' => null,
            ]);
        }
    }

    /**
     * G: 探索（Symbol Search）- 36問のプールから24問ランダム選択
     */
    private function seedSymbolSearch(): void
    {
        $symbols = ['○', '△', '□', '◇', '☆', '◎', '▽', '■', '◆', '★'];
        $questionPool = [];

        for ($i = 0; $i < 36; $i++) {
            $targetSymbol = $symbols[$i % count($symbols)];
            $questionPool[] = [
                'target' => $targetSymbol,
                'description' => "記号 {$targetSymbol} が右側のグループに含まれているか答えてください",
            ];
        }

        $selectedQuestions = $this->selectRandom($questionPool, 24);

        foreach ($selectedQuestions as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'G',
                'sequence_number' => $index + 1,
                'content' => $question['description'],
                'question_type' => 'time_based',
                'correct_answer' => '○',
                'options' => ['○' => 'はい', '×' => 'いいえ'],
                'max_points' => 1,
                'hint' => null,
            ]);
        }
    }

    /**
     * H: 符号化（Coding）- 90問のプールから60問ランダム選択
     */
    private function seedCoding(): void
    {
        $codeMapping = [
            '1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E',
            '6' => 'F', '7' => 'G', '8' => 'H', '9' => 'I', '0' => 'J',
        ];

        $questionPool = [];
        for ($i = 0; $i < 90; $i++) {
            $digit = (string) ($i % 10);
            $questionPool[] = [
                'digit' => $digit,
                'code' => $codeMapping[$digit],
            ];
        }

        $selectedQuestions = $this->selectRandom($questionPool, 60);

        foreach ($selectedQuestions as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'H',
                'sequence_number' => $index + 1,
                'content' => "数字 {$question['digit']} に対応する記号を選んでください",
                'question_type' => 'time_based',
                'correct_answer' => $question['code'],
                'options' => $codeMapping,
                'max_points' => 1,
                'hint' => '表を参照してください',
            ]);
        }
    }

    /**
     * 配列からランダムに指定数の要素を選択
     */
    private function selectRandom(array $array, int $count): array
    {
        $keys = array_rand($array, min($count, count($array)));
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        return array_map(fn($key) => $array[$key], $keys);
    }
}

