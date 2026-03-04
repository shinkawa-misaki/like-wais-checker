<template>
  <div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs font-semibold text-gray-400">問 {{ question.sequenceNumber }}</span>
      </div>
      <p class="text-gray-800 font-medium whitespace-pre-line">{{ question.content }}</p>
    </div>

    <div class="grid grid-cols-2 gap-2">
      <button
        v-for="(label, key) in question.options"
        :key="key"
        @click="select(key)"
        :class="[
          'py-3 px-4 rounded-xl border-2 text-left transition-all',
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
import { ref } from 'vue';

const props = defineProps({
    question: { type: Object, required: true },
});

const emit = defineEmits(['answered']);

const selected = ref(null);

function select(key) {
    selected.value = key;
}

function confirm() {
    if (!selected.value) return;
    emit('answered', {
        question_id: props.question.id,
        response: selected.value,
        awarded_score: null,
    });
    selected.value = null;
}
</script>
