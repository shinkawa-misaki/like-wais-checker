import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import { createPinia, setActivePinia } from 'pinia';
import DisclaimerView from '@/views/DisclaimerView.vue';
import { useAssessmentStore } from '@/stores/assessment.js';

vi.mock('@/api/assessment.js');

function makeWrapper() {
    const pinia = createPinia();
    setActivePinia(pinia);
    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/disclaimer', component: DisclaimerView },
            { path: '/subtest/:subtestType', component: { template: '<div />' } },
        ],
    });
    return {
        wrapper: mount(DisclaimerView, {
            global: { plugins: [pinia, router] },
        }),
        router,
        store: useAssessmentStore(),
    };
}

describe('DisclaimerView', () => {
    it('免責事項が表示される', () => {
        const { wrapper } = makeWrapper();
        expect(wrapper.text()).toContain('重要な注意');
        expect(wrapper.text()).toContain('WAIS');
    });

    it('5つのチェックボックスが表示される', () => {
        const { wrapper } = makeWrapper();
        const checkboxes = wrapper.findAll('input[type="checkbox"]');
        expect(checkboxes).toHaveLength(5);
    });

    it('全チェックなしでは「同意して開始する」ボタンが disabled', () => {
        const { wrapper } = makeWrapper();
        const btn = wrapper.findAll('button').find(b => b.text().includes('同意して開始する'));
        expect(btn.attributes('disabled')).toBeDefined();
    });

    it('全チェック後に「同意して開始する」ボタンが活性化する', async () => {
        const { wrapper } = makeWrapper();
        const checkboxes = wrapper.findAll('input[type="checkbox"]');
        for (const cb of checkboxes) {
            await cb.setValue(true);
        }
        const btn = wrapper.findAll('button').find(b => b.text().includes('同意して開始する'));
        expect(btn.attributes('disabled')).toBeUndefined();
    });

    it('1つでもチェック漏れがあるとボタンは disabled のまま', async () => {
        const { wrapper } = makeWrapper();
        const checkboxes = wrapper.findAll('input[type="checkbox"]');
        // 4つだけチェック
        for (let i = 0; i < 4; i++) {
            await checkboxes[i].setValue(true);
        }
        const btn = wrapper.findAll('button').find(b => b.text().includes('同意して開始する'));
        expect(btn.attributes('disabled')).toBeDefined();
    });

    it('開始ボタン押下で startAssessment が呼ばれる', async () => {
        const { wrapper, store } = makeWrapper();
        store.startAssessment = vi.fn().mockResolvedValueOnce({ id: 'new-id' });

        const checkboxes = wrapper.findAll('input[type="checkbox"]');
        for (const cb of checkboxes) {
            await cb.setValue(true);
        }
        const btn = wrapper.findAll('button').find(b => b.text().includes('同意して開始する'));
        await btn.trigger('click');
        expect(store.startAssessment).toHaveBeenCalledOnce();
    });

    it('VCI自由記述の注意書きが表示される', () => {
        const { wrapper } = makeWrapper();
        expect(wrapper.text()).toContain('自己採点');
    });

    it('「戻る」ボタンが表示される', () => {
        const { wrapper } = makeWrapper();
        expect(wrapper.text()).toContain('戻る');
    });
});
