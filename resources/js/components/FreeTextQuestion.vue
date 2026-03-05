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

    <!-- 回答入力 -->
    <textarea
      v-model="response"
      placeholder="回答を入力してください..."
      rows="3"
      class="w-full border border-gray-300 rounded-xl p-3 text-sm resize-none
             focus:outline-none focus:ring-2 focus:ring-blue-400"
    ></textarea>

    <button
      type="button"
      @click.prevent="handleSubmit"
      :disabled="!canSubmit"
      class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium
             hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
    >
      回答する
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    question: { type: Object, required: true },
});

const emit = defineEmits(['answered']);

const response = ref('');

const canSubmit = computed(() => {
    return response.value && response.value.trim().length > 0;
});

function handleSubmit() {
    const trimmedResponse = response.value.trim();

    if (!trimmedResponse) {
        return;
    }

    // 親コンポーネントに回答を送信
    emit('answered', {
        question_id: props.question.id,
        response: trimmedResponse,
    });
}
</script>
