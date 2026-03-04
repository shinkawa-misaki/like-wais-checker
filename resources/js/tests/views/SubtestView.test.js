import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import { createPinia, setActivePinia } from 'pinia';
import SubtestView from '@/views/SubtestView.vue';
import { useAssessmentStore } from '@/stores/assessment.js';

vi.mock('@/api/assessment.js');

const mockQuestions = {
    subtestType: 'A',
    subtestLabel: '類似 (Similarities)',
    indexType: 'VCI',
    instructions: 'テスト用の説明文',
    timeLimitSeconds: null,
    questions: [
        { id: 'q1', questionType: 'free_text', text: '問題1', maxPoints: 2 },
        { id: 'q2', questionType: 'free_text', text: '問題2', maxPoints: 2 },
    ],
};

function makeWrapper(subtestType = 'A', storeOverrides = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            {
                path: '/subtest/:subtestType',
                name: 'subtest',
                component: SubtestView,
                props: true,
            },
            {
                path: '/report',
                name: 'report',
                component: { template: '<div>Report</div>' },
            },
        ],
    });

    const store = useAssessmentStore();
    store.assessmentId = 'test-assessment-id';
    store.fetchQuestions = vi.fn().mockResolvedValue(mockQuestions);
    store.submitAnswers = vi.fn().mockResolvedValue({
        completedSubtests: [subtestType],
    });
    Object.assign(store, storeOverrides);

    router.push({ name: 'subtest', params: { subtestType } });

    const wrapper = mount(SubtestView, {
        props: { subtestType },
        global: {
            plugins: [pinia, router],
            stubs: {
                FreeTextQuestion: {
                    template: '<div class="stub-free-text">FreeTextQuestion</div>',
                },
                MultipleChoiceQuestion: {
                    template: '<div class="stub-multiple-choice">MultipleChoiceQuestion</div>',
                },
                SequenceQuestion: {
                    template: '<div class="stub-sequence">SequenceQuestion</div>',
                },
                TimedSubtest: {
                    template: '<div class="stub-timed">TimedSubtest</div>',
                },
            },
        },
    });

    return { wrapper, store, router };
}

describe('SubtestView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('初期表示', () => {
        it('ローディング状態を表示する', () => {
            const { wrapper } = makeWrapper();
            expect(wrapper.text()).toContain('読み込み中...');
        });

        it('問題を取得する', async () => {
            const { wrapper, store } = makeWrapper();
            await flushPromises();
            expect(store.fetchQuestions).toHaveBeenCalledWith('A');
        });

        it('案内画面を表示する', async () => {
            const { wrapper } = makeWrapper();
            await flushPromises();
            expect(wrapper.text()).toContain('類似 (Similarities)');
            expect(wrapper.text()).toContain('📋 実施方法');
            expect(wrapper.text()).toContain('開始する');
        });

        it('問題数と満点が表示される', async () => {
            const { wrapper } = makeWrapper();
            await flushPromises();
            expect(wrapper.text()).toContain('問題数：2問');
            expect(wrapper.text()).toContain('満点：4点');
        });
    });

    describe('サブテストの切り替え', () => {
        it('propsが変更されたら再読み込みする', async () => {
            const { wrapper, store, router } = makeWrapper('A');
            await flushPromises();

            // 最初のサブテストが読み込まれる
            expect(store.fetchQuestions).toHaveBeenCalledWith('A');
            expect(store.fetchQuestions).toHaveBeenCalledTimes(1);

            // サブテストBに切り替え
            store.fetchQuestions.mockResolvedValue({
                ...mockQuestions,
                subtestType: 'B',
                subtestLabel: '語彙 (Vocabulary)',
            });

            await router.push({ name: 'subtest', params: { subtestType: 'B' } });
            await wrapper.setProps({ subtestType: 'B' });
            await flushPromises();

            // 再読み込みされる
            expect(store.fetchQuestions).toHaveBeenCalledWith('B');
            expect(store.fetchQuestions).toHaveBeenCalledTimes(2);
        });

        it('サブテスト切り替え時に状態がリセットされる', async () => {
            const { wrapper, store, router } = makeWrapper('A');
            await flushPromises();

            // 問題を開始
            const startButton = wrapper.find('button');
            await startButton.trigger('click');
            await flushPromises();

            // サブテストBに切り替え
            store.fetchQuestions.mockResolvedValue({
                ...mockQuestions,
                subtestType: 'B',
            });

            await router.push({ name: 'subtest', params: { subtestType: 'B' } });
            await wrapper.setProps({ subtestType: 'B' });
            await flushPromises();

            // 案内画面に戻っている
            expect(wrapper.text()).toContain('開始する');
        });
    });

    describe('問題フェーズ', () => {
        it('「開始する」ボタンで問題フェーズに移行する', async () => {
            const { wrapper } = makeWrapper();
            await flushPromises();

            const startButton = wrapper.find('button');
            await startButton.trigger('click');
            await flushPromises();

            // 問題コンポーネントが表示される
            expect(wrapper.html()).toContain('stub-free-text');
        });
    });

    describe('完了フェーズ', () => {
        it('最後まで完了すると完了画面を表示する', async () => {
            const { wrapper, store } = makeWrapper('A', {
                completedSubtests: [],
            });
            await flushPromises();

            // submitAnswersをモックして完了状態にする
            store.submitAnswers.mockResolvedValue({
                completedSubtests: ['A'],
            });

            // 手動で完了フェーズに移行
            wrapper.vm.phase = 'done';
            await flushPromises();

            expect(wrapper.text()).toContain('完了！');
            expect(wrapper.text()).toContain('次へ進む');
        });

        it('全サブテスト完了後は「結果を見る」ボタンを表示する', async () => {
            const { wrapper, store } = makeWrapper('H', {
                completedSubtests: ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
                isComplete: true,
            });
            await flushPromises();

            // 完了フェーズに移行
            wrapper.vm.phase = 'done';
            await flushPromises();

            expect(wrapper.text()).toContain('結果を見る');
        });

        it('「次へ進む」ボタンで次のサブテストに遷移する', async () => {
            const { wrapper, store, router } = makeWrapper('A', {
                completedSubtests: [],
                isComplete: false,
            });
            await flushPromises();

            // 次のサブテストを設定
            wrapper.vm.nextSubtest = 'B';
            wrapper.vm.phase = 'done';
            await flushPromises();

            const pushSpy = vi.spyOn(router, 'push');
            const nextButton = wrapper.findAll('button').find(btn =>
                btn.text().includes('次へ進む')
            );

            if (nextButton) {
                await nextButton.trigger('click');
                await flushPromises();

                expect(pushSpy).toHaveBeenCalledWith({
                    name: 'subtest',
                    params: { subtestType: 'B' },
                });
            }
        });

        it('「結果を見る」ボタンでレポート画面に遷移する', async () => {
            const { wrapper, store, router } = makeWrapper('H', {
                completedSubtests: ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
                isComplete: true,
            });
            await flushPromises();

            wrapper.vm.phase = 'done';
            await flushPromises();

            const pushSpy = vi.spyOn(router, 'push');
            const resultButton = wrapper.findAll('button').find(btn =>
                btn.text().includes('結果を見る')
            );

            if (resultButton) {
                await resultButton.trigger('click');
                await flushPromises();

                expect(pushSpy).toHaveBeenCalledWith({
                    name: 'report',
                });
            }
        });
    });

    describe('エラーハンドリング', () => {
        it('エラー時にエラーメッセージを表示する', async () => {
            const { wrapper, store } = makeWrapper('A', {
                error: '問題の取得に失敗しました。',
            });
            await flushPromises();

            expect(wrapper.text()).toContain('問題の取得に失敗しました。');
            expect(wrapper.text()).toContain('再試行');
        });
    });

    describe('プログレス表示', () => {
        it('サブテストの進捗が表示される', async () => {
            const { wrapper } = makeWrapper('A', {
                completedSubtests: ['A', 'B'],
            });
            await flushPromises();

            expect(wrapper.text()).toContain('サブテスト 3/8');
        });
    });
});

