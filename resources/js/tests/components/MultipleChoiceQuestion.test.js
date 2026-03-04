import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import MultipleChoiceQuestion from '@/components/MultipleChoiceQuestion.vue';

const baseQuestion = {
    id: 2,
    sequenceNumber: 3,
    content: '次のうち「植物」に分類されないものはどれですか？',
    questionType: 'multiple_choice',
    options: { A: 'バラ', B: '犬', C: 'サクラ', D: 'タンポポ' },
    maxPoints: 1,
};

describe('MultipleChoiceQuestion', () => {
    it('問題文が表示される', () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).toContain(baseQuestion.content);
    });

    it('シーケンス番号が表示される', () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).toContain('問 3');
    });

    it('4つの選択肢ボタンが表示される', () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).toContain('バラ');
        expect(wrapper.text()).toContain('犬');
        expect(wrapper.text()).toContain('サクラ');
        expect(wrapper.text()).toContain('タンポポ');
    });

    it('初期状態では「確定して次へ」ボタンが disabled', () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        expect(confirmBtn.attributes('disabled')).toBeDefined();
    });

    it('選択肢ボタンをクリックすると選択状態になる', async () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        const buttons = wrapper.findAll('button').filter(b => Object.values(baseQuestion.options).some(opt => b.text().includes(opt)));
        await buttons[1].trigger('click'); // 犬
        // border-blue-500 クラスが付与されていることを確認
        expect(buttons[1].classes()).toContain('border-blue-500');
    });

    it('選択後に「確定して次へ」が活性化する', async () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        const optionBtn = wrapper.findAll('button').find(b => b.text().includes('犬'));
        await optionBtn.trigger('click');
        const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        expect(confirmBtn.attributes('disabled')).toBeUndefined();
    });

    it('確定すると answered イベントが発火する（選択肢Bを選んだ場合）', async () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        await wrapper.findAll('button').find(b => b.text().includes('犬')).trigger('click');
        await wrapper.findAll('button').find(b => b.text().includes('確定して次へ')).trigger('click');
        expect(wrapper.emitted('answered')).toHaveLength(1);
        expect(wrapper.emitted('answered')[0][0]).toEqual({
            question_id: 2,
            response: 'B',
            awarded_score: null,
        });
    });

    it('確定後、選択状態がリセットされる', async () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        await wrapper.findAll('button').find(b => b.text().includes('犬')).trigger('click');
        await wrapper.findAll('button').find(b => b.text().includes('確定して次へ')).trigger('click');
        // 「確定して次へ」が再び disabled に戻る
        const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        expect(confirmBtn.attributes('disabled')).toBeDefined();
    });

    it('別の選択肢に変更できる', async () => {
        const wrapper = mount(MultipleChoiceQuestion, { props: { question: baseQuestion } });
        const btnA = wrapper.findAll('button').find(b => b.text().includes('バラ'));
        const btnB = wrapper.findAll('button').find(b => b.text().includes('犬'));
        await btnA.trigger('click');
        await btnB.trigger('click');
        await wrapper.findAll('button').find(b => b.text().includes('確定して次へ')).trigger('click');
        expect(wrapper.emitted('answered')[0][0].response).toBe('B');
    });
});
