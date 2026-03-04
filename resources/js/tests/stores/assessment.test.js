import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import {
    useAssessmentStore,
    SUBTEST_ORDER,
    SUBTEST_META,
    INDEX_META,
} from '@/stores/assessment.js';
import { assessmentApi } from '@/api/assessment.js';

vi.mock('@/api/assessment.js');

// ----- ヘルパー -----
function makeStore() {
    setActivePinia(createPinia());
    return useAssessmentStore();
}

// ----- 定数 -----
describe('SUBTEST_ORDER', () => {
    it('8サブテストが定義されている', () => {
        expect(SUBTEST_ORDER).toHaveLength(8);
        expect(SUBTEST_ORDER).toEqual(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
    });
});

describe('SUBTEST_META', () => {
    it('全サブテストにicon・index・labelが存在する', () => {
        for (const key of SUBTEST_ORDER) {
            expect(SUBTEST_META[key]).toHaveProperty('label');
            expect(SUBTEST_META[key]).toHaveProperty('index');
            expect(SUBTEST_META[key]).toHaveProperty('icon');
        }
    });

    it('VCIはA・B、PSIはG・Hに割り当てられている', () => {
        expect(SUBTEST_META['A'].index).toBe('VCI');
        expect(SUBTEST_META['B'].index).toBe('VCI');
        expect(SUBTEST_META['G'].index).toBe('PSI');
        expect(SUBTEST_META['H'].index).toBe('PSI');
    });
});

describe('INDEX_META', () => {
    it('4指数すべてが定義されている', () => {
        expect(Object.keys(INDEX_META)).toEqual(['VCI', 'PRI', 'WMI', 'PSI']);
    });

    it('各指数に2つのサブテストが紐付いている', () => {
        for (const meta of Object.values(INDEX_META)) {
            expect(meta.subtests).toHaveLength(2);
        }
    });
});

// ----- 初期状態 -----
describe('useAssessmentStore — 初期状態', () => {
    beforeEach(() => {
        sessionStorage.clear();
    });

    it('assessmentId は null', () => {
        const store = makeStore();
        expect(store.assessmentId).toBeNull();
    });

    it('completedSubtests は空配列', () => {
        const store = makeStore();
        expect(store.completedSubtests).toEqual([]);
    });

    it('loading は false', () => {
        const store = makeStore();
        expect(store.loading).toBe(false);
    });

    it('error は null', () => {
        const store = makeStore();
        expect(store.error).toBeNull();
    });
});

// ----- ゲッター -----
describe('useAssessmentStore — getters', () => {
    it('progress: 0%（未開始）', () => {
        const store = makeStore();
        expect(store.progress).toBe(0);
    });

    it('progress: 50%（4サブテスト完了）', () => {
        const store = makeStore();
        store.completedSubtests = ['A', 'B', 'C', 'D'];
        expect(store.progress).toBe(50);
    });

    it('progress: 100%（全完了）', () => {
        const store = makeStore();
        store.completedSubtests = [...SUBTEST_ORDER];
        expect(store.progress).toBe(100);
    });

    it('currentSubtest: 未開始はA', () => {
        const store = makeStore();
        expect(store.currentSubtest).toBe('A');
    });

    it('currentSubtest: A完了後はB', () => {
        const store = makeStore();
        store.completedSubtests = ['A'];
        expect(store.currentSubtest).toBe('B');
    });

    it('currentSubtest: 全完了後は null', () => {
        const store = makeStore();
        store.completedSubtests = [...SUBTEST_ORDER];
        expect(store.currentSubtest).toBeNull();
    });

    it('isComplete: 8サブテスト完了でtrue', () => {
        const store = makeStore();
        store.completedSubtests = [...SUBTEST_ORDER];
        expect(store.isComplete).toBe(true);
    });

    it('isComplete: 7サブテスト完了でfalse', () => {
        const store = makeStore();
        store.completedSubtests = SUBTEST_ORDER.slice(0, 7);
        expect(store.isComplete).toBe(false);
    });

    it('currentSubtestIndex: completedSubtests.lengthと一致する', () => {
        const store = makeStore();
        store.completedSubtests = ['A', 'B', 'C'];
        expect(store.currentSubtestIndex).toBe(3);
    });
});

// ----- アクション: startAssessment -----
describe('useAssessmentStore — startAssessment', () => {
    it('成功時: assessmentId がセットされ loading が false に戻る', async () => {
        assessmentApi.start.mockResolvedValueOnce({ data: { data: { id: 'test-uuid-123' } } });
        const store = makeStore();
        await store.startAssessment();
        expect(store.assessmentId).toBe('test-uuid-123');
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
    });

    it('成功時: sessionStorage に assessmentId が保存される', async () => {
        assessmentApi.start.mockResolvedValueOnce({ data: { data: { id: 'sess-456' } } });
        const store = makeStore();
        await store.startAssessment();
        expect(sessionStorage.setItem).toHaveBeenCalledWith('assessmentId', 'sess-456');
    });

    it('成功時: completedSubtests がリセットされる', async () => {
        assessmentApi.start.mockResolvedValueOnce({ data: { data: { id: 'x' } } });
        const store = makeStore();
        store.completedSubtests = ['A', 'B'];
        await store.startAssessment();
        expect(store.completedSubtests).toEqual([]);
    });

    it('失敗時: error がセットされ例外が再スローされる', async () => {
        assessmentApi.start.mockRejectedValueOnce(new Error('Network Error'));
        const store = makeStore();
        await expect(store.startAssessment()).rejects.toThrow();
        expect(store.error).toBe('アセスメントの開始に失敗しました。');
        expect(store.loading).toBe(false);
    });
});

// ----- アクション: fetchQuestions -----
describe('useAssessmentStore — fetchQuestions', () => {
    const mockQuestions = { questions: [{ id: 1, content: 'テスト問題' }], timeLimitSeconds: null };

    it('成功時: 問題データを返す', async () => {
        assessmentApi.getQuestions.mockResolvedValueOnce({ data: { data: mockQuestions } });
        const store = makeStore();
        store.assessmentId = 'aid-1';
        const result = await store.fetchQuestions('A');
        expect(result).toEqual(mockQuestions);
        expect(assessmentApi.getQuestions).toHaveBeenCalledWith('aid-1', 'A');
    });

    it('失敗時: error がセットされる', async () => {
        assessmentApi.getQuestions.mockRejectedValueOnce(new Error('404'));
        const store = makeStore();
        await expect(store.fetchQuestions('A')).rejects.toThrow();
        expect(store.error).toBe('問題の取得に失敗しました。');
    });
});

// ----- アクション: submitAnswers -----
describe('useAssessmentStore — submitAnswers', () => {
    const mockResponse = { completedSubtests: ['A'] };

    it('成功時: completedSubtests が更新される', async () => {
        assessmentApi.submitAnswers.mockResolvedValueOnce({ data: { data: mockResponse } });
        const store = makeStore();
        store.assessmentId = 'aid-1';
        await store.submitAnswers('A', [{ question_id: 1, response: 'テスト', awarded_score: 2 }]);
        expect(store.completedSubtests).toEqual(['A']);
    });

    it('成功時: sessionStorage が更新される', async () => {
        assessmentApi.submitAnswers.mockResolvedValueOnce({ data: { data: mockResponse } });
        const store = makeStore();
        store.assessmentId = 'aid-1';
        await store.submitAnswers('A', []);
        expect(sessionStorage.setItem).toHaveBeenCalledWith(
            'completedSubtests',
            JSON.stringify(['A'])
        );
    });

    it('elapsedSeconds が API に渡される', async () => {
        assessmentApi.submitAnswers.mockResolvedValueOnce({ data: { data: mockResponse } });
        const store = makeStore();
        store.assessmentId = 'aid-1';
        await store.submitAnswers('G', [], 45);
        expect(assessmentApi.submitAnswers).toHaveBeenCalledWith('aid-1', 'G', [], 45);
    });

    it('失敗時: error がセットされる', async () => {
        assessmentApi.submitAnswers.mockRejectedValueOnce(new Error('500'));
        const store = makeStore();
        await expect(store.submitAnswers('A', [])).rejects.toThrow();
        expect(store.error).toBe('回答の送信に失敗しました。');
    });
});

// ----- アクション: fetchReport -----
describe('useAssessmentStore — fetchReport', () => {
    it('成功時: レポートデータを返す', async () => {
        const mockReport = { indexScores: [], strategies: {} };
        assessmentApi.getReport.mockResolvedValueOnce({ data: { data: mockReport } });
        const store = makeStore();
        store.assessmentId = 'aid-1';
        const result = await store.fetchReport();
        expect(result).toEqual(mockReport);
    });

    it('失敗時: error がセットされる', async () => {
        assessmentApi.getReport.mockRejectedValueOnce(new Error('500'));
        const store = makeStore();
        await expect(store.fetchReport()).rejects.toThrow();
        expect(store.error).toBe('レポートの取得に失敗しました。');
    });
});

// ----- アクション: reset -----
describe('useAssessmentStore — reset', () => {
    it('全状態がクリアされる', () => {
        const store = makeStore();
        store.assessmentId = 'abc';
        store.completedSubtests = ['A', 'B'];
        store.error = 'some error';
        store.reset();
        expect(store.assessmentId).toBeNull();
        expect(store.completedSubtests).toEqual([]);
        expect(store.error).toBeNull();
    });

    it('sessionStorage から削除される', () => {
        const store = makeStore();
        store.reset();
        expect(sessionStorage.removeItem).toHaveBeenCalledWith('assessmentId');
        expect(sessionStorage.removeItem).toHaveBeenCalledWith('completedSubtests');
    });
});
