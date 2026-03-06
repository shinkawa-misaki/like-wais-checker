import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import FreeTextQuestion from '@/components/FreeTextQuestion.vue';

const baseQuestion = {
    id: 1,
    sequenceNumber: 1,
    content: '犬と猫はどのような点で似ていますか？',
    questionType: 'free_text',
    hint: '2点：哺乳動物・ペットなど抽象的カテゴリ\n1点：毛がある・4本足など具体的特徴\n0点：ズレた回答',
    maxPoints: 2,
};

describe('FreeTextQuestion', () => {
    // ─── 初期表示 ───────────────────────────────────────────────────

    it('問題文が表示される', () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).toContain(baseQuestion.content);
    });

    it('シーケンス番号が表示される', () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).toContain('問 1');
    });

    it('初期状態ではテキストエリアが表示される', () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        expect(wrapper.find('textarea').exists()).toBe(true);
    });

    it('初期状態では採点基準が表示されない', () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).not.toContain('採点基準');
    });

    it('回答未入力では「回答する」ボタンが disabled', () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        const btn = wrapper.find('button');
        expect(btn.attributes('disabled')).toBeDefined();
    });

    // ─── 回答入力→採点フェーズ移行 ──────────────────────────────────

    it('テキストを入力すると「回答する」ボタンが活性化する', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('どちらも哺乳動物です');
        const btn = wrapper.find('button');
        expect(btn.attributes('disabled')).toBeUndefined();
    });

    it('「回答する」ボタン押下で採点フェーズに移行する', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('どちらも哺乳動物です');
        await wrapper.find('button').trigger('click');
        expect(wrapper.text()).toContain('この回答に点数をつけてください');
        expect(wrapper.text()).toContain(baseQuestion.hint);
    });

    it('採点フェーズでは自分の回答が表示される', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('どちらも哺乳動物です');
        await wrapper.find('button').trigger('click');
        expect(wrapper.text()).toContain('どちらも哺乳動物です');
    });

    it('採点フェーズでは 0・1・2 の採点ボタンが表示される', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('回答');
        await wrapper.find('button').trigger('click');
        expect(wrapper.text()).toContain('0点');
        expect(wrapper.text()).toContain('1点');
        expect(wrapper.text()).toContain('2点');
    });

    // ─── 自己採点→confirmed イベント ──────────────────────────────

    it('スコア選択前は「確定して次へ」が disabled', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('回答');
        await wrapper.find('button').trigger('click');
        const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        expect(confirmBtn.attributes('disabled')).toBeDefined();
    });

    it('スコア2を選択して確定すると answered イベントが発火する', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('どちらも哺乳動物です');
        await wrapper.find('button').trigger('click');

        // 2点のラジオボタンをクリック
        const radioButtons = wrapper.findAll('input[type="radio"]');
        const radio2 = radioButtons.find(r => r.attributes('value') === '2');
        await radio2.setValue(true);

        // 確定
        const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        await confirmBtn.trigger('click');

        expect(wrapper.emitted('answered')).toHaveLength(1);
        expect(wrapper.emitted('answered')[0][0]).toEqual({
            question_id: 1,
            response: 'どちらも哺乳動物です',
            awarded_score: 2,
        });
    });

    it('スコア0を選択して確定できる', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('大きい動物');
        await wrapper.find('button').trigger('click');

        const radioButtons = wrapper.findAll('input[type="radio"]');
        const radio0 = radioButtons.find(r => r.attributes('value') === '0');
        await radio0.setValue(true);

        const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        await confirmBtn.trigger('click');

        const emitted = wrapper.emitted('answered')[0][0];
        expect(emitted.awarded_score).toBe(0);
    });

    it('確定後、フォームがリセットされて次の問題を受け付ける', async () => {
        const wrapper = mount(FreeTextQuestion, { props: { question: baseQuestion } });
        await wrapper.find('textarea').setValue('回答1');
        await wrapper.find('button').trigger('click');

        const radioButtons = wrapper.findAll('input[type="radio"]');
        const radio1 = radioButtons.find(r => r.attributes('value') === '1');
        await radio1.setValue(true);

        const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        await confirmBtn.trigger('click');

        // テキストエリアが再表示される
        expect(wrapper.find('textarea').exists()).toBe(true);
        expect(wrapper.find('textarea').element.value).toBe('');
    });
});
