import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import { createPinia, setActivePinia } from 'pinia';
import ReportView from '@/views/ReportView.vue';
import { useAssessmentStore } from '@/stores/assessment.js';

vi.mock('@/api/assessment.js');

const mockReport = {
    generatedAt: '2026-03-04 12:00:00',
    disclaimer: 'これは正式なWAIS検査ではありません。',
    indexScores: [
        { indexType: 'VCI', label: '言語理解', rawScore: 18, maxScore: 24, percentage: 75, level: 'やや高め' },
        { indexType: 'PRI', label: '知覚推理', rawScore: 14, maxScore: 24, percentage: 58, level: '平均的' },
        { indexType: 'WMI', label: 'ワーキングメモリー', rawScore: 10, maxScore: 20, percentage: 50, level: '平均的' },
        { indexType: 'PSI', label: '処理速度', rawScore: 20, maxScore: 30, percentage: 67, level: 'やや高め' },
    ],
    strengthIndices: [
        { indexType: 'VCI', label: '言語理解', percentage: 75, interpretation: '抽象的思考が得意です。' },
        { indexType: 'PSI', label: '処理速度', percentage: 67, interpretation: '情報処理が素早いです。' },
    ],
    weaknessIndices: [
        { indexType: 'WMI', label: 'ワーキングメモリー', percentage: 50, interpretation: '情報の保持に負荷がかかりやすいです。' },
    ],
    strategies: {
        strength1: '言語化してから行動する戦略が効果的です。',
        weakness1: 'チェックリストを活用してください。',
    },
    nextSteps: ['日記で言語化を練習する', '記憶術を使う'],
};

function makeWrapper(fetchReportResult = null, fetchReportError = null) {
    const pinia = createPinia();
    setActivePinia(pinia);
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/report', name: 'report', component: ReportView },
            { path: '/', name: 'home', component: { template: '<div />' } },
        ],
    });

    const store = useAssessmentStore();
    if (fetchReportError) {
        store.fetchReport = vi.fn().mockRejectedValueOnce(fetchReportError);
    } else {
        store.fetchReport = vi.fn().mockResolvedValueOnce(fetchReportResult ?? mockReport);
    }

    const wrapper = mount(ReportView, {
        global: { plugins: [pinia, router] },
    });
    return { wrapper, store, router };
}

describe('ReportView', () => {
    it('ローディング中はスピナーが表示される', () => {
        const { wrapper } = makeWrapper();
        expect(wrapper.find('.animate-spin').exists()).toBe(true);
    });

    it('レポート取得後にタイトルが表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('検査結果レポート');
    });

    it('免責事項が表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('これは正式なWAIS検査ではありません。');
    });

    it('4指数スコアがすべて表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('言語理解');
        expect(wrapper.text()).toContain('知覚推理');
        expect(wrapper.text()).toContain('ワーキングメモリー');
        expect(wrapper.text()).toContain('処理速度');
    });

    it('パーセンテージが表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('75%');
        expect(wrapper.text()).toContain('58%');
    });

    it('強み解釈が表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('抽象的思考が得意です。');
    });

    it('負荷ポイントが表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('情報の保持に負荷がかかりやすいです。');
    });

    it('戦略テキストが表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('言語化してから行動する戦略が効果的です。');
    });

    it('次の一手リストが表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('日記で言語化を練習する');
        expect(wrapper.text()).toContain('記憶術を使う');
    });

    it('エラー時にはエラーメッセージが表示される', async () => {
        const { wrapper } = makeWrapper(null, new Error('500'));
        await flushPromises();
        expect(wrapper.text()).toContain('レポートの取得に失敗しました');
    });

    it('「最初からやり直す」ボタンが表示される', async () => {
        const { wrapper } = makeWrapper();
        await flushPromises();
        expect(wrapper.text()).toContain('最初からやり直す');
    });

    it('「最初からやり直す」クリックでストアがリセットされる', async () => {
        const { wrapper, store } = makeWrapper();
        await flushPromises();
        store.reset = vi.fn();
        const restartBtn = wrapper.findAll('button').find(b => b.text().includes('最初からやり直す'));
        await restartBtn.trigger('click');
        expect(store.reset).toHaveBeenCalledOnce();
    });

    it('fetchReport はマウント時に1回だけ呼ばれる', async () => {
        const { store } = makeWrapper();
        await flushPromises();
        expect(store.fetchReport).toHaveBeenCalledOnce();
    });
});
