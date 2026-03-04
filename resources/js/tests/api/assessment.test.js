import { describe, it, expect, vi, beforeEach } from 'vitest';

// axios をモック（モジュール全体）
vi.mock('axios', () => {
    const instance = {
        get: vi.fn(),
        post: vi.fn(),
        defaults: { headers: { common: {} } },
        interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
    };
    return {
        default: {
            create: vi.fn(() => instance),
        },
        __instance: instance,
    };
});

// テスト用に axios インスタンスを取得
import axios from 'axios';
const axiosInstance = axios.create();

// api モジュールをインポート（axios モック後に必ず行う）
const { assessmentApi } = await import('@/api/assessment.js?t=' + Date.now());

describe('assessmentApi', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('start()', () => {
        it('POST /api/assessments を呼ぶ', async () => {
            axiosInstance.post.mockResolvedValueOnce({ data: { data: { id: 'uuid-1' } } });
            const result = await assessmentApi.start();
            expect(axiosInstance.post).toHaveBeenCalledWith('/assessments');
            expect(result.data.data.id).toBe('uuid-1');
        });
    });

    describe('getQuestions()', () => {
        it('GET /api/assessments/:id/subtests/:type/questions を呼ぶ', async () => {
            axiosInstance.get.mockResolvedValueOnce({ data: { data: [] } });
            await assessmentApi.getQuestions('uuid-1', 'A');
            expect(axiosInstance.get).toHaveBeenCalledWith(
                '/assessments/uuid-1/subtests/A/questions'
            );
        });
    });

    describe('submitAnswers()', () => {
        it('POST /api/assessments/:id/subtests/:type/answers を呼ぶ', async () => {
            axiosInstance.post.mockResolvedValueOnce({ data: { data: {} } });
            const answers = [{ question_id: 1, response: 'test', awarded_score: 2 }];
            await assessmentApi.submitAnswers('uuid-1', 'A', answers, 120);
            expect(axiosInstance.post).toHaveBeenCalledWith(
                '/assessments/uuid-1/subtests/A/answers',
                { answers, elapsed_seconds: 120 }
            );
        });

        it('elapsedSeconds が null でも送信できる', async () => {
            axiosInstance.post.mockResolvedValueOnce({ data: { data: {} } });
            await assessmentApi.submitAnswers('uuid-1', 'B', [], null);
            expect(axiosInstance.post).toHaveBeenCalledWith(
                '/assessments/uuid-1/subtests/B/answers',
                { answers: [], elapsed_seconds: null }
            );
        });
    });

    describe('getReport()', () => {
        it('GET /api/assessments/:id/report を呼ぶ', async () => {
            axiosInstance.get.mockResolvedValueOnce({ data: { data: {} } });
            await assessmentApi.getReport('uuid-1');
            expect(axiosInstance.get).toHaveBeenCalledWith('/assessments/uuid-1/report');
        });
    });
});
