import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

export const assessmentApi = {
    /** アセスメントを開始する */
    start() {
        return api.post('/assessments');
    },

    /** サブテストの問題を取得する */
    getQuestions(assessmentId, subtestType) {
        return api.get(`/assessments/${assessmentId}/subtests/${subtestType}/questions`);
    },

    /** サブテストの回答を提出する */
    submitAnswers(assessmentId, subtestType, answers, elapsedSeconds = null) {
        return api.post(`/assessments/${assessmentId}/subtests/${subtestType}/answers`, {
            answers,
            elapsed_seconds: elapsedSeconds,
        });
    },

    /** 結果レポートを取得する */
    getReport(assessmentId) {
        return api.get(`/assessments/${assessmentId}/report`);
    },
};
