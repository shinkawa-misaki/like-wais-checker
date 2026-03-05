<template>
  <div class="space-y-6">
    <!-- 問題番号と説明 -->
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xs font-semibold text-gray-400">問 {{ question.sequenceNumber }}</span>
        <span class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded-full font-semibold">
          マトリクス推論
        </span>
      </div>

      <!-- 問題の説明文 -->
      <p class="text-gray-800 font-medium mb-4">{{ getDescription() }}</p>

      <!-- マトリクス表示エリア -->
      <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-6 border border-gray-300">
        <div class="font-mono text-sm leading-relaxed whitespace-pre-line text-gray-800">
          {{ getPattern() }}
        </div>
      </div>
    </div>

    <!-- ヒント表示（もしあれば） -->
    <div v-if="question.hint" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-start gap-2">
        <span class="text-blue-600 text-lg">💡</span>
        <div>
          <p class="text-sm font-semibold text-blue-800 mb-1">ヒント</p>
          <p class="text-sm text-blue-700">{{ question.hint }}</p>
        </div>
      </div>
    </div>

    <!-- 選択肢 -->
    <div class="space-y-2">
      <p class="text-sm font-semibold text-gray-700 mb-3">空欄（?）に入る図形を選んでください：</p>
      <div class="grid grid-cols-1 gap-3">
        <button
          v-for="(label, key) in question.options"
          :key="key"
          @click="select(key)"
          class="p-4 rounded-xl border-2 transition-all text-left flex items-center gap-3"
          :class="[
            selected === key
              ? 'border-purple-500 bg-purple-50 text-purple-800 font-semibold shadow-md'
              : 'border-gray-200 text-gray-700 hover:border-purple-300 hover:bg-purple-50/50 bg-white'
          ]"
        >
          <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full font-bold text-lg"
                :class="selected === key ? 'bg-purple-500 text-white' : 'bg-gray-200 text-gray-600'">
            {{ key }}
          </span>
          <span class="text-base">{{ label }}</span>
        </button>
      </div>
    </div>

    <!-- 確定ボタン -->
    <button
      @click="confirm"
      :disabled="!selected"
      class="w-full py-4 bg-purple-600 text-white rounded-xl font-semibold text-lg
             hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed
             transition-all shadow-lg hover:shadow-xl disabled:shadow-none"
    >
      <span v-if="selected">選択肢 {{ selected }} で確定して次へ →</span>
      <span v-else>選択肢を選んでください</span>
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

function getDescription() {
  // contentから説明部分を抽出（最初の行または最初の改行まで）
  const content = props.question.content || '';
  const lines = content.split('\n');
  return lines[0];
}

function getPattern() {
  // contentからマトリクスパターン部分を抽出
  const content = props.question.content || '';
  const lines = content.split('\n');
  // 最初の行（説明）以外を返す
  return lines.slice(1).join('\n').trim();
}

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

<style scoped>
/* マトリクス表示を見やすくするスタイル */
.font-mono {
  font-family: 'Courier New', monospace;
}
</style>

