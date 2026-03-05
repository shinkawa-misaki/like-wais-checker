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
     * A: 類似（Similarities）- 60問のプール全てを保存
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
            // 新規追加30問
            ['content' => '火と水の共通点は何ですか？', 'hint' => '自然の要素'],
            ['content' => '戦争と平和の共通点は何ですか？', 'hint' => '社会の状態'],
            ['content' => '希望と絶望の共通点は何ですか？', 'hint' => '心の状態'],
            ['content' => '始まりと終わりの共通点は何ですか？', 'hint' => '時間の概念'],
            ['content' => '愛と憎しみの共通点は何ですか？', 'hint' => '強い感情'],
            ['content' => '成功と失敗の共通点は何ですか？', 'hint' => '結果として'],
            ['content' => '真実と嘘の共通点は何ですか？', 'hint' => '情報として'],
            ['content' => '過去と未来の共通点は何ですか？', 'hint' => '時間軸'],
            ['content' => '原因と結果の共通点は何ですか？', 'hint' => '因果関係'],
            ['content' => '質問と回答の共通点は何ですか？', 'hint' => 'コミュニケーション'],
            ['content' => 'テレビとラジオの共通点は何ですか？', 'hint' => 'メディア'],
            ['content' => 'パンと米の共通点は何ですか？', 'hint' => '主食'],
            ['content' => '蝶と花の共通点は何ですか？', 'hint' => '自然の美しさ'],
            ['content' => '勝利と敗北の共通点は何ですか？', 'hint' => '競争の結果'],
            ['content' => '光と影の共通点は何ですか？', 'hint' => '光の現象'],
            ['content' => '記憶と忘却の共通点は何ですか？', 'hint' => '脳の働き'],
            ['content' => '病気と健康の共通点は何ですか？', 'hint' => '身体の状態'],
            ['content' => '善と悪の共通点は何ですか？', 'hint' => '道徳的概念'],
            ['content' => '富と貧困の共通点は何ですか？', 'hint' => '経済状態'],
            ['content' => '理想と現実の共通点は何ですか？', 'hint' => '認識'],
            ['content' => '自由と束縛の共通点は何ですか？', 'hint' => '状態の概念'],
            ['content' => '沈黙と騒音の共通点は何ですか？', 'hint' => '音の状態'],
            ['content' => '秩序と混沌の共通点は何ですか？', 'hint' => 'システムの状態'],
            ['content' => '知識と無知の共通点は何ですか？', 'hint' => '認識の状態'],
            ['content' => '伝統と革新の共通点は何ですか？', 'hint' => '文化の要素'],
            ['content' => '個人と集団の共通点は何ですか？', 'hint' => '社会の単位'],
            ['content' => '科学と芸術の共通点は何ですか？', 'hint' => '人間の営み'],
            ['content' => '肉体と精神の共通点は何ですか？', 'hint' => '人間の構成要素'],
            ['content' => '言葉と沈黙の共通点は何ですか？', 'hint' => 'コミュニケーション手段'],
            ['content' => '夢と現実の共通点は何ですか？', 'hint' => '経験'],
        ];

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
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
     * B: 語彙（Vocabulary）- 60問のプール全てを保存
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
            // 新規追加30問
            ['word' => '誠意', 'hint' => '真心を持って接すること'],
            ['word' => '率直', 'hint' => 'ありのままを伝えること'],
            ['word' => '寛恕', 'hint' => '過ちを許すこと'],
            ['word' => '清廉', 'hint' => '心が清く正しいこと'],
            ['word' => '高潔', 'hint' => '品格が高く立派なこと'],
            ['word' => '真摯', 'hint' => 'まじめで熱心なこと'],
            ['word' => '温厚', 'hint' => '穏やかで優しいこと'],
            ['word' => '冷静', 'hint' => '落ち着いて判断すること'],
            ['word' => '明朗', 'hint' => '明るく朗らかなこと'],
            ['word' => '実直', 'hint' => 'まじめで正直なこと'],
            ['word' => '純粋', 'hint' => '混じり気がないこと'],
            ['word' => '賢明', 'hint' => '知恵があり判断力に優れること'],
            ['word' => '博識', 'hint' => '広い知識を持つこと'],
            ['word' => '機知', 'hint' => '状況に応じた素早い判断力'],
            ['word' => '洗練', 'hint' => '磨かれて上品になること'],
            ['word' => '品格', 'hint' => '人としての気品'],
            ['word' => '威厳', 'hint' => '堂々として尊敬される様子'],
            ['word' => '風格', 'hint' => '立派で貫禄があること'],
            ['word' => '節度', 'hint' => '度を越さない適度な行動'],
            ['word' => '配慮', 'hint' => '相手の立場を考えること'],
            ['word' => '気配り', 'hint' => '細かく注意を向けること'],
            ['word' => '思いやり', 'hint' => '他人の気持ちを察する心'],
            ['word' => '共感', 'hint' => '相手の感情を理解すること'],
            ['word' => '寄り添う', 'hint' => '側で支えること'],
            ['word' => '奉仕', 'hint' => '人のために尽くすこと'],
            ['word' => '貢献', 'hint' => '役に立つこと'],
            ['word' => '犠牲', 'hint' => '自分を投げ出すこと'],
            ['word' => '献身的', 'hint' => '身を捧げて尽くすこと'],
            ['word' => '無私', 'hint' => '私心がないこと'],
            ['word' => '公正', 'hint' => '偏りなく正しいこと'],
        ];

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
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
     * C: 規則発見（Pattern Recognition）- 50問のプール全てを保存
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
            // 新規追加26問
            ['pattern' => '9, 18, 27, 36, ?', 'options' => ['A' => '42', 'B' => '45', 'C' => '48', 'D' => '54'], 'correct' => 'B'],
            ['pattern' => '200, 150, 100, 50, ?', 'options' => ['A' => '0', 'B' => '10', 'C' => '25', 'D' => '-50'], 'correct' => 'A'],
            ['pattern' => '5, 25, 125, 625, ?', 'options' => ['A' => '2500', 'B' => '3125', 'C' => '3750', 'D' => '5000'], 'correct' => 'B'],
            ['pattern' => '144, 121, 100, 81, ?', 'options' => ['A' => '60', 'B' => '64', 'C' => '72', 'D' => '49'], 'correct' => 'B'],
            ['pattern' => '12, 24, 36, 48, ?', 'options' => ['A' => '54', 'B' => '60', 'C' => '64', 'D' => '72'], 'correct' => 'B'],
            ['pattern' => '1, 8, 27, 64, ?', 'options' => ['A' => '100', 'B' => '125', 'C' => '144', 'D' => '216'], 'correct' => 'B'],
            ['pattern' => '256, 128, 64, 32, ?', 'options' => ['A' => '8', 'B' => '16', 'C' => '24', 'D' => '12'], 'correct' => 'B'],
            ['pattern' => '2, 5, 11, 23, ?', 'options' => ['A' => '35', 'B' => '47', 'C' => '51', 'D' => '46'], 'correct' => 'B'],
            ['pattern' => '19, 38, 57, 76, ?', 'options' => ['A' => '90', 'B' => '95', 'C' => '100', 'D' => '105'], 'correct' => 'B'],
            ['pattern' => '1000, 900, 800, 700, ?', 'options' => ['A' => '500', 'B' => '600', 'C' => '650', 'D' => '550'], 'correct' => 'B'],
            ['pattern' => '0, 1, 1, 2, 3, 5, 8, ?', 'options' => ['A' => '11', 'B' => '13', 'C' => '15', 'D' => '16'], 'correct' => 'B'],
            ['pattern' => '14, 28, 42, 56, ?', 'options' => ['A' => '64', 'B' => '70', 'C' => '72', 'D' => '84'], 'correct' => 'B'],
            ['pattern' => '512, 256, 128, 64, ?', 'options' => ['A' => '16', 'B' => '32', 'C' => '48', 'D' => '24'], 'correct' => 'B'],
            ['pattern' => '20, 40, 60, 80, ?', 'options' => ['A' => '90', 'B' => '100', 'C' => '110', 'D' => '120'], 'correct' => 'B'],
            ['pattern' => '36, 49, 64, 81, ?', 'options' => ['A' => '90', 'B' => '100', 'C' => '110', 'D' => '121'], 'correct' => 'B'],
            ['pattern' => '16, 32, 48, 64, ?', 'options' => ['A' => '72', 'B' => '80', 'C' => '88', 'D' => '96'], 'correct' => 'B'],
            ['pattern' => '10, 9, 7, 4, ?', 'options' => ['A' => '-1', 'B' => '0', 'C' => '1', 'D' => '2'], 'correct' => 'B'],
            ['pattern' => '21, 42, 63, 84, ?', 'options' => ['A' => '100', 'B' => '105', 'C' => '110', 'D' => '126'], 'correct' => 'B'],
            ['pattern' => '2, 3, 5, 7, 11, ?', 'options' => ['A' => '12', 'B' => '13', 'C' => '15', 'D' => '17'], 'correct' => 'B'],
            ['pattern' => '18, 36, 54, 72, ?', 'options' => ['A' => '80', 'B' => '90', 'C' => '96', 'D' => '108'], 'correct' => 'B'],
            ['pattern' => '25, 50, 75, 100, ?', 'options' => ['A' => '120', 'B' => '125', 'C' => '130', 'D' => '150'], 'correct' => 'B'],
            ['pattern' => '729, 243, 81, 27, ?', 'options' => ['A' => '3', 'B' => '9', 'C' => '18', 'D' => '12'], 'correct' => 'B'],
            ['pattern' => '4, 9, 16, 25, 36, ?', 'options' => ['A' => '42', 'B' => '49', 'C' => '54', 'D' => '64'], 'correct' => 'B'],
            ['pattern' => '23, 46, 69, 92, ?', 'options' => ['A' => '110', 'B' => '115', 'C' => '120', 'D' => '125'], 'correct' => 'B'],
            ['pattern' => '1024, 512, 256, 128, ?', 'options' => ['A' => '32', 'B' => '64', 'C' => '96', 'D' => '48'], 'correct' => 'B'],
        ];

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
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
     * D: 簡易マトリクス（Matrix Reasoning）- 50問のプール全てを保存
     */
    private function seedMatrixReasoning(): void
    {
        $questionPool = [];

        // パターン1: 図形の数が増えるパターン (10問)
        for ($i = 1; $i <= 10; $i++) {
            $questionPool[] = [
                'description' => "図形が{$i}個から段階的に増える規則 (バリエーション{$i})",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // パターン2: 図形が回転するパターン (10問)
        for ($i = 1; $i <= 10; $i++) {
            $angle = ($i * 30) % 360;
            $questionPool[] = [
                'description' => "図形が{$angle}度回転する規則 (バリエーション{$i})",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // パターン3: 色や濃淡が変わるパターン (10問)
        for ($i = 1; $i <= 10; $i++) {
            $questionPool[] = [
                'description' => "図形の色や濃淡が段階的に変わる規則 (バリエーション{$i})",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // パターン4: サイズが変わるパターン (10問)
        for ($i = 1; $i <= 10; $i++) {
            $questionPool[] = [
                'description' => "図形のサイズが段階的に変化する規則 (バリエーション{$i})",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // パターン5: 位置が変わるパターン (10問)
        for ($i = 1; $i <= 10; $i++) {
            $questionPool[] = [
                'description' => "図形の位置が移動する規則 (バリエーション{$i})",
                'options' => ['A' => '選択肢A', 'B' => '選択肢B', 'C' => '選択肢C', 'D' => '選択肢D'],
                'correct' => 'B',
            ];
        }

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
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
     * E: 数唱（Digit Span）- 60系列のプール全てを保存
     */
    private function seedDigitSpan(): void
    {
        $sequencePool = [
            // 3桁 (15パターン)
            '258', '714', '639', '482', '157', '926', '341', '785', '692',
            '531', '846', '279', '164', '953', '372',
            // 4桁 (15パターン)
            '5827', '9146', '3682', '7459', '1834', '6291', '4753', '8126', '2947',
            '3951', '7284', '5173', '9426', '1658', '8397',
            // 5桁 (15パターン)
            '39825', '74162', '58394', '91647', '26183', '47925', '83516', '65829', '12749',
            '54782', '91356', '28741', '63195', '45827', '71964',
            // 6桁 (15パターン)
            '582749', '914637', '368259', '745182', '183649', '629174', '475386', '812693', '294751',
            '537821', '946257', '128574', '739164', '461852', '825379',
        ];

        // 全ての系列をデータベースに保存
        foreach ($sequencePool as $index => $sequence) {
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
     * F: 暗算（Arithmetic）- 50問のプール全てを保存
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
            // 新規追加30問
            ['problem' => '37 + 49 = ?', 'options' => ['A' => '84', 'B' => '86', 'C' => '88', 'D' => '90'], 'correct' => 'B'],
            ['problem' => '63 - 27 = ?', 'options' => ['A' => '34', 'B' => '36', 'C' => '38', 'D' => '40'], 'correct' => 'B'],
            ['problem' => '13 × 8 = ?', 'options' => ['A' => '96', 'B' => '104', 'C' => '108', 'D' => '112'], 'correct' => 'B'],
            ['problem' => '132 ÷ 11 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '13', 'D' => '14'], 'correct' => 'B'],
            ['problem' => '72 + 59 = ?', 'options' => ['A' => '129', 'B' => '131', 'C' => '133', 'D' => '135'], 'correct' => 'B'],
            ['problem' => '98 - 54 = ?', 'options' => ['A' => '42', 'B' => '44', 'C' => '46', 'D' => '48'], 'correct' => 'B'],
            ['problem' => '17 × 6 = ?', 'options' => ['A' => '96', 'B' => '102', 'C' => '108', 'D' => '114'], 'correct' => 'B'],
            ['problem' => '156 ÷ 13 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '14', 'D' => '16'], 'correct' => 'B'],
            ['problem' => '41 + 78 = ?', 'options' => ['A' => '117', 'B' => '119', 'C' => '121', 'D' => '123'], 'correct' => 'B'],
            ['problem' => '87 - 39 = ?', 'options' => ['A' => '46', 'B' => '48', 'C' => '50', 'D' => '52'], 'correct' => 'B'],
            ['problem' => '19 × 4 = ?', 'options' => ['A' => '72', 'B' => '76', 'C' => '80', 'D' => '84'], 'correct' => 'B'],
            ['problem' => '168 ÷ 14 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '14', 'D' => '16'], 'correct' => 'B'],
            ['problem' => '56 + 67 = ?', 'options' => ['A' => '121', 'B' => '123', 'C' => '125', 'D' => '127'], 'correct' => 'B'],
            ['problem' => '76 - 42 = ?', 'options' => ['A' => '32', 'B' => '34', 'C' => '36', 'D' => '38'], 'correct' => 'B'],
            ['problem' => '14 × 9 = ?', 'options' => ['A' => '118', 'B' => '126', 'C' => '132', 'D' => '140'], 'correct' => 'B'],
            ['problem' => '180 ÷ 15 = ?', 'options' => ['A' => '10', 'B' => '12', 'C' => '14', 'D' => '16'], 'correct' => 'B'],
            ['problem' => '64 + 58 = ?', 'options' => ['A' => '120', 'B' => '122', 'C' => '124', 'D' => '126'], 'correct' => 'B'],
            ['problem' => '93 - 56 = ?', 'options' => ['A' => '35', 'B' => '37', 'C' => '39', 'D' => '41'], 'correct' => 'B'],
            ['problem' => '21 × 5 = ?', 'options' => ['A' => '100', 'B' => '105', 'C' => '110', 'D' => '115'], 'correct' => 'B'],
            ['problem' => '195 ÷ 13 = ?', 'options' => ['A' => '13', 'B' => '15', 'C' => '17', 'D' => '19'], 'correct' => 'B'],
            ['problem' => '88 + 47 = ?', 'options' => ['A' => '133', 'B' => '135', 'C' => '137', 'D' => '139'], 'correct' => 'B'],
            ['problem' => '104 - 68 = ?', 'options' => ['A' => '34', 'B' => '36', 'C' => '38', 'D' => '40'], 'correct' => 'B'],
            ['problem' => '23 × 4 = ?', 'options' => ['A' => '88', 'B' => '92', 'C' => '96', 'D' => '100'], 'correct' => 'B'],
            ['problem' => '210 ÷ 14 = ?', 'options' => ['A' => '13', 'B' => '15', 'C' => '17', 'D' => '19'], 'correct' => 'B'],
            ['problem' => '79 + 66 = ?', 'options' => ['A' => '143', 'B' => '145', 'C' => '147', 'D' => '149'], 'correct' => 'B'],
            ['problem' => '112 - 75 = ?', 'options' => ['A' => '35', 'B' => '37', 'C' => '39', 'D' => '41'], 'correct' => 'B'],
            ['problem' => '26 × 3 = ?', 'options' => ['A' => '74', 'B' => '78', 'C' => '82', 'D' => '86'], 'correct' => 'B'],
            ['problem' => '224 ÷ 16 = ?', 'options' => ['A' => '12', 'B' => '14', 'C' => '16', 'D' => '18'], 'correct' => 'B'],
            ['problem' => '95 + 38 = ?', 'options' => ['A' => '131', 'B' => '133', 'C' => '135', 'D' => '137'], 'correct' => 'B'],
            ['problem' => '127 - 89 = ?', 'options' => ['A' => '36', 'B' => '38', 'C' => '40', 'D' => '42'], 'correct' => 'B'],
        ];

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
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
     * G: 探索（Symbol Search）- 36問のプール全てを保存
     */
    private function seedSymbolSearch(): void
    {
        $symbols = ['○', '△', '□', '◇', '☆', '◎', '▽', '■', '◆', '★'];
        $questionPool = [];

        for ($i = 0; $i < 36; $i++) {
            $targetSymbol = $symbols[$i % count($symbols)];
            // ランダムに右側のグループを生成（5つの記号）
            $rightGroup = [];
            $hasTarget = ($i % 2 === 0); // 半分は含まれる、半分は含まれない

            if ($hasTarget) {
                // ターゲットを含める
                $rightGroup[] = $targetSymbol;
                // 残り4つをランダムに追加
                $otherSymbols = array_values(array_diff($symbols, [$targetSymbol]));
                shuffle($otherSymbols);
                $rightGroup = array_merge($rightGroup, array_slice($otherSymbols, 0, 4));
                shuffle($rightGroup);
            } else {
                // ターゲットを含めない
                $otherSymbols = array_values(array_diff($symbols, [$targetSymbol]));
                shuffle($otherSymbols);
                $rightGroup = array_slice($otherSymbols, 0, 5);
            }

            $questionPool[] = [
                'target' => $targetSymbol,
                'rightGroup' => implode(' ', $rightGroup),
                'correct' => $hasTarget ? '○' : '×',
                'description' => "左側の記号【{$targetSymbol}】が、右側のグループ【" . implode(' ', $rightGroup) . "】に含まれていますか？",
            ];
        }

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'G',
                'sequence_number' => $index + 1,
                'content' => $question['description'],
                'question_type' => 'time_based',
                'correct_answer' => $question['correct'],
                'options' => ['○' => 'はい', '×' => 'いいえ'],
                'max_points' => 1,
                'hint' => null,
            ]);
        }
    }

    /**
     * H: 符号化（Coding）- 90問のプール全てを保存
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

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
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
}

