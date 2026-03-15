<template>
  <div class="space-y-4">

    <!-- タイマーバー -->
    <div v-if="timeLimitSeconds" class="bg-white rounded-xl border border-gray-200 p-3">
      <div class="flex items-center justify-between mb-1.5">
        <span class="text-xs text-gray-500">残り時間</span>
        <span :class="['text-lg font-bold tabular-nums', timerColor]">
          {{ remaining }}秒
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-1.5">
        <div
          :class="['h-1.5 rounded-full transition-all duration-1000', timerBarColor]"
          :style="{ width: timerPercent + '%' }"
        ></div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs font-semibold text-gray-400">問 {{ question.sequenceNumber }}</span>
      </div>
      <p class="text-gray-800 font-medium whitespace-pre-line">{{ question.content }}</p>
    </div>

    <!-- 選択肢がない場合のエラー表示 -->
    <div v-if="!question.options || Object.keys(question.options).length === 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
      <p class="text-red-700 text-sm">⚠️ 選択肢が読み込まれませんでした。ページを再読み込みしてください。</p>
    </div>

    <div v-else class="grid grid-cols-2 gap-2">
      <button
        v-for="(label, key) in question.options"
        :key="key"
        @click="select(key)"
        class="p-3 rounded-xl border-2 transition-all text-left"
        :class="[
          selected === key
            ? 'border-blue-500 bg-blue-50 text-blue-700 font-semibold'
            : 'border-gray-200 text-gray-700 hover:border-gray-300 bg-white'
        ]"
      >
        <span class="font-bold mr-2">{{ key }})</span>{{ label }}
      </button>
    </div>

    <button
      @click="confirm"
      :disabled="!selected"
      class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium
             hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
    >
      確定して次へ
    </button>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';

const props = defineProps({
    question: { type: Object, required: true },
    timeLimitSeconds: { type: Number, default: null },
});

const emit = defineEmits(['answered']);

const selected = ref(null);
const remaining = ref(props.timeLimitSeconds ?? 0);
let timer = null;

const timerPercent = computed(() =>
    props.timeLimitSeconds ? (remaining.value / props.timeLimitSeconds) * 100 : 100
);

const timerColor = computed(() => {
    if (remaining.value <= 5) return 'text-red-600';
    if (remaining.value <= 10) return 'text-orange-500';
    return 'text-gray-700';
});

const timerBarColor = computed(() => {
    if (remaining.value <= 5) return 'bg-red-500';
    if (remaining.value <= 10) return 'bg-orange-400';
    return 'bg-blue-500';
});

function startTimer() {
    if (!props.timeLimitSeconds) return;
    remaining.value = props.timeLimitSeconds;
    clearInterval(timer);
    timer = setInterval(() => {
        remaining.value--;
        if (remaining.value <= 0) {
            clearInterval(timer);
            // 時間切れ：現在の選択（なければ空文字）で自動提出
            emitAnswer(selected.value ?? '');
        }
    }, 1000);
}

function select(key) {
    selected.value = key;
}

function confirm() {
    if (!selected.value) return;
    clearInterval(timer);
    emitAnswer(selected.value);
}

function emitAnswer(response) {
    const answer = {
        question_id: props.question.id,
        response,
        awarded_score: null,
    };
    selected.value = null;
    emit('answered', answer);
}

// 問題が切り替わるたびにタイマーをリセット
watch(() => props.question, () => {
    selected.value = null;
    startTimer();
}, { immediate: true });

onUnmounted(() => clearInterval(timer));
</script>
