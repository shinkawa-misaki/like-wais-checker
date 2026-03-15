import { defineStore } from 'pinia';
import { assessmentApi } from '../api/assessment.js';

/** セクション実施順（4資源モデル） */
export const SUBTEST_ORDER = ['A', 'B', 'C', 'D'];

/** セクションのメタ情報 */
export const SUBTEST_META = {
    A: { label: '言語整理', index: 'VCI', icon: '🔤' },
    B: { label: '構造理解', index: 'PRI', icon: '🔲' },
    C: { label: '保持操作', index: 'WMI', icon: '🧠' },
    D: { label: '速度耐性', index: 'PSI', icon: '⚡' },
};

export const INDEX_META = {
    VCI: { label: '言語整理', color: 'blue', subtests: ['A'] },
    PRI: { label: '構造理解', color: 'green', subtests: ['B'] },
    WMI: { label: '保持操作', color: 'purple', subtests: ['C'] },
    PSI: { label: '速度耐性', color: 'orange', subtests: ['D'] },
};

export const useAssessmentStore = defineStore('assessment', {
    state: () => ({
        assessmentId: sessionStorage.getItem('assessmentId') || null,
        completedSubtests: JSON.parse(sessionStorage.getItem('completedSubtests') || '[]'),
        condition: JSON.parse(sessionStorage.getItem('condition') || 'null'),
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
                const response = await assessmentApi.getReport(this.assessmentId, this.condition);
                return response.data.data;
            } catch (e) {
                this.error = 'レポートの取得に失敗しました。';
                throw e;
            } finally {
                this.loading = false;
            }
        },

        setCondition(condition) {
            this.condition = condition;
            sessionStorage.setItem('condition', JSON.stringify(condition));
        },

        reset() {
            this.assessmentId = null;
            this.completedSubtests = [];
            this.condition = null;
            this.error = null;
            sessionStorage.removeItem('assessmentId');
            sessionStorage.removeItem('completedSubtests');
            sessionStorage.removeItem('condition');
        },
    },
});
