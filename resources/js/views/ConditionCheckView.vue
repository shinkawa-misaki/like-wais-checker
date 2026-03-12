<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">

    <header class="bg-white border-b border-gray-100 px-4 py-4">
      <div class="max-w-lg mx-auto text-center">
        <h1 class="text-xl font-bold text-gray-900">現在の状態チェック</h1>
        <p class="text-xs text-gray-400 mt-1">AIレポート生成のための状態確認です</p>
      </div>
    </header>

    <main class="flex-1 px-4 py-6 max-w-lg mx-auto w-full">
      <div class="space-y-5">

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">
          全サブテスト完了です。現在の体調・状態を教えてください。
          AIが結果に状態要因を加味した、より的確なフィードバックを生成します。
        </div>

        <!-- 睡眠 -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-semibold text-gray-800 mb-3">😴 昨夜の睡眠は？</p>
          <div class="space-y-2">
            <label
              v-for="opt in SLEEP_OPTIONS"
              :key="opt.value"
              class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
              :class="condition.sleep === opt.value
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-200 hover:border-gray-300'"
            >
              <input type="radio" :value="opt.value" v-model="condition.sleep" class="w-4 h-4 text-blue-600" />
              <span class="text-sm text-gray-800">{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <!-- 疲労 -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-semibold text-gray-800 mb-3">🔋 現在の疲労感は？</p>
          <div class="space-y-2">
            <label
              v-for="opt in LEVEL_OPTIONS"
              :key="opt.value"
              class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
              :class="condition.fatigue === opt.value
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-200 hover:border-gray-300'"
            >
              <input type="radio" :value="opt.value" v-model="condition.fatigue" class="w-4 h-4 text-blue-600" />
              <span class="text-sm text-gray-800">{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <!-- 焦り -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-semibold text-gray-800 mb-3">⚡ 今の焦り・プレッシャー感は？</p>
          <div class="space-y-2">
            <label
              v-for="opt in LEVEL_OPTIONS"
              :key="opt.value"
              class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
              :class="condition.anxiety === opt.value
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-200 hover:border-gray-300'"
            >
              <input type="radio" :value="opt.value" v-model="condition.anxiety" class="w-4 h-4 text-blue-600" />
              <span class="text-sm text-gray-800">{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <!-- 集中の波 -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-sm font-semibold text-gray-800 mb-3">🌊 受検中の集中の波は？</p>
          <div class="space-y-2">
            <label
              v-for="opt in FOCUS_OPTIONS"
              :key="opt.value"
              class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
              :class="condition.focus === opt.value
                ? 'border-blue-500 bg-blue-50'
                : 'border-gray-200 hover:border-gray-300'"
            >
              <input type="radio" :value="opt.value" v-model="condition.focus" class="w-4 h-4 text-blue-600" />
              <span class="text-sm text-gray-800">{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <button
          @click="submit"
          :disabled="!isComplete"
          class="w-full py-4 bg-blue-600 text-white rounded-2xl font-semibold text-lg
                 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-md"
        >
          レポートを見る →
        </button>

      </div>
    </main>
  </div>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAssessmentStore } from '../stores/assessment.js';

const router = useRouter();
const store = useAssessmentStore();

const SLEEP_OPTIONS = [
    { value: '十分', label: '十分（6時間以上、すっきり起きられた）' },
    { value: 'やや不足', label: 'やや不足（4〜6時間、少し眠い）' },
    { value: '不足', label: '不足（4時間未満、かなり眠い）' },
];

const LEVEL_OPTIONS = [
    { value: '低', label: '低（ほぼ感じない）' },
    { value: '中', label: '中（多少ある）' },
    { value: '高', label: '高（かなり感じている）' },
];

const FOCUS_OPTIONS = [
    { value: '安定', label: '安定（最初から最後まで集中できた）' },
    { value: 'やや波あり', label: 'やや波あり（途中で少し崩れた）' },
    { value: '波あり', label: '波あり（集中が続かなかった）' },
];

const condition = reactive({
    sleep: '',
    fatigue: '',
    anxiety: '',
    focus: '',
});

const isComplete = computed(() =>
    condition.sleep && condition.fatigue && condition.anxiety && condition.focus
);

function submit() {
    if (!isComplete.value) return;
    store.setCondition({ ...condition });
    router.push({ name: 'report' });
}
</script>
