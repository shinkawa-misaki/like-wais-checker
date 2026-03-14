<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
    <div class="max-w-lg w-full text-center space-y-8">

      <!-- ロゴ / タイトル -->
      <div>
        <div class="text-6xl mb-4">🧠</div>
        <h1 class="text-3xl font-bold text-gray-900">認知の手すりチェック Lite</h1>
        <p class="mt-3 text-gray-500 text-sm">
          言語整理・構造理解・保持操作・速度耐性の4つの認知資源を確認するチェックです
        </p>
      </div>

      <!-- 4指数カード -->
      <div class="grid grid-cols-2 gap-3 text-left">
        <div
          v-for="index in indices"
          :key="index.key"
          class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm"
        >
          <div class="flex items-center gap-2 mb-1">
            <span class="text-lg">{{ index.icon }}</span>
            <span class="text-xs font-semibold text-gray-400 uppercase">{{ index.key }}</span>
          </div>
          <p class="text-sm font-medium text-gray-700">{{ index.label }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ index.desc }}</p>
        </div>
      </div>

      <!-- スペック -->
      <div class="bg-blue-50 rounded-xl p-4 text-sm text-blue-800 text-left space-y-1">
        <div class="flex items-center gap-2">
          <span>⏱️</span>
          <span>所要時間：10〜20分</span>
        </div>
        <div class="flex items-center gap-2">
          <span>📝</span>
          <span>4セクション・24問</span>
        </div>
        <div class="flex items-center gap-2">
          <span>📵</span>
          <span>検索・電卓禁止</span>
        </div>
      </div>

      <!-- 開始ボタン -->
      <button
        @click="goToDisclaimer"
        class="w-full py-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800
               text-white font-semibold rounded-2xl transition-colors text-lg shadow-md"
      >
        検査を始める
      </button>

      <!-- 再開リンク -->
      <p
        v-if="hasSavedSession"
        class="text-sm text-gray-500"
      >
        <button
          @click="resumeSession"
          class="text-blue-600 underline"
        >
          前回の続きから再開する
        </button>
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAssessmentStore, SUBTEST_ORDER } from '../stores/assessment.js';

const router = useRouter();
const store = useAssessmentStore();

const indices = [
    { key: 'VCI', label: '言語整理', icon: '🔤', desc: '要点をつかむ・言い換える・輪郭を作る' },
    { key: 'PRI', label: '構造理解', icon: '🔲', desc: '規則発見・分類・手順や型を読む' },
    { key: 'WMI', label: '保持操作', icon: '🧠', desc: '情報を保持しながら並べ替え・条件を扱う' },
    { key: 'PSI', label: '速度耐性', icon: '⚡', desc: '時間圧の中で処理を進める・焦りに崩れにくい' },
];

const hasSavedSession = computed(() =>
    store.assessmentId && store.completedSubtests.length > 0 && !store.isComplete
);

function goToDisclaimer() {
    store.reset();
    router.push({ name: 'disclaimer' });
}

function resumeSession() {
    const nextSubtest = SUBTEST_ORDER[store.completedSubtests.length];
    router.push({ name: 'subtest', params: { subtestType: nextSubtest } });
}
</script>
