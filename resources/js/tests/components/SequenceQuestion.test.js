import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import SequenceQuestion from '@/components/SequenceQuestion.vue';

const baseQuestion = {
    id: 5,
    sequenceNumber: 2,
    content: '次の数字を覚えてください：3 - 7 - 1 - 9',
    questionType: 'sequence',
    maxPoints: 1,
};

describe('SequenceQuestion', () => {
    it('問題文が表示される', () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).toContain(baseQuestion.content);
    });

    it('シーケンス番号が表示される', () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        expect(wrapper.text()).toContain('問 2');
    });

    it('初期状態では「回答する」ボタンが表示され入力フィールドは表示されない', () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        expect(wrapper.find('button').exists()).toBe(true);
        expect(wrapper.find('input').exists()).toBe(false);
    });

    it('「回答する」ボタン押下で入力フィールドが表示される', async () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        await wrapper.find('button').trigger('click');
        expect(wrapper.find('input').exists()).toBe(true);
    });

    it('入力前は提出ボタンが disabled', async () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        await wrapper.find('button').trigger('click');
        const submitBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        expect(submitBtn.attributes('disabled')).toBeDefined();
    });

    it('数字を入力すると提出ボタンが活性化する', async () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        await wrapper.find('button').trigger('click');
        await wrapper.find('input').setValue('3719');
        const submitBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        expect(submitBtn.attributes('disabled')).toBeUndefined();
    });

    it('回答すると answered イベントが発火する', async () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        await wrapper.find('button').trigger('click');
        await wrapper.find('input').setValue('3719');
        const submitBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        await submitBtn.trigger('click');
        expect(wrapper.emitted('answered')).toHaveLength(1);
        expect(wrapper.emitted('answered')[0][0]).toEqual({
            question_id: 5,
            response: '3719',
        });
    });

    it('スペースを含む入力はトリムされる', async () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        await wrapper.find('button').trigger('click');
        await wrapper.find('input').setValue('3 7 1 9');
        const submitBtn = wrapper.findAll('button').find(b => b.text().includes('確定して次へ'));
        await submitBtn.trigger('click');
        expect(wrapper.emitted('answered')[0][0].response).toBe('3719');
    });

    it('Enterキーで回答を送信できる', async () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        await wrapper.find('button').trigger('click');
        const input = wrapper.find('input');
        await input.setValue('3719');
        await input.trigger('keyup.enter');
        expect(wrapper.emitted('answered')).toHaveLength(1);
    });

    it('確定後、入力フィールドがリセットされる', async () => {
        const wrapper = mount(SequenceQuestion, { props: { question: baseQuestion } });
        await wrapper.find('button').trigger('click');
        await wrapper.find('input').setValue('3719');
        await wrapper.findAll('button').find(b => b.text().includes('確定して次へ')).trigger('click');
        // 初期状態に戻る
        expect(wrapper.find('input').exists()).toBe(false);
    });
});
