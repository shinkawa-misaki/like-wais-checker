<template>
  <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12">
    <div class="max-w-lg w-full space-y-6">

      <div class="text-center">
        <div class="text-4xl mb-3">⚠️</div>
        <h2 class="text-xl font-bold text-gray-900">実施前のご確認</h2>
      </div>

      <!-- エラーメッセージ -->
      <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
        <p class="font-semibold">エラーが発生しました</p>
        <p class="mt-1">{{ errorMessage }}</p>
      </div>

      <!-- 注意事項 -->
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-3 text-sm text-gray-700">
        <p class="font-semibold text-amber-800">【重要な注意】</p>
        <ul class="space-y-2 list-none">
          <li class="flex gap-2">
            <span class="text-amber-500 shrink-0">•</span>
            <span>これはWAIS（標準化検査）<strong>ではありません</strong>。年齢別ノームがないため、正式なIQは算出できません。</span>
          </li>
          <li class="flex gap-2">
            <span class="text-amber-500 shrink-0">•</span>
            <span>結果は「強み／負荷ポイント」の自己理解・戦略設計の参考指標です。診断・断定・ラベリングには使いません。</span>
          </li>
          <li class="flex gap-2">
            <span class="text-amber-500 shrink-0">•</span>
            <span>体調（睡眠不足・不安・疲労・血糖）でWMI/PSIが落ちやすいです。体調が悪い日は別日に実施してください。</span>
          </li>
          <li class="flex gap-2">
            <span class="text-amber-500 shrink-0">•</span>
            <span><strong>検索・辞書・電卓は禁止</strong>。</span>
          </li>
        </ul>
      </div>

      <!-- 準備チェックリスト -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <p class="font-semibold text-gray-800 mb-3">実施前チェック</p>
        <div class="space-y-3">
          <label
            v-for="item in checklist"
            :key="item.id"
            class="flex items-start gap-3 cursor-pointer"
          >
            <input
              type="checkbox"
              v-model="item.checked"
              class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 cursor-pointer"
            >
            <span class="text-sm text-gray-700">{{ item.label }}</span>
          </label>
        </div>
      </div>

      <!-- ボタン -->
      <div class="flex gap-3">
        <button
          @click="$router.back()"
          class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors"
        >
          戻る
        </button>
        <button
          @click="start"
          :disabled="!allChecked || loading"
          class="flex-2 flex-1 py-3 bg-blue-600 text-white rounded-xl font-semibold
                 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {{ loading ? '準備中...' : '同意して開始する' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAssessmentStore, SUBTEST_ORDER } from '../stores/assessment.js';

const router = useRouter();
const store = useAssessmentStore();
const loading = ref(false);
const errorMessage = ref('');

const checklist = ref([
    { id: 'quiet', label: '静かな場所にいる', checked: false },
    { id: 'nodic', label: '検索・辞書・電卓を使わないことに同意する', checked: false },
    { id: 'health', label: '体調は問題ない（睡眠・疲労など）', checked: false },
    { id: 'time', label: '30〜40分の時間を確保した', checked: false },
]);

const allChecked = computed(() => checklist.value.every(item => item.checked));

async function start() {
    if (!allChecked.value) return;
    loading.value = true;
    errorMessage.value = '';
    try {
        console.log('Starting assessment...');
        const result = await store.startAssessment();
        console.log('Assessment started:', result);
        console.log('Navigating to subtest:', SUBTEST_ORDER[0]);
        await router.push({ name: 'subtest', params: { subtestType: SUBTEST_ORDER[0] } });
    } catch (error) {
        console.error('Failed to start assessment:', error);
        console.error('Error response:', error.response?.data);
        errorMessage.value = error.response?.data?.error || error.response?.data?.message || 'アセスメントの開始に失敗しました。ページを再読み込みして再度お試しください。';
        loading.value = false;
    }
}
</script>
