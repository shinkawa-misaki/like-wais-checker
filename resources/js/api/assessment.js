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

    /** 1問ずつ回答を保存する */
    saveAnswer(assessmentId, subtestType, answer) {
        return api.post(`/assessments/${assessmentId}/subtests/${subtestType}/answer`, answer);
    },

    /** サブテスト完了（一括送信 or 完了マーク） */
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
