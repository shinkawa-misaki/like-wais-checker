<template>
  <div class="min-h-screen bg-gray-50">

    <!-- ヘッダー -->
    <header class="bg-white border-b border-gray-100 px-4 py-4">
      <div class="max-w-lg mx-auto text-center">
        <h1 class="text-xl font-bold text-gray-900">検査結果レポート</h1>
        <p class="text-xs text-gray-400 mt-1">{{ report?.generatedAt }}</p>
      </div>
    </header>

    <main class="px-4 py-6 max-w-lg mx-auto space-y-6">

      <!-- ローディング -->
      <div v-if="loading" class="text-center py-20">
        <div class="inline-block w-8 h-8 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin"></div>
        <p class="mt-3 text-sm text-gray-500">レポートを生成中...</p>
      </div>

      <!-- エラー -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
        {{ error }}
      </div>

      <template v-else-if="report">

        <!-- 免責事項 -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800">
          {{ report.disclaimer }}
        </div>

        <!-- 4指数スコア一覧 -->
        <section>
          <h2 class="text-base font-bold text-gray-800 mb-3">📊 4指数の結果</h2>
          <div class="space-y-3">
            <div
              v-for="score in report.indexScores"
              :key="score.indexType"
              class="bg-white rounded-xl border border-gray-200 p-4"
            >
              <div class="flex items-center justify-between mb-2">
                <div>
                  <span class="text-sm font-semibold text-gray-800">{{ score.label }}</span>
                  <span :class="['ml-2 text-xs px-2 py-0.5 rounded-full font-medium', levelBadgeClass(score.percentage)]">
                    {{ score.level }}
                  </span>
                </div>
                <div class="text-right">
                  <div class="text-2xl font-bold tabular-nums" :class="iqColor(score.pseudoIQ)">
                    {{ score.pseudoIQ }}
                  </div>
                  <div class="text-xs text-gray-400">擬似IQ</div>
                </div>
              </div>
              <!-- IQ解釈 -->
              <div class="mb-2 text-xs" :class="iqColor(score.pseudoIQ)">
                {{ score.iqInterpretation }}
              </div>
              <!-- プログレスバー -->
              <div class="w-full bg-gray-100 rounded-full h-3 mb-2">
                <div
                  :class="['h-3 rounded-full transition-all duration-1000', barColor(score.percentage)]"
                  :style="{ width: score.percentage + '%' }"
                ></div>
              </div>
              <p class="text-xs text-gray-500">
                得点：{{ score.rawScore }} / {{ score.maxScore }}点（{{ score.percentage }}%）
              </p>
            </div>
          </div>
        </section>

        <!-- 強み -->
        <section>
          <h2 class="text-base font-bold text-gray-800 mb-3">💪 強みTOP2</h2>
          <div class="space-y-3">
            <div
              v-for="s in report.strengthIndices"
              :key="s.indexType"
              class="bg-green-50 border border-green-200 rounded-xl p-4"
            >
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-bold text-green-800">{{ s.label }}</span>
                  <span class="text-xs text-green-600">{{ s.percentage }}%</span>
                </div>
                <div class="text-right">
                  <div class="text-xl font-bold text-green-700">IQ {{ s.pseudoIQ }}</div>
                  <div class="text-xs text-green-600">{{ s.iqInterpretation }}</div>
                </div>
              </div>
              <p class="text-sm text-green-700">{{ s.interpretation }}</p>
            </div>
          </div>
        </section>

        <!-- 負荷ポイント -->
        <section>
          <h2 class="text-base font-bold text-gray-800 mb-3">⚡ 負荷ポイントTOP2</h2>
          <div class="space-y-3">
            <div
              v-for="w in report.weaknessIndices"
              :key="w.indexType"
              class="bg-orange-50 border border-orange-200 rounded-xl p-4"
            >
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-bold text-orange-800">{{ w.label }}</span>
                  <span class="text-xs text-orange-600">{{ w.percentage }}%</span>
                </div>
                <div class="text-right">
                  <div class="text-xl font-bold text-orange-700">IQ {{ w.pseudoIQ }}</div>
                  <div class="text-xs text-orange-600">{{ w.iqInterpretation }}</div>
                </div>
              </div>
              <p class="text-sm text-orange-700">{{ w.interpretation }}</p>
            </div>
          </div>
        </section>

        <!-- 戦略 -->
        <section>
          <h2 class="text-base font-bold text-gray-800 mb-3">🎯 仕事・学習の戦略</h2>
          <div class="space-y-3">
            <div
              v-for="(desc, key) in report.strategies"
              :key="key"
              :class="[
                'rounded-xl p-4 text-sm',
                key.startsWith('strength') ? 'bg-blue-50 border border-blue-200 text-blue-800' : 'bg-purple-50 border border-purple-200 text-purple-800'
              ]"
            >
              {{ desc }}
            </div>
          </div>
        </section>

        <!-- 次の一手 -->
        <section>
          <h2 class="text-base font-bold text-gray-800 mb-3">🚀 次の一手</h2>
          <div class="bg-white rounded-xl border border-gray-200 p-4">
            <ul class="space-y-3">
              <li
                v-for="(step, i) in report.nextSteps"
                :key="i"
                class="flex gap-3 text-sm text-gray-700"
              >
                <span class="text-blue-500 font-bold shrink-0">{{ i + 1 }}.</span>
                <span>{{ step }}</span>
              </li>
            </ul>
          </div>
        </section>

        <!-- レーダーチャート的サマリー -->
        <section class="bg-white rounded-xl border border-gray-200 p-4">
          <h2 class="text-sm font-bold text-gray-700 mb-3">指数バランス一覧</h2>
          <div class="space-y-2">
            <div v-for="score in report.indexScores" :key="score.indexType" class="flex items-center gap-3">
              <span class="text-xs text-gray-500 w-8 shrink-0">{{ score.indexType }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-4">
                <div
                  :class="['h-4 rounded-full', barColor(score.percentage)]"
                  :style="{ width: score.percentage + '%' }"
                ></div>
              </div>
              <span class="text-xs font-semibold text-gray-600 w-12 text-right">{{ score.percentage }}%</span>
            </div>
          </div>
          <div class="flex justify-between text-xs text-gray-300 mt-1 px-11">
            <span>0</span>
            <span>25</span>
            <span>50</span>
            <span>75</span>
            <span>100%</span>
          </div>
        </section>

        <!-- 再実施 -->
        <div class="text-center pb-8">
          <button
            @click="restart"
            class="px-6 py-3 border border-gray-300 text-gray-600 rounded-xl text-sm
                   hover:bg-gray-50 transition-colors"
          >
            最初からやり直す
          </button>
          <p class="text-xs text-gray-400 mt-3">
            ※ 別日に再実施することで、体調によるブレを確認できます
          </p>
        </div>

      </template>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAssessmentStore } from '../stores/assessment.js';

const router = useRouter();
const store = useAssessmentStore();

const report = ref(null);
const loading = ref(true);
const error = ref(null);

async function load() {
    loading.value = true;
    error.value = null;
    try {
        report.value = await store.fetchReport();
    } catch {
        error.value = 'レポートの取得に失敗しました。全サブテストを完了してから再度お試しください。';
    } finally {
        loading.value = false;
    }
}

function levelBadgeClass(pct) {
    if (pct >= 81) return 'bg-green-100 text-green-700';
    if (pct >= 61) return 'bg-blue-100 text-blue-700';
    if (pct >= 41) return 'bg-gray-100 text-gray-600';
    if (pct >= 21) return 'bg-orange-100 text-orange-700';
    return 'bg-red-100 text-red-700';
}

function scoreColor(pct) {
    if (pct >= 61) return 'text-green-600';
    if (pct >= 41) return 'text-gray-700';
    return 'text-orange-600';
}

function barColor(pct) {
    if (pct >= 81) return 'bg-green-500';
    if (pct >= 61) return 'bg-blue-500';
    if (pct >= 41) return 'bg-gray-400';
    if (pct >= 21) return 'bg-orange-400';
    return 'bg-red-400';
}

function iqColor(iq) {
    if (iq >= 130) return 'text-green-600';
    if (iq >= 110) return 'text-blue-600';
    if (iq >= 90) return 'text-gray-700';
    if (iq >= 70) return 'text-orange-600';
    return 'text-red-600';
}

function restart() {
    store.reset();
    router.push({ name: 'home' });
}

onMounted(load);
</script>
