<template>
  <div class="space-y-4">
    <!-- 問題 -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs font-semibold text-gray-400">問 {{ question.sequenceNumber }}</span>
        <span class="text-xs text-gray-300">/</span>
        <span class="text-xs text-gray-400">自由記述</span>
      </div>
      <p class="text-gray-800 font-medium whitespace-pre-line">{{ question.content }}</p>
    </div>

    <!-- 回答入力フェーズ -->
    <div v-if="!hasAnswered">
      <textarea
        v-model="response"
        placeholder="回答を入力してください..."
        rows="3"
        class="w-full border border-gray-300 rounded-xl p-3 text-sm resize-none
               focus:outline-none focus:ring-2 focus:ring-blue-400"
      ></textarea>

      <button
        type="button"
        @click.prevent="submitResponse"
        :disabled="!canSubmit"
        class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium mt-3
               hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        回答を確定する
      </button>
    </div>

    <!-- 採点フェーズ -->
    <div v-else>
      <!-- 自分の回答表示 -->
      <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
        <p class="text-xs text-gray-500 mb-2">あなたの回答：</p>
        <p class="text-gray-800 whitespace-pre-line">{{ response }}</p>
      </div>

      <!-- 模範解答表示 -->
      <div v-if="question.correctAnswer || question.correct_answer || question.hint" class="bg-green-50 rounded-xl border border-green-200 p-4 mt-3">
        <p class="text-xs text-green-600 font-semibold mb-2">✓ 模範解答</p>
        <p class="text-sm text-green-800">{{ question.correctAnswer || question.correct_answer || question.hint }}</p>
      </div>

      <!-- 採点UI -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 mt-4">
        <h3 class="text-sm font-semibold text-gray-900 mb-3">📝 この回答に点数をつけてください</h3>

        <div class="space-y-2 mb-4">
          <label
            v-for="score in [0, 1, 2]"
            :key="score"
            class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all"
            :class="selectedScore === score
              ? 'border-blue-500 bg-blue-50'
              : 'border-gray-200 hover:border-gray-300'"
          >
            <input
              type="radio"
              :value="score"
              v-model="selectedScore"
              class="w-4 h-4 text-blue-600"
            />
            <span class="flex items-center gap-2 flex-1">
              <span class="font-semibold text-gray-900">{{ score }}点</span>
              <span class="text-xs text-gray-500">{{ getScoreLabel(score) }}</span>
            </span>
          </label>
        </div>

        <button
          type="button"
          @click.prevent="submitGrading"
          :disabled="selectedScore === null"
          class="w-full py-3 bg-green-600 text-white rounded-xl font-medium
                 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          採点を確定して次へ
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    question: { type: Object, required: true },
});

const emit = defineEmits(['answered']);

const response = ref('');
const hasAnswered = ref(false);
const selectedScore = ref(null);

const canSubmit = computed(() => {
    return response.value && response.value.trim().length > 0;
});

function submitResponse() {
    const trimmedResponse = response.value.trim();

    if (!trimmedResponse) {
        return;
    }

    // 回答を確定
    hasAnswered.value = true;
}

function submitGrading() {
    if (selectedScore.value === null) {
        return;
    }

    // 親コンポーネントに回答と採点を送信
    emit('answered', {
        question_id: props.question.id,
        response: response.value.trim(),
        awarded_score: selectedScore.value,
    });

    // フォームをリセット
    response.value = '';
    hasAnswered.value = false;
    selectedScore.value = null;
}

function getScoreLabel(score) {
    switch(score) {
        case 0: return '不正解または見当違い';
        case 1: return '部分的に正解';
        case 2: return '完全に正解';
        default: return '';
    }
}
</script>
