<template>
  <div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs font-semibold text-gray-400">問 {{ question.sequenceNumber }}</span>
      </div>
      <p class="text-gray-800 font-medium whitespace-pre-line">{{ question.content }}</p>
    </div>

    <!-- 表示ボタン（数字を1秒ずつ表示するシミュレーション） -->
    <div v-if="!revealed" class="text-center">
      <p class="text-sm text-gray-500 mb-4">
        問題文を読んで、数字を記憶してから「回答する」を押してください
      </p>
      <button
        @click="reveal"
        class="w-full py-3 bg-gray-700 text-white rounded-xl font-medium hover:bg-gray-800 transition-colors"
      >
        回答する
      </button>
    </div>

    <template v-else>
      <input
        v-model="response"
        type="text"
        inputmode="numeric"
        placeholder="数字を続けて入力（例：4，7，3 → 473）"
        class="w-full border border-gray-300 rounded-xl p-3 text-lg text-center
               tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-400"
        @keyup.enter="confirm"
        autofocus
      >
      <p class="text-xs text-gray-400 text-center">
        スペースや区切り文字なしで連続して入力してください
      </p>
      <button
        @click="confirm"
        :disabled="!response.trim()"
        class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium
               hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
      >
        回答する（やり直し不可）
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
const revealed = ref(false);

function reveal() {
    revealed.value = true;
}

function confirm() {
    if (!response.value.trim()) return;
    emit('answered', {
        question_id: props.question.id,
        response: response.value.replace(/\s/g, ''),
    });
    response.value = '';
    revealed.value = false;
}
</script>
