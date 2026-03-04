<template>
  <div class="space-y-4">
    <!-- 問題 -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs font-semibold text-gray-400">問 {{ question.sequenceNumber }}</span>
        <span class="text-xs text-gray-300">/</span>
        <span class="text-xs text-gray-400">{{ question.questionType === 'free_text' ? '自由記述' : '' }}</span>
      </div>
      <p class="text-gray-800 font-medium whitespace-pre-line">{{ question.content }}</p>
    </div>

    <!-- 回答フェーズ -->
    <template v-if="!submitted">
      <textarea
        v-model="response"
        placeholder="回答を入力してください..."
        rows="3"
        class="w-full border border-gray-300 rounded-xl p-3 text-sm resize-none
               focus:outline-none focus:ring-2 focus:ring-blue-400"
      ></textarea>
      <button
        @click="submit"
        :disabled="!response.trim()"
        class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium
               hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        回答する
      </button>
    </template>

    <!-- 採点フェーズ -->
    <template v-else>
      <!-- 自分の回答 -->
      <div class="bg-gray-50 rounded-xl p-4 text-sm">
        <p class="text-xs text-gray-400 mb-1">あなたの回答</p>
        <p class="text-gray-800">{{ response }}</p>
      </div>

      <!-- 採点基準 -->
      <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm">
        <p class="font-semibold text-green-800 mb-2">採点基準</p>
        <p class="text-green-700 whitespace-pre-line">{{ question.hint }}</p>
      </div>

      <!-- 自己採点 -->
      <div>
        <p class="text-sm font-medium text-gray-700 mb-2">自己採点（採点基準を見て選択）</p>
        <div class="flex gap-2">
          <button
            v-for="score in scoreOptions"
            :key="score.value"
            @click="selectScore(score.value)"
            :class="[
              'flex-1 py-3 rounded-xl border-2 text-sm font-semibold transition-all',
              selectedScore === score.value
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-gray-200 text-gray-600 hover:border-gray-300'
            ]"
          >
            <span class="text-lg block">{{ score.value }}点</span>
            <span class="text-xs font-normal">{{ score.label }}</span>
          </button>
        </div>
      </div>

      <button
        @click="confirm"
        :disabled="selectedScore === null"
        class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium
               hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        確定して次へ
      </button>
    </template>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    question: { type: Object, required: true },
});

const emit = defineEmits(['answered']);

const response = ref('');
const submitted = ref(false);
const selectedScore = ref(null);

const scoreOptions = [
    { value: 0, label: 'ズレている' },
    { value: 1, label: '方向性はOK' },
    { value: 2, label: '抽象的に正確' },
];

function submit() {
    if (!response.value.trim()) return;
    submitted.value = true;
}

function selectScore(score) {
    selectedScore.value = score;
}

function confirm() {
    if (selectedScore.value === null) return;
    emit('answered', {
        question_id: props.question.id,
        response: response.value,
        awarded_score: selectedScore.value,
    });
    // reset for next question
    response.value = '';
    submitted.value = false;
    selectedScore.value = null;
}
</script>
