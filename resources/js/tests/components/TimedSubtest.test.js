import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import TimedSubtest from '@/components/TimedSubtest.vue';

const makeQuestions = (count = 3, type = 'G') =>
    Array.from({ length: count }, (_, i) => ({
        id: i + 1,
        content: `問題${i + 1}`,
        questionType: 'time_based',
        sequenceNumber: i + 1,
    }));

describe('TimedSubtest', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.clearAllMocks();
    });

    // ─── 初期表示 ─────────────────────────────────────────────────

    it('マウント時に残り時間の初期値が表示される', () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(), timeLimitSeconds: 60, subtestType: 'G' },
        });
        expect(wrapper.text()).toContain('01:00');
    });

    it('マウント時に問題一覧が表示される', () => {
        const questions = makeQuestions(3);
        const wrapper = mount(TimedSubtest, {
            props: { questions, timeLimitSeconds: 60, subtestType: 'G' },
        });
        expect(wrapper.text()).toContain('問題1');
        expect(wrapper.text()).toContain('問題2');
        expect(wrapper.text()).toContain('問題3');
    });

    // ─── タイマー ────────────────────────────────────────────────

    it('マウント後、1秒ごとにカウントダウンする', async () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(), timeLimitSeconds: 60, subtestType: 'G' },
        });
        vi.advanceTimersByTime(5000);
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('00:55');
    });

    it('時間切れになると完了メッセージが表示される', async () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(), timeLimitSeconds: 3, subtestType: 'G' },
        });
        vi.advanceTimersByTime(4000);
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('時間になりました');
    });

    // ─── Symbol Search (G): ○×ボタン ─────────────────────────────

    it('G: ○・× ボタンが各問題に表示される', async () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(2, 'G'), timeLimitSeconds: 60, subtestType: 'G' },
        });
        expect(wrapper.text()).toContain('○ あり');
        expect(wrapper.text()).toContain('× なし');
    });

    it('G: ○を選択すると選択状態になる', async () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(1, 'G'), timeLimitSeconds: 60, subtestType: 'G' },
        });
        const circleBtn = wrapper.findAll('button').find(b => b.text().includes('○ あり'));
        await circleBtn.trigger('click');
        expect(circleBtn.classes()).toContain('border-green-500');
    });

    // ─── Coding (H): テキスト入力 ────────────────────────────────

    it('H: テキスト入力フィールドが表示される', async () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(2, 'H'), timeLimitSeconds: 120, subtestType: 'H' },
        });
        expect(wrapper.find('input').exists()).toBe(true);
    });

    // ─── 提出 ─────────────────────────────────────────────────────

    it('「回答を提出する」ボタン押下で submitted イベントが発火する', async () => {
        const questions = makeQuestions(2, 'G');
        const wrapper = mount(TimedSubtest, {
            props: { questions, timeLimitSeconds: 60, subtestType: 'G' },
        });

        // ○ を選択
        const circleBtn = wrapper.findAll('button').find(b => b.text().includes('○ あり'));
        await circleBtn.trigger('click');

        const submitBtn = wrapper.findAll('button').find(b => b.text().includes('回答を提出する'));
        await submitBtn.trigger('click');

        expect(wrapper.emitted('submitted')).toHaveLength(1);
        const emitted = wrapper.emitted('submitted')[0][0];
        expect(emitted).toHaveProperty('answers');
        expect(emitted).toHaveProperty('elapsedSeconds');
        expect(emitted.answers).toHaveLength(2);
    });

    it('回答済み件数が提出ボタンに反映される', async () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(3, 'G'), timeLimitSeconds: 60, subtestType: 'G' },
        });

        const circleBtn = wrapper.findAll('button').find(b => b.text().includes('○ あり'));
        await circleBtn.trigger('click');

        expect(wrapper.text()).toContain('1/3問回答済み');
    });

    it('未回答項目は空文字列で送信される', async () => {
        const questions = makeQuestions(2, 'G');
        const wrapper = mount(TimedSubtest, {
            props: { questions, timeLimitSeconds: 60, subtestType: 'G' },
        });
        const submitBtn = wrapper.findAll('button').find(b => b.text().includes('回答を提出する'));
        await submitBtn.trigger('click');
        const emitted = wrapper.emitted('submitted')[0][0];
        expect(emitted.answers[0].response).toBe('');
    });

    it('経過秒数が正しく送信される', async () => {
        const wrapper = mount(TimedSubtest, {
            props: { questions: makeQuestions(1, 'G'), timeLimitSeconds: 60, subtestType: 'G' },
        });
        vi.advanceTimersByTime(10000);
        await wrapper.vm.$nextTick();
        const submitBtn = wrapper.findAll('button').find(b => b.text().includes('回答を提出する'));
        await submitBtn.trigger('click');
        const emitted = wrapper.emitted('submitted')[0][0];
        expect(emitted.elapsedSeconds).toBe(10);
    });
});
