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
        // 外部キー制約を考慮して、関連データを先に削除
        \DB::table('answers')->delete();
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
            ['content' => 'リンゴとバナナの共通点は何ですか？', 'answer' => '果物（食べ物）'],
            ['content' => '犬と猫の共通点は何ですか？', 'answer' => '哺乳類（ペット・動物）'],
            ['content' => '椅子とテーブルの共通点は何ですか？', 'answer' => '家具'],
            ['content' => '鉛筆とペンの共通点は何ですか？', 'answer' => '筆記具'],
            ['content' => '太陽と月の共通点は何ですか？', 'answer' => '天体（空にあるもの）'],
            ['content' => '車と自転車の共通点は何ですか？', 'answer' => '乗り物（移動手段）'],
            ['content' => '本と新聞の共通点は何ですか？', 'answer' => '印刷物（情報媒体）'],
            ['content' => '冬と夏の共通点は何ですか？', 'answer' => '季節'],
            ['content' => '山と谷の共通点は何ですか？', 'answer' => '地形'],
            ['content' => '赤と青の共通点は何ですか？', 'answer' => '色'],
            ['content' => '笑いと涙の共通点は何ですか？', 'answer' => '感情表現'],
            ['content' => '音楽と絵画の共通点は何ですか？', 'answer' => '芸術'],
            ['content' => '朝と夜の共通点は何ですか？', 'answer' => '一日の時間帯'],
            ['content' => '医師と看護師の共通点は何ですか？', 'answer' => '医療職'],
            ['content' => '電話とメールの共通点は何ですか？', 'answer' => '通信手段'],
            ['content' => '川と海の共通点は何ですか？', 'answer' => '水域（水のある場所）'],
            ['content' => '鳥と飛行機の共通点は何ですか？', 'answer' => '空を飛ぶもの'],
            ['content' => '靴と帽子の共通点は何ですか？', 'answer' => '身につけるもの（衣類・装飾品）'],
            ['content' => '春と秋の共通点は何ですか？', 'answer' => '季節（穏やかな気候の季節）'],
            ['content' => '鏡とカメラの共通点は何ですか？', 'answer' => '映すもの（像を作るもの）'],
            ['content' => '時計とカレンダーの共通点は何ですか？', 'answer' => '時間を示すもの'],
            ['content' => '橋とトンネルの共通点は何ですか？', 'answer' => '交通インフラ（通路）'],
            ['content' => '雨と雪の共通点は何ですか？', 'answer' => '降水現象（天気）'],
            ['content' => 'ドアと窓の共通点は何ですか？', 'answer' => '建物の開口部'],
            ['content' => '鍵と暗証番号の共通点は何ですか？', 'answer' => 'セキュリティ手段'],
            ['content' => '砂糖と塩の共通点は何ですか？', 'answer' => '調味料'],
            ['content' => '教師と生徒の共通点は何ですか？', 'answer' => '教育に関わる人'],
            ['content' => '誕生日と結婚記念日の共通点は何ですか？', 'answer' => '記念日'],
            ['content' => '野球とサッカーの共通点は何ですか？', 'answer' => 'スポーツ（球技）'],
            ['content' => '詩と小説の共通点は何ですか？', 'answer' => '文学作品'],
            // 新規追加30問
            ['content' => '火と水の共通点は何ですか？', 'answer' => '自然の要素'],
            ['content' => '戦争と平和の共通点は何ですか？', 'answer' => '社会の状態'],
            ['content' => '希望と絶望の共通点は何ですか？', 'answer' => '感情・心の状態'],
            ['content' => '始まりと終わりの共通点は何ですか？', 'answer' => '時間の概念（区切り）'],
            ['content' => '愛と憎しみの共通点は何ですか？', 'answer' => '強い感情'],
            ['content' => '成功と失敗の共通点は何ですか？', 'answer' => '結果・成果'],
            ['content' => '真実と嘘の共通点は何ですか？', 'answer' => '情報・言葉'],
            ['content' => '過去と未来の共通点は何ですか？', 'answer' => '時間軸（現在ではない時間）'],
            ['content' => '原因と結果の共通点は何ですか？', 'answer' => '因果関係の要素'],
            ['content' => '質問と回答の共通点は何ですか？', 'answer' => 'コミュニケーション（会話の要素）'],
            ['content' => 'テレビとラジオの共通点は何ですか？', 'answer' => 'メディア（放送媒体）'],
            ['content' => 'パンと米の共通点は何ですか？', 'answer' => '主食（炭水化物）'],
            ['content' => '蝶と花の共通点は何ですか？', 'answer' => '自然の美しいもの'],
            ['content' => '勝利と敗北の共通点は何ですか？', 'answer' => '競争の結果'],
            ['content' => '光と影の共通点は何ですか？', 'answer' => '光に関する現象'],
            ['content' => '記憶と忘却の共通点は何ですか？', 'answer' => '脳の働き（記憶機能）'],
            ['content' => '病気と健康の共通点は何ですか？', 'answer' => '身体の状態'],
            ['content' => '善と悪の共通点は何ですか？', 'answer' => '道徳的概念（価値判断）'],
            ['content' => '富と貧困の共通点は何ですか？', 'answer' => '経済状態'],
            ['content' => '理想と現実の共通点は何ですか？', 'answer' => '認識・物事の捉え方'],
            ['content' => '自由と束縛の共通点は何ですか？', 'answer' => '状態の概念'],
            ['content' => '沈黙と騒音の共通点は何ですか？', 'answer' => '音の状態'],
            ['content' => '秩序と混沌の共通点は何ですか？', 'answer' => 'システム・状態の概念'],
            ['content' => '知識と無知の共通点は何ですか？', 'answer' => '認識の状態'],
            ['content' => '伝統と革新の共通点は何ですか？', 'answer' => '文化の要素'],
            ['content' => '個人と集団の共通点は何ですか？', 'answer' => '社会の単位'],
            ['content' => '科学と芸術の共通点は何ですか？', 'answer' => '人間の営み（創造活動）'],
            ['content' => '肉体と精神の共通点は何ですか？', 'answer' => '人間の構成要素'],
            ['content' => '言葉と沈黙の共通点は何ですか？', 'answer' => 'コミュニケーション手段'],
            ['content' => '夢と現実の共通点は何ですか？', 'answer' => '経験'],
        ];

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'A',
                'sequence_number' => $index + 1,
                'content' => $question['content'],
                'question_type' => 'free_text',
                'correct_answer' => $question['answer'],
                'options' => null,
                'max_points' => 2,
                'hint' => null,
            ]);
        }
    }

    /**
     * B: 語彙（Vocabulary）- 60問のプール全てを保存
     */
    private function seedVocabulary(): void
    {
        $questionPool = [
            ['word' => '勇気', 'answer' => '恐怖に打ち勝つ心'],
            ['word' => '寛容', 'answer' => '他人を受け入れる心'],
            ['word' => '誠実', 'answer' => '嘘をつかず正直であること'],
            ['word' => '慎重', 'answer' => '注意深く行動すること'],
            ['word' => '楽観的', 'answer' => '物事を明るく考えること'],
            ['word' => '協力', 'answer' => '力を合わせること'],
            ['word' => '創造', 'answer' => '新しいものを作り出すこと'],
            ['word' => '独立', 'answer' => '自分で決めて行動すること'],
            ['word' => '調和', 'answer' => 'バランスが取れている状態'],
            ['word' => '尊重', 'answer' => '相手を大切にすること'],
            ['word' => '効率', 'answer' => '無駄なく物事を進めること'],
            ['word' => '柔軟', 'answer' => '状況に応じて対応できること'],
            ['word' => '継続', 'answer' => '途中でやめずに続けること'],
            ['word' => '革新', 'answer' => '新しいやり方を取り入れること'],
            ['word' => '公平', 'answer' => 'えこひいきせず平等に扱うこと'],
            ['word' => '謙虚', 'answer' => '控えめで偉ぶらないこと'],
            ['word' => '献身', 'answer' => '自分を犠牲にして尽くすこと'],
            ['word' => '寛大', 'answer' => '心が広く許すこと'],
            ['word' => '洞察', 'answer' => '物事の本質を見抜くこと'],
            ['word' => '忍耐', 'answer' => '苦しみに耐えること'],
            ['word' => '積極的', 'answer' => '自ら進んで行動すること'],
            ['word' => '慈悲', 'answer' => '思いやりの心'],
            ['word' => '正義', 'answer' => '正しいことを貫くこと'],
            ['word' => '責任', 'answer' => '自分の役割を果たすこと'],
            ['word' => '信頼', 'answer' => '相手を信じること'],
            ['word' => '感謝', 'answer' => 'ありがたく思う気持ち'],
            ['word' => '誇り', 'answer' => '自分を価値あると思う気持ち'],
            ['word' => '思慮深い', 'answer' => 'よく考えて行動すること'],
            ['word' => '勤勉', 'answer' => 'まじめに働くこと'],
            ['word' => '友情', 'answer' => '友達との絆'],
            // 新規追加30問
            ['word' => '誠意', 'answer' => '真心を持って接すること'],
            ['word' => '率直', 'answer' => 'ありのままを伝えること'],
            ['word' => '寛恕', 'answer' => '過ちを許すこと'],
            ['word' => '清廉', 'answer' => '心が清く正しいこと'],
            ['word' => '高潔', 'answer' => '品格が高く立派なこと'],
            ['word' => '真摯', 'answer' => 'まじめで熱心なこと'],
            ['word' => '温厚', 'answer' => '穏やかで優しいこと'],
            ['word' => '冷静', 'answer' => '落ち着いて判断すること'],
            ['word' => '明朗', 'answer' => '明るく朗らかなこと'],
            ['word' => '実直', 'answer' => 'まじめで正直なこと'],
            ['word' => '純粋', 'answer' => '混じり気がないこと'],
            ['word' => '賢明', 'answer' => '知恵があり判断力に優れること'],
            ['word' => '博識', 'answer' => '広い知識を持つこと'],
            ['word' => '機知', 'answer' => '状況に応じた素早い判断力'],
            ['word' => '洗練', 'answer' => '磨かれて上品になること'],
            ['word' => '品格', 'answer' => '人としての気品'],
            ['word' => '威厳', 'answer' => '堂々として尊敬される様子'],
            ['word' => '風格', 'answer' => '立派で貫禄があること'],
            ['word' => '節度', 'answer' => '度を越さない適度な行動'],
            ['word' => '配慮', 'answer' => '相手の立場を考えること'],
            ['word' => '気配り', 'answer' => '細かく注意を向けること'],
            ['word' => '思いやり', 'answer' => '他人の気持ちを察する心'],
            ['word' => '共感', 'answer' => '相手の感情を理解すること'],
            ['word' => '寄り添う', 'answer' => '側で支えること'],
            ['word' => '奉仕', 'answer' => '人のために尽くすこと'],
            ['word' => '貢献', 'answer' => '役に立つこと'],
            ['word' => '犠牲', 'answer' => '自分を投げ出すこと'],
            ['word' => '献身的', 'answer' => '身を捧げて尽くすこと'],
            ['word' => '無私', 'answer' => '私心がないこと'],
            ['word' => '公正', 'answer' => '偏りなく正しいこと'],
        ];

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'B',
                'sequence_number' => $index + 1,
                'content' => "「{$question['word']}」という言葉の意味を説明してください。",
                'question_type' => 'free_text',
                'correct_answer' => $question['answer'],
                'options' => null,
                'max_points' => 2,
                'hint' => null,
            ]);
        }
    }

    /**
     * C: 規則発見（Pattern Recognition）- 50問のプール全てを保存
     * 難易度9.5：より複雑な数列、正解をランダムに配置
     */
    private function seedPatternRecognition(): void
    {
        $questionPool = [
            // 基本的な数列（難易度アップ版）
            ['pattern' => '2, 4, 6, 8, ?', 'options' => ['A' => '10', 'B' => '9', 'C' => '11', 'D' => '12'], 'correct' => 'A'],
            ['pattern' => '1, 3, 5, 7, ?', 'options' => ['A' => '8', 'B' => '10', 'C' => '9', 'D' => '11'], 'correct' => 'C'],
            ['pattern' => '10, 20, 30, 40, ?', 'options' => ['A' => '45', 'B' => '55', 'C' => '60', 'D' => '50'], 'correct' => 'D'],
            ['pattern' => '1, 4, 9, 16, ?', 'options' => ['A' => '25', 'B' => '20', 'C' => '30', 'D' => '36'], 'correct' => 'A'],
            ['pattern' => '5, 10, 15, 20, ?', 'options' => ['A' => '22', 'B' => '27', 'C' => '25', 'D' => '30'], 'correct' => 'C'],

            // 複雑な規則（難易度9.5）
            ['pattern' => '2, 6, 12, 20, 30, ?', 'options' => ['A' => '40', 'B' => '38', 'C' => '42', 'D' => '36'], 'correct' => 'C'], // n*(n+1)
            ['pattern' => '1, 1, 2, 3, 5, 8, 13, ?', 'options' => ['A' => '18', 'B' => '21', 'C' => '20', 'D' => '19'], 'correct' => 'B'], // フィボナッチ
            ['pattern' => '3, 6, 12, 24, ?', 'options' => ['A' => '36', 'B' => '60', 'C' => '48', 'D' => '72'], 'correct' => 'C'],
            ['pattern' => '100, 50, 25, 12.5, ?', 'options' => ['A' => '6.25', 'B' => '5', 'C' => '7.5', 'D' => '10'], 'correct' => 'A'],
            ['pattern' => '1, 4, 10, 22, 46, ?', 'options' => ['A' => '92', 'B' => '88', 'C' => '94', 'D' => '90'], 'correct' => 'C'], // *2+2のパターン

            // 素数列
            ['pattern' => '2, 3, 5, 7, 11, 13, ?', 'options' => ['A' => '15', 'B' => '16', 'C' => '17', 'D' => '19'], 'correct' => 'C'],
            ['pattern' => '3, 5, 7, 11, 13, 17, ?', 'options' => ['A' => '21', 'B' => '19', 'C' => '23', 'D' => '20'], 'correct' => 'B'],

            // 2乗・3乗数列
            ['pattern' => '1, 8, 27, 64, 125, ?', 'options' => ['A' => '196', 'B' => '216', 'C' => '225', 'D' => '256'], 'correct' => 'B'], // n^3
            ['pattern' => '1, 4, 9, 16, 25, 36, ?', 'options' => ['A' => '42', 'B' => '48', 'C' => '49', 'D' => '64'], 'correct' => 'C'],
            ['pattern' => '4, 16, 36, 64, 100, ?', 'options' => ['A' => '144', 'B' => '121', 'C' => '136', 'D' => '128'], 'correct' => 'A'], // (2n)^2

            // 複雑な加減算
            ['pattern' => '3, 7, 13, 21, 31, ?', 'options' => ['A' => '41', 'B' => '43', 'C' => '45', 'D' => '39'], 'correct' => 'B'], // +4,+6,+8,+10,+12
            ['pattern' => '2, 5, 11, 23, 47, ?', 'options' => ['A' => '94', 'B' => '91', 'C' => '95', 'D' => '93'], 'correct' => 'C'], // *2+1
            ['pattern' => '5, 8, 14, 26, 50, ?', 'options' => ['A' => '102', 'B' => '98', 'C' => '94', 'D' => '100'], 'correct' => 'B'], // *2-2
            ['pattern' => '1, 3, 7, 15, 31, ?', 'options' => ['A' => '61', 'B' => '62', 'C' => '63', 'D' => '64'], 'correct' => 'C'], // 2^n-1

            // 交互パターン
            ['pattern' => '2, 5, 3, 6, 4, 7, 5, ?', 'options' => ['A' => '6', 'B' => '8', 'C' => '9', 'D' => '7'], 'correct' => 'B'], // +3/-2交互
            ['pattern' => '10, 5, 20, 10, 30, 15, ?', 'options' => ['A' => '40', 'B' => '35', 'C' => '45', 'D' => '50'], 'correct' => 'A'], // 2系列
            ['pattern' => '1, 4, 2, 5, 3, 6, 4, ?', 'options' => ['A' => '5', 'B' => '8', 'C' => '7', 'D' => '6'], 'correct' => 'C'],

            // 階乗関連
            ['pattern' => '1, 2, 6, 24, 120, ?', 'options' => ['A' => '720', 'B' => '600', 'C' => '480', 'D' => '240'], 'correct' => 'A'], // n!
            ['pattern' => '2, 6, 24, 120, ?', 'options' => ['A' => '480', 'B' => '600', 'C' => '720', 'D' => '240'], 'correct' => 'C'],

            // 複雑な減算
            ['pattern' => '100, 91, 83, 76, 70, ?', 'options' => ['A' => '63', 'B' => '64', 'C' => '65', 'D' => '66'], 'correct' => 'C'], // -9,-8,-7,-6,-5
            ['pattern' => '81, 72, 64, 57, 51, ?', 'options' => ['A' => '46', 'B' => '45', 'C' => '44', 'D' => '47'], 'correct' => 'A'], // -9,-8,-7,-6,-5

            // べき乗数列
            ['pattern' => '2, 4, 8, 16, 32, 64, ?', 'options' => ['A' => '96', 'B' => '128', 'C' => '112', 'D' => '120'], 'correct' => 'B'],
            ['pattern' => '3, 9, 27, 81, 243, ?', 'options' => ['A' => '486', 'B' => '648', 'C' => '729', 'D' => '810'], 'correct' => 'C'], // 3^n
            ['pattern' => '5, 25, 125, 625, ?', 'options' => ['A' => '2500', 'B' => '3750', 'C' => '3125', 'D' => '5000'], 'correct' => 'C'],

            // 複雑な複合パターン
            ['pattern' => '1, 2, 4, 7, 11, 16, 22, ?', 'options' => ['A' => '28', 'B' => '29', 'C' => '30', 'D' => '27'], 'correct' => 'B'], // +1,+2,+3,+4,+5,+6,+7
            ['pattern' => '2, 3, 5, 9, 17, 33, ?', 'options' => ['A' => '65', 'B' => '64', 'C' => '66', 'D' => '63'], 'correct' => 'A'], // *2-1
            ['pattern' => '1, 3, 6, 10, 15, 21, ?', 'options' => ['A' => '27', 'B' => '28', 'C' => '26', 'D' => '29'], 'correct' => 'B'], // 三角数
            ['pattern' => '0, 1, 3, 6, 10, 15, ?', 'options' => ['A' => '20', 'B' => '21', 'C' => '22', 'D' => '19'], 'correct' => 'B'],

            // 平方根・累乗パターン
            ['pattern' => '1, 3, 9, 81, 6561, ?', 'options' => ['A' => '43046721', 'B' => '19683', 'C' => '531441', 'D' => '10000'], 'correct' => 'A'], // a(n+1)=a(n)^2
            ['pattern' => '256, 128, 64, 32, 16, ?', 'options' => ['A' => '4', 'B' => '8', 'C' => '12', 'D' => '10'], 'correct' => 'B'],
            ['pattern' => '1024, 512, 256, 128, ?', 'options' => ['A' => '32', 'B' => '48', 'C' => '64', 'D' => '96'], 'correct' => 'C'],

            // 複雑な和と差
            ['pattern' => '11, 13, 17, 19, 23, 29, ?', 'options' => ['A' => '30', 'B' => '31', 'C' => '33', 'D' => '35'], 'correct' => 'B'], // 素数
            ['pattern' => '7, 11, 15, 19, 23, 27, ?', 'options' => ['A' => '30', 'B' => '29', 'C' => '31', 'D' => '33'], 'correct' => 'C'], // +4
            ['pattern' => '13, 21, 34, 55, 89, ?', 'options' => ['A' => '144', 'B' => '120', 'C' => '133', 'D' => '121'], 'correct' => 'A'], // フィボナッチ

            // 高難度パターン
            ['pattern' => '2, 6, 14, 30, 62, ?', 'options' => ['A' => '124', 'B' => '126', 'C' => '128', 'D' => '130'], 'correct' => 'B'], // 2^n-2
            ['pattern' => '1, 5, 14, 30, 55, ?', 'options' => ['A' => '89', 'B' => '90', 'C' => '91', 'D' => '85'], 'correct' => 'C'], // n(n+1)(n+2)/6
            ['pattern' => '3, 8, 18, 38, 78, ?', 'options' => ['A' => '158', 'B' => '156', 'C' => '160', 'D' => '154'], 'correct' => 'A'], // *2+2
            ['pattern' => '5, 12, 21, 32, 45, ?', 'options' => ['A' => '58', 'B' => '60', 'C' => '62', 'D' => '56'], 'correct' => 'B'], // n^2+4
            ['pattern' => '4, 12, 24, 40, 60, ?', 'options' => ['A' => '80', 'B' => '84', 'C' => '88', 'D' => '82'], 'correct' => 'B'], // 4n(n+1)/2
            ['pattern' => '9, 16, 25, 36, 49, ?', 'options' => ['A' => '60', 'B' => '64', 'C' => '72', 'D' => '81'], 'correct' => 'B'], // (n+2)^2
            ['pattern' => '6, 15, 28, 45, 66, ?', 'options' => ['A' => '89', 'B' => '90', 'C' => '91', 'D' => '88'], 'correct' => 'C'], // 3n^2+3
            ['pattern' => '10, 22, 46, 94, 190, ?', 'options' => ['A' => '382', 'B' => '380', 'C' => '378', 'D' => '384'], 'correct' => 'A'], // *2+2
            ['pattern' => '7, 17, 37, 77, 157, ?', 'options' => ['A' => '315', 'B' => '317', 'C' => '313', 'D' => '320'], 'correct' => 'B'], // *2+3
            ['pattern' => '12, 35, 81, 173, 357, ?', 'options' => ['A' => '725', 'B' => '715', 'C' => '720', 'D' => '730'], 'correct' => 'A'], // *2+11,*2+11,...
        ];

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'C',
                'sequence_number' => $index + 1,
                'content' => "次の数列の規則性を見つけて、?に入る数を選んでください：\n\n{$question['pattern']}",
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

        // パターン1: 複合規則 - 記号と数が両方変化 (10問)
        $complexPatterns = [
            ['symbols' => ['●', '■', '▲'], 'counts' => [1, 2, 3]],
            ['symbols' => ['◆', '★', '◎'], 'counts' => [1, 2, 3]],
            ['symbols' => ['▽', '◇', '○'], 'counts' => [2, 3, 4]],
            ['symbols' => ['△', '□', '■'], 'counts' => [1, 3, 2]],
            ['symbols' => ['☆', '●', '▲'], 'counts' => [3, 2, 1]],
        ];

        for ($i = 0; $i < 10; $i++) {
            $pattern = $complexPatterns[$i % count($complexPatterns)];
            $s1 = str_repeat($pattern['symbols'][0], $pattern['counts'][0]);
            $s2 = str_repeat($pattern['symbols'][1], $pattern['counts'][1]);
            $s3 = str_repeat($pattern['symbols'][2], $pattern['counts'][2]);

            $matrixPattern = "
【3×3マトリクス】
1行目: {$s1} | {$s2} | {$s3}
2行目: {$s2} | {$s3} | {$s1}
3行目: {$s3} | {$s1} | ?
            ";
            $questionPool[] = [
                'description' => "3×3のマトリクスで、各行が循環パターンで配置されています。規則を見つけて空欄を埋めてください。",
                'pattern' => $matrixPattern,
                'options' => [
                    'A' => $s1,
                    'B' => $s2,
                    'C' => $s3,
                    'D' => str_repeat($pattern['symbols'][0], 4)
                ],
                'correct' => 'B',
            ];
        }

        // パターン2: 位置と形状の複合規則 (10問)
        for ($i = 0; $i < 10; $i++) {
            $shapes = ['●', '■', '▲', '◆', '★'];
            $positions = ['左', '中央', '右'];

            $matrixPattern = "
【3×3マトリクス - 位置と形状】
1行目: 左に● | 中央に■ | 右に▲
2行目: 中央に● | 右に■ | 左に▲
3行目: 右に● | 左に■ | ?
            ";
            $questionPool[] = [
                'description' => "3×3のマトリクスで、図形の位置が規則的に移動しています。?に入る内容を選んでください。",
                'pattern' => $matrixPattern,
                'options' => [
                    'A' => '左に▲',
                    'B' => '中央に▲',
                    'C' => '右に▲',
                    'D' => '▲がない'
                ],
                'correct' => 'B',
            ];
        }

        // パターン3: 加算・減算パターン (10問)
        for ($i = 0; $i < 10; $i++) {
            $symbol = ['●', '■', '▲', '◆', '★'][$i % 5];

            $matrixPattern = "
【3×3マトリクス - 加算規則】
1行目: {$symbol} | {$symbol}{$symbol} | {$symbol}{$symbol}{$symbol}
2行目: {$symbol}{$symbol} | {$symbol}{$symbol}{$symbol} | {$symbol}{$symbol}{$symbol}{$symbol}
3行目: {$symbol}{$symbol}{$symbol} | {$symbol}{$symbol}{$symbol}{$symbol} | ?
            ";
            $questionPool[] = [
                'description' => "3×3のマトリクスで、行ごとに記号の数が+1ずつ増え、列ごとにも+1ずつ増えています。",
                'pattern' => $matrixPattern,
                'options' => [
                    'A' => str_repeat($symbol, 3),
                    'B' => str_repeat($symbol, 4),
                    'C' => str_repeat($symbol, 5),
                    'D' => str_repeat($symbol, 6)
                ],
                'correct' => 'C',
            ];
        }

        // パターン4: 対称性と回転 (8問)
        for ($i = 0; $i < 8; $i++) {
            $matrixPattern = "
【3×3マトリクス - 対称性】
1行目: ● | ■ | ●
2行目: ■ | ▲ | ■
3行目: ● | ■ | ?
            ";
            $questionPool[] = [
                'description' => "3×3のマトリクスが中心点に対して点対称になっています。?に入る図形は？",
                'pattern' => $matrixPattern,
                'options' => [
                    'A' => '●',
                    'B' => '■',
                    'C' => '▲',
                    'D' => '◆'
                ],
                'correct' => 'A',
            ];
        }

        // パターン5: 複数図形の組み合わせ (7問)
        for ($i = 0; $i < 7; $i++) {
            $matrixPattern = "
【3×3マトリクス - 組み合わせ】
1行目: ●■ | ●▲ | ●◆
2行目: ■● | ■▲ | ■◆
3行目: ▲● | ▲■ | ?
            ";
            $questionPool[] = [
                'description' => "3×3のマトリクスで、各セルに2つの図形があり、規則的に配置されています。",
                'pattern' => $matrixPattern,
                'options' => [
                    'A' => '▲●',
                    'B' => '▲◆',
                    'C' => '◆▲',
                    'D' => '●▲'
                ],
                'correct' => 'B',
            ];
        }

        // パターン6: 欠損パターン (5問)
        for ($i = 0; $i < 5; $i++) {
            $matrixPattern = "
【3×3マトリクス - 欠損規則】
1行目: ●●● | ●●○ | ●○○
2行目: ■■■ | ■■○ | ■○○
3行目: ▲▲▲ | ▲▲○ | ?
            ";
            $questionPool[] = [
                'description' => "3×3のマトリクスで、右に行くほど図形が○（空白）に置き換わっています。",
                'pattern' => $matrixPattern,
                'options' => [
                    'A' => '▲▲▲',
                    'B' => '▲▲○',
                    'C' => '▲○○',
                    'D' => '○○○'
                ],
                'correct' => 'C',
            ];
        }

        // 全ての問題をデータベースに保存
        foreach ($questionPool as $index => $question) {
            QuestionModel::create([
                'id' => (string) Str::uuid(),
                'subtest_type' => 'D',
                'sequence_number' => $index + 1,
                'content' => "{$question['description']}\n\n{$question['pattern']}",
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
     * 難易度9.5：より複雑な計算、正解をランダムに配置
     */
    private function seedArithmetic(): void
    {
        $questionPool = [
            // 加減乗除の基本（難易度アップ）
            ['problem' => '127 + 86 = ?', 'options' => ['A' => '213', 'B' => '211', 'C' => '215', 'D' => '209'], 'correct' => 'A'],
            ['problem' => '348 - 179 = ?', 'options' => ['A' => '167', 'B' => '171', 'C' => '169', 'D' => '165'], 'correct' => 'C'],
            ['problem' => '23 × 17 = ?', 'options' => ['A' => '389', 'B' => '391', 'C' => '387', 'D' => '393'], 'correct' => 'B'],
            ['problem' => '288 ÷ 16 = ?', 'options' => ['A' => '16', 'B' => '17', 'C' => '18', 'D' => '19'], 'correct' => 'C'],

            // 3つの数の計算
            ['problem' => '45 + 67 - 28 = ?', 'options' => ['A' => '82', 'B' => '84', 'C' => '86', 'D' => '88'], 'correct' => 'B'],
            ['problem' => '120 ÷ 4 + 15 = ?', 'options' => ['A' => '43', 'B' => '45', 'C' => '47', 'D' => '49'], 'correct' => 'B'],
            ['problem' => '8 × 9 + 13 = ?', 'options' => ['A' => '83', 'B' => '85', 'C' => '87', 'D' => '89'], 'correct' => 'B'],
            ['problem' => '95 - 37 + 24 = ?', 'options' => ['A' => '80', 'B' => '82', 'C' => '84', 'D' => '86'], 'correct' => 'B'],

            // 複雑な四則演算（かっこあり）
            ['problem' => '(24 + 36) ÷ 12 = ?', 'options' => ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'], 'correct' => 'C'],
            ['problem' => '15 × (8 - 3) = ?', 'options' => ['A' => '75', 'B' => '70', 'C' => '65', 'D' => '80'], 'correct' => 'A'],
            ['problem' => '(48 - 12) ÷ 9 = ?', 'options' => ['A' => '3', 'B' => '4', 'C' => '5', 'D' => '6'], 'correct' => 'B'],
            ['problem' => '7 × (15 + 9) = ?', 'options' => ['A' => '164', 'B' => '168', 'C' => '172', 'D' => '176'], 'correct' => 'B'],

            // 2桁の乗算
            ['problem' => '35 × 12 = ?', 'options' => ['A' => '420', 'B' => '410', 'C' => '430', 'D' => '400'], 'correct' => 'A'],
            ['problem' => '28 × 19 = ?', 'options' => ['A' => '522', 'B' => '532', 'C' => '542', 'D' => '552'], 'correct' => 'B'],
            ['problem' => '46 × 15 = ?', 'options' => ['A' => '670', 'B' => '680', 'C' => '690', 'D' => '700'], 'correct' => 'C'],

            // 3桁の加減算
            ['problem' => '567 + 289 = ?', 'options' => ['A' => '846', 'B' => '856', 'C' => '866', 'D' => '876'], 'correct' => 'B'],
            ['problem' => '823 - 456 = ?', 'options' => ['A' => '357', 'B' => '367', 'C' => '377', 'D' => '387'], 'correct' => 'B'],
            ['problem' => '734 + 198 = ?', 'options' => ['A' => '922', 'B' => '932', 'C' => '942', 'D' => '952'], 'correct' => 'B'],

            // 大きな数の除算
            ['problem' => '456 ÷ 24 = ?', 'options' => ['A' => '17', 'B' => '18', 'C' => '19', 'D' => '20'], 'correct' => 'C'],
            ['problem' => '675 ÷ 25 = ?', 'options' => ['A' => '25', 'B' => '26', 'C' => '27', 'D' => '28'], 'correct' => 'C'],
            ['problem' => '888 ÷ 37 = ?', 'options' => ['A' => '22', 'B' => '23', 'C' => '24', 'D' => '25'], 'correct' => 'C'],

            // 複合計算
            ['problem' => '18 × 7 - 35 = ?', 'options' => ['A' => '89', 'B' => '91', 'C' => '93', 'D' => '95'], 'correct' => 'B'],
            ['problem' => '144 ÷ 12 + 27 = ?', 'options' => ['A' => '37', 'B' => '38', 'C' => '39', 'D' => '40'], 'correct' => 'C'],
            ['problem' => '65 - 28 + 49 = ?', 'options' => ['A' => '84', 'B' => '85', 'C' => '86', 'D' => '87'], 'correct' => 'C'],
            ['problem' => '9 × 13 - 42 = ?', 'options' => ['A' => '73', 'B' => '75', 'C' => '77', 'D' => '79'], 'correct' => 'B'],

            // 難易度高
            ['problem' => '256 ÷ 16 × 3 = ?', 'options' => ['A' => '46', 'B' => '48', 'C' => '50', 'D' => '52'], 'correct' => 'B'],
            ['problem' => '(85 + 35) ÷ 8 = ?', 'options' => ['A' => '13', 'B' => '14', 'C' => '15', 'D' => '16'], 'correct' => 'C'],
            ['problem' => '17 × 11 - 56 = ?', 'options' => ['A' => '129', 'B' => '131', 'C' => '133', 'D' => '135'], 'correct' => 'B'],
            ['problem' => '324 ÷ 18 + 47 = ?', 'options' => ['A' => '63', 'B' => '64', 'C' => '65', 'D' => '66'], 'correct' => 'C'],

            // 更に難易度アップ
            ['problem' => '52 × 14 + 38 = ?', 'options' => ['A' => '764', 'B' => '766', 'C' => '768', 'D' => '770'], 'correct' => 'B'],
            ['problem' => '945 ÷ 21 - 12 = ?', 'options' => ['A' => '31', 'B' => '32', 'C' => '33', 'D' => '34'], 'correct' => 'C'],
            ['problem' => '(96 - 42) × 5 = ?', 'options' => ['A' => '268', 'B' => '270', 'C' => '272', 'D' => '274'], 'correct' => 'B'],
            ['problem' => '13 × 8 + 76 - 29 = ?', 'options' => ['A' => '149', 'B' => '151', 'C' => '153', 'D' => '155'], 'correct' => 'B'],

            // 最高難度
            ['problem' => '784 ÷ 28 × 9 = ?', 'options' => ['A' => '250', 'B' => '252', 'C' => '254', 'D' => '256'], 'correct' => 'B'],
            ['problem' => '(156 + 84) ÷ 15 = ?', 'options' => ['A' => '14', 'B' => '15', 'C' => '16', 'D' => '17'], 'correct' => 'C'],
            ['problem' => '29 × 16 - 127 = ?', 'options' => ['A' => '335', 'B' => '337', 'C' => '339', 'D' => '341'], 'correct' => 'B'],
            ['problem' => '567 ÷ 21 + 89 = ?', 'options' => ['A' => '114', 'B' => '115', 'C' => '116', 'D' => '117'], 'correct' => 'C'],
            ['problem' => '38 × 22 - 235 = ?', 'options' => ['A' => '599', 'B' => '601', 'C' => '603', 'D' => '605'], 'correct' => 'B'],

            // 追加の高難度問題
            ['problem' => '(234 - 87) × 4 = ?', 'options' => ['A' => '586', 'B' => '588', 'C' => '590', 'D' => '592'], 'correct' => 'B'],
            ['problem' => '1024 ÷ 32 + 56 = ?', 'options' => ['A' => '86', 'B' => '87', 'C' => '88', 'D' => '89'], 'correct' => 'C'],
            ['problem' => '43 × 19 + 127 = ?', 'options' => ['A' => '942', 'B' => '944', 'C' => '946', 'D' => '948'], 'correct' => 'B'],
            ['problem' => '729 ÷ 27 - 18 = ?', 'options' => ['A' => '7', 'B' => '8', 'C' => '9', 'D' => '10'], 'correct' => 'C'],
            ['problem' => '(145 + 78) ÷ 13 = ?', 'options' => ['A' => '15', 'B' => '16', 'C' => '17', 'D' => '18'], 'correct' => 'C'],
            ['problem' => '56 × 13 - 289 = ?', 'options' => ['A' => '437', 'B' => '439', 'C' => '441', 'D' => '443'], 'correct' => 'B'],
            ['problem' => '896 ÷ 28 + 73 = ?', 'options' => ['A' => '103', 'B' => '104', 'C' => '105', 'D' => '106'], 'correct' => 'C'],
            ['problem' => '67 × 14 + 156 = ?', 'options' => ['A' => '1092', 'B' => '1094', 'C' => '1096', 'D' => '1098'], 'correct' => 'B'],
            ['problem' => '512 ÷ 16 - 19 = ?', 'options' => ['A' => '11', 'B' => '12', 'C' => '13', 'D' => '14'], 'correct' => 'C'],
            ['problem' => '(267 - 139) × 6 = ?', 'options' => ['A' => '766', 'B' => '768', 'C' => '770', 'D' => '772'], 'correct' => 'B'],
            ['problem' => '945 ÷ 35 + 68 = ?', 'options' => ['A' => '93', 'B' => '94', 'C' => '95', 'D' => '96'], 'correct' => 'C'],
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
