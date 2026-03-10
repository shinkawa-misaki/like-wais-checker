import { defineStore } from 'pinia';
import { assessmentApi } from '../api/assessment.js';

/** サブテストの実施順 */
export const SUBTEST_ORDER = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

/** サブテストのメタ情報 */
export const SUBTEST_META = {
    A: { label: '類似（Similarities）', index: 'VCI', icon: '🔤' },
    B: { label: '語彙（Vocabulary）', index: 'VCI', icon: '📖' },
    C: { label: '規則発見（Pattern Recognition）', index: 'PRI', icon: '🔢' },
    D: { label: '簡易マトリクス（Matrix Reasoning）', index: 'PRI', icon: '🔲' },
    E: { label: '数唱（Digit Span）', index: 'WMI', icon: '🔢' },
    F: { label: '暗算（Arithmetic）', index: 'WMI', icon: '🧮' },
    G: { label: '探索（Symbol Search）', index: 'PSI', icon: '🔍' },
    H: { label: '符号化（Coding）', index: 'PSI', icon: '⌨️' },
};

export const INDEX_META = {
    VCI: { label: '言語理解', color: 'blue', subtests: ['A', 'B'] },
    PRI: { label: '知覚推理', color: 'green', subtests: ['C', 'D'] },
    WMI: { label: 'ワーキングメモリー', color: 'purple', subtests: ['E', 'F'] },
    PSI: { label: '処理速度', color: 'orange', subtests: ['G', 'H'] },
};

export const useAssessmentStore = defineStore('assessment', {
    state: () => ({
        assessmentId: sessionStorage.getItem('assessmentId') || null,
        completedSubtests: JSON.parse(sessionStorage.getItem('completedSubtests') || '[]'),
        loading: false,
        error: null,
    }),

    getters: {
        currentSubtestIndex(state) {
            return state.completedSubtests.length;
        },

        currentSubtest(state) {
            return SUBTEST_ORDER[state.completedSubtests.length] || null;
        },

        isComplete(state) {
            return state.completedSubtests.length >= SUBTEST_ORDER.length;
        },

        progress(state) {
            return Math.round((state.completedSubtests.length / SUBTEST_ORDER.length) * 100);
        },
    },

    actions: {
        async startAssessment() {
            this.loading = true;
            this.error = null;
            try {
                const response = await assessmentApi.start();
                const data = response.data.data;
                this.assessmentId = data.id;
                this.completedSubtests = [];
                sessionStorage.setItem('assessmentId', data.id);
                sessionStorage.setItem('completedSubtests', '[]');
                return data;
            } catch (e) {
                this.error = 'アセスメントの開始に失敗しました。';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async fetchQuestions(subtestType) {
            this.loading = true;
            this.error = null;
            try {
                const response = await assessmentApi.getQuestions(this.assessmentId, subtestType);
                console.log('Fetched questions:', response.data.data);
                return response.data.data;
            } catch (e) {
                console.error('Fetch questions error:', e);
                this.error = '問題の取得に失敗しました。';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async saveAnswer(subtestType, answer) {
            try {
                const response = await assessmentApi.saveAnswer(this.assessmentId, subtestType, answer);
                return response.data;
            } catch (e) {
                console.error('Save answer error:', e);
                if (e.response?.status === 422 && e.response?.data?.error?.includes('Assessment not found')) {
                    this.reset();
                }
                throw e;
            }
        },

        async submitAnswers(subtestType, answers, elapsedSeconds = null) {
            this.loading = true;
            this.error = null;
            try {
                console.log('Submitting answers:', { assessmentId: this.assessmentId, subtestType, answers, elapsedSeconds });
                const response = await assessmentApi.submitAnswers(
                    this.assessmentId,
                    subtestType,
                    answers,
                    elapsedSeconds
                );
                const data = response.data.data;
                this.completedSubtests = data.completedSubtests;
                sessionStorage.setItem('completedSubtests', JSON.stringify(data.completedSubtests));
                return data;
            } catch (e) {
                console.error('Submit answers error:', e);
                console.error('Error response:', e.response?.data);

                // より詳細なエラーメッセージを表示
                if (e.response?.data?.error) {
                    this.error = `回答の送信に失敗しました: ${e.response.data.error}`;
                } else if (e.response?.data?.message) {
                    this.error = `回答の送信に失敗しました: ${e.response.data.message}`;
                } else {
                    this.error = '回答の送信に失敗しました。';
                }
                throw e;
            } finally {
                this.loading = false;
            }
        },

        async fetchReport() {
            this.loading = true;
            this.error = null;
            try {
                const response = await assessmentApi.getReport(this.assessmentId);
                return response.data.data;
            } catch (e) {
                this.error = 'レポートの取得に失敗しました。';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        reset() {
            this.assessmentId = null;
            this.completedSubtests = [];
            this.error = null;
            sessionStorage.removeItem('assessmentId');
            sessionStorage.removeItem('completedSubtests');
        },
    },
});
