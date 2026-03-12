<template>
  <div class="min-h-screen flex flex-col">

    <!-- ヘッダー -->
    <header class="bg-white border-b border-gray-100 px-4 py-3 sticky top-0 z-10">
      <div class="max-w-lg mx-auto flex items-center gap-4">
        <div class="flex-1">
          <p class="text-xs text-gray-400">
            {{ meta?.subtestLabel }} — {{ meta?.indexType }}
          </p>
        </div>
        <!-- プログレスバー -->
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <div class="flex-1 bg-gray-100 rounded-full h-2">
              <div
                class="bg-blue-500 h-2 rounded-full transition-all"
                :style="{ width: subtestProgress + '%' }"
              ></div>
            </div>
            <span class="text-xs text-gray-400 whitespace-nowrap">
              {{ currentIndex + 1 }}/{{ questions.length }}問
            </span>
          </div>
        </div>
        <!-- 全体進捗 -->
        <div class="text-xs text-gray-400 whitespace-nowrap">
          サブテスト {{ store.completedSubtests.length + 1 }}/8
        </div>
      </div>
    </header>

    <main class="flex-1 px-4 py-6">
      <div class="max-w-lg mx-auto space-y-4">

        <!-- ローディング -->
        <div v-if="store.loading" class="text-center py-20">
          <div class="inline-block w-8 h-8 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
          <p class="mt-3 text-sm text-gray-500">読み込み中...</p>
        </div>

        <!-- エラー -->
        <div v-else-if="store.error" class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
          {{ store.error }}
          <button @click="load" class="mt-2 underline block">再試行</button>
        </div>

        <!-- 案内画面 -->
        <template v-else-if="phase === 'intro'">
          <div class="text-center space-y-4">
            <div class="text-5xl">{{ SUBTEST_META[subtestType]?.icon }}</div>
            <h2 class="text-xl font-bold text-gray-900">{{ meta?.subtestLabel }}</h2>
            <div class="text-left bg-white rounded-xl border border-gray-200 p-5 text-sm text-gray-700 space-y-2">
              <p class="font-semibold text-gray-900 mb-3">📋 実施方法</p>
              <p class="whitespace-pre-line">{{ meta?.instructions }}</p>
            </div>
            <div v-if="meta?.timeLimitSeconds" class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-800">
              <strong>⏱️ 時間制限：{{ meta.timeLimitSeconds }}秒</strong>
              <br>開始ボタンを押してください。
            </div>
            <div class="flex gap-2 text-sm text-gray-500">
              <span>問題数：{{ questions.length }}問</span>
              <span>•</span>
              <span>満点：{{ questions.reduce((sum, q) => sum + q.maxPoints, 0) }}点</span>
            </div>
            <button
              @click="phase = 'questions'"
              class="w-full py-4 bg-blue-600 text-white rounded-2xl font-semibold text-lg
                     hover:bg-blue-700 transition-colors shadow-md"
            >
              開始する →
            </button>
          </div>
        </template>

        <!-- 問題フェーズ：時間制限ありサブテスト -->
        <template v-else-if="phase === 'questions' && isTimeBased">
          <TimedSubtest
            :questions="questions"
            :time-limit-seconds="meta.timeLimitSeconds"
            :subtest-type="subtestType"
            @submitted="onTimedSubmit"
          />
        </template>

        <!-- 問題フェーズ：通常サブテスト -->
        <template v-else-if="phase === 'questions' && !isTimeBased">

          <!-- 現在の問題 -->
          <FreeTextQuestion
            v-if="currentQuestion?.questionType === 'free_text'"
            :key="`question-${currentIndex}`"
            :question="currentQuestion"
            :revealed-answer="currentCorrectAnswer"
            @confirm="onConfirmResponse"
            @answered="onAnswered"
          />
          <MatrixReasoningQuestion
            v-else-if="subtestType === 'D'"
            :key="`question-${currentIndex}`"
            :question="currentQuestion"
            @answered="onAnswered"
          />
          <MultipleChoiceQuestion
            v-else-if="currentQuestion?.questionType === 'multiple_choice'"
            :key="`question-${currentIndex}`"
            :question="currentQuestion"
            @answered="onAnswered"
          />
          <SequenceQuestion
            v-else-if="currentQuestion?.questionType === 'sequence'"
            :key="`question-${currentIndex}`"
            :question="currentQuestion"
            @answered="onAnswered"
          />
        </template>

        <!-- 提出中 -->
        <div v-else-if="phase === 'submitting'" class="text-center py-20">
          <div class="inline-block w-8 h-8 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
          <p class="mt-3 text-sm text-gray-500">回答を送信中...</p>
        </div>

        <!-- 完了 -->
        <div v-else-if="phase === 'done'" class="text-center space-y-6">
          <div class="text-5xl">✅</div>
          <h2 class="text-xl font-bold text-gray-900">{{ meta?.subtestLabel }}　完了！</h2>

          <div v-if="!store.isComplete" class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-600 mb-4">次のサブテストへ進んでください</p>
            <div class="flex items-center gap-3 text-left">
              <div class="text-3xl">{{ SUBTEST_META[nextSubtest]?.icon }}</div>
              <div>
                <p class="font-semibold text-gray-800">{{ SUBTEST_META[nextSubtest]?.label }}</p>
                <p class="text-xs text-gray-400">{{ SUBTEST_META[nextSubtest]?.index }}</p>
              </div>
            </div>
          </div>

          <button
            @click="goNext"
            class="w-full py-4 bg-blue-600 text-white rounded-2xl font-semibold text-lg
                   hover:bg-blue-700 transition-colors shadow-md"
          >
            {{ store.isComplete ? '結果を見る →' : '次へ進む →' }}
          </button>
        </div>

      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAssessmentStore, SUBTEST_ORDER, SUBTEST_META } from '../stores/assessment.js';
import FreeTextQuestion from '../components/FreeTextQuestion.vue';
import MultipleChoiceQuestion from '../components/MultipleChoiceQuestion.vue';
import MatrixReasoningQuestion from '../components/MatrixReasoningQuestion.vue';
import SequenceQuestion from '../components/SequenceQuestion.vue';
import TimedSubtest from '../components/TimedSubtest.vue';

const props = defineProps({
    subtestType: { type: String, required: true },
});

const router = useRouter();
const store = useAssessmentStore();

const phase = ref('loading');
const meta = ref(null);
const questions = ref([]);
const collectedAnswers = ref([]);
const currentIndex = ref(0);
const nextSubtest = ref(null);

const currentQuestion = computed(() => questions.value[currentIndex.value] || null);
const currentCorrectAnswer = ref(null);
const isTimeBased = computed(() => meta.value?.timeLimitSeconds !== null);
const subtestProgress = computed(() =>
    questions.value.length > 0
        ? Math.round(((currentIndex.value) / questions.value.length) * 100)
        : 0
);

async function load() {
    phase.value = 'loading';
    // 古い回答データをクリア
    collectedAnswers.value = [];
    currentIndex.value = 0;

    const data = await store.fetchQuestions(props.subtestType);
    meta.value = data;
    questions.value = data.questions;

    const nextIdx = SUBTEST_ORDER.indexOf(props.subtestType) + 1;
    nextSubtest.value = SUBTEST_ORDER[nextIdx] || null;

    phase.value = 'intro';
}

// FREE_TEXT: 回答確定時に保存して模範解答を取得
async function onConfirmResponse(answer) {
    try {
        const result = await store.saveAnswer(props.subtestType, answer);
        currentCorrectAnswer.value = result?.correctAnswer ?? '';
    } catch (e) {
        const serverMsg = e.response?.data?.error;
        store.error = serverMsg
            ? `回答の保存に失敗しました: ${serverMsg}`
            : '回答の保存に失敗しました。もう一度お試しください。';
    }
}

async function onAnswered(answer) {
    // 1問ずつ即座にDBへ保存（FREE_TEXT は採点スコアの更新のみ）
    try {
        await store.saveAnswer(props.subtestType, answer);
    } catch (e) {
        const serverMsg = e.response?.data?.error;
        store.error = serverMsg
            ? `回答の保存に失敗しました: ${serverMsg}`
            : '回答の保存に失敗しました。もう一度お試しください。';
        return;
    }

    currentCorrectAnswer.value = null;
    collectedAnswers.value.push(answer);
    if (currentIndex.value < questions.value.length - 1) {
        currentIndex.value++;
    } else {
        // 全問回答済み → サブテスト完了マークのみ送信（回答は既にDB保存済み）
        await completeSubtest();
    }
}

async function onTimedSubmit({ answers, elapsedSeconds }) {
    // タイムド系は一括送信（従来通り）
    collectedAnswers.value = answers;
    phase.value = 'submitting';
    await store.submitAnswers(props.subtestType, collectedAnswers.value, elapsedSeconds);
    phase.value = 'done';
}

async function completeSubtest() {
    phase.value = 'submitting';
    // 回答なしで完了マークのみ送信
    await store.submitAnswers(props.subtestType, [], null);
    phase.value = 'done';
}

function goNext() {
    if (store.isComplete) {
        router.push({ name: 'condition-check' });
    } else {
        router.push({ name: 'subtest', params: { subtestType: nextSubtest.value } });
    }
}

onMounted(load);

// assessmentId が null になった（セッションリセット）場合はホームへ
watch(() => store.assessmentId, (id) => {
    if (!id) {
        router.push({ name: 'home' });
    }
});

// propsの変更を監視して、サブテストが切り替わったら再読み込み
watch(() => props.subtestType, () => {
    // 状態をリセット
    phase.value = 'loading';
    collectedAnswers.value = [];
    currentIndex.value = 0;
    // 新しいサブテストを読み込む
    load();
});
</script>
