<template>
  <div class="space-y-4">

    <!-- タイマーバー -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">残り時間</span>
        <span :class="['text-2xl font-bold tabular-nums', timerColor]">
          {{ formatTime(remainingSeconds) }}
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          :class="['h-2 rounded-full transition-all duration-1000', timerBarColor]"
          :style="{ width: timerPercent + '%' }"
        ></div>
      </div>
    </div>


    <!-- 前段階：タイマー開始前 -->
    <div v-if="!started">
      <button
        @click="startTimer"
        class="w-full py-4 bg-red-600 text-white rounded-xl font-bold text-lg
               hover:bg-red-700 transition-colors shadow-md"
      >
        タイマー開始 & 回答を開始する
      </button>
    </div>

    <!-- 問題一覧（タイマー稼働中） -->
    <template v-else-if="!finished">
      <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
        <div
          v-for="(q, i) in questions"
          :key="q.id"
          class="bg-white rounded-xl border border-gray-200 p-4"
        >
          <div class="flex items-start gap-3">
            <span class="text-xs font-bold text-gray-400 pt-0.5 shrink-0">{{ i + 1 }}.</span>
            <div class="flex-1">
              <p class="text-sm text-gray-700 whitespace-pre-line mb-2">{{ q.content }}</p>
              <!-- 速度耐性: ○×ボタン -->
              <div class="flex gap-2">
                <button
                  @click="setAnswer(q.id, '○')"
                  :class="[
                    'flex-1 py-2 rounded-lg border-2 text-sm font-bold transition-all',
                    answers[q.id] === '○'
                      ? 'border-green-500 bg-green-50 text-green-700'
                      : 'border-gray-200 text-gray-600'
                  ]"
                >○ はい</button>
                <button
                  @click="setAnswer(q.id, '×')"
                  :class="[
                    'flex-1 py-2 rounded-lg border-2 text-sm font-bold transition-all',
                    answers[q.id] === '×'
                      ? 'border-red-500 bg-red-50 text-red-700'
                      : 'border-gray-200 text-gray-600'
                  ]"
                >× いいえ</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <button
        @click="submitAll"
        class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium
               hover:bg-blue-700 transition-colors"
      >
        回答を提出する（{{ answeredCount }}/{{ questions.length }}問回答済み）
      </button>
    </template>

    <!-- 時間切れ -->
    <div v-else class="text-center py-8">
      <div class="text-4xl mb-3">⏱️</div>
      <p class="text-gray-700 font-medium mb-4">時間になりました</p>
      <p class="text-sm text-gray-500 mb-6">
        回答済み：{{ answeredCount }}/{{ questions.length }}問
      </p>
      <button
        @click="submitAll"
        class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium
               hover:bg-blue-700 transition-colors"
      >
        提出する
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps({
    questions: { type: Array, required: true },
    timeLimitSeconds: { type: Number, required: true },
    subtestType: { type: String, required: true },
});

const emit = defineEmits(['submitted']);

const started = ref(false);
const finished = ref(false);
const remainingSeconds = ref(props.timeLimitSeconds);
const answers = ref({});
const elapsedSeconds = ref(0);

let timer = null;

const timerPercent = computed(() =>
    (remainingSeconds.value / props.timeLimitSeconds) * 100
);

const timerColor = computed(() => {
    if (remainingSeconds.value <= 10) return 'text-red-600';
    if (remainingSeconds.value <= 20) return 'text-orange-500';
    return 'text-gray-800';
});

const timerBarColor = computed(() => {
    if (remainingSeconds.value <= 10) return 'bg-red-500';
    if (remainingSeconds.value <= 20) return 'bg-orange-400';
    return 'bg-blue-500';
});

const answeredCount = computed(() => Object.keys(answers.value).length);

function formatTime(secs) {
    const m = Math.floor(secs / 60).toString().padStart(2, '0');
    const s = (secs % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function setAnswer(questionId, value) {
    answers.value = { ...answers.value, [questionId]: value };
}

function startTimer() {
    started.value = true;
    timer = setInterval(() => {
        remainingSeconds.value--;
        elapsedSeconds.value++;
        if (remainingSeconds.value <= 0) {
            clearInterval(timer);
            finished.value = true;
        }
    }, 1000);
}

function submitAll() {
    clearInterval(timer);
    const result = props.questions.map(q => ({
        question_id: q.id,
        response: answers.value[q.id] || '',
        awarded_score: null,
    }));
    emit('submitted', { answers: result, elapsedSeconds: elapsedSeconds.value });
}

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>
