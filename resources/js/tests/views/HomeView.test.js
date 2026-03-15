import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createRouter, createMemoryHistory } from 'vue-router';
import { createPinia, setActivePinia } from 'pinia';
import HomeView from '@/views/HomeView.vue';
import { useAssessmentStore } from '@/stores/assessment.js';

vi.mock('@/api/assessment.js');

function makeWrapper(storeOverrides = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);

    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/', name: 'home', component: HomeView },
            { path: '/disclaimer', name: 'disclaimer', component: { template: '<div>Disclaimer</div>' } },
            { path: '/subtest/:subtestType', name: 'subtest', component: { template: '<div>Subtest</div>' } },
        ],
    });

    const store = useAssessmentStore();
    Object.assign(store, storeOverrides);

    return mount(HomeView, {
        global: {
            plugins: [pinia, router],
            stubs: { RouterView: true },
        },
    });
}

describe('HomeView', () => {
    beforeEach(() => {
        sessionStorage.clear();
    });

    it('タイトルが表示される', () => {
        const wrapper = makeWrapper();
        expect(wrapper.text()).toContain('認知の手すりチェック Lite');
    });

    it('4つの指数カードが表示される', () => {
        const wrapper = makeWrapper();
        expect(wrapper.text()).toContain('VCI');
        expect(wrapper.text()).toContain('PRI');
        expect(wrapper.text()).toContain('WMI');
        expect(wrapper.text()).toContain('PSI');
    });

    it('「検査を始める」ボタンが表示される', () => {
        const wrapper = makeWrapper();
        expect(wrapper.text()).toContain('検査を始める');
    });

    it('保存済みセッションがない場合「再開」リンクは表示されない', () => {
        const wrapper = makeWrapper({ assessmentId: null, completedSubtests: [] });
        expect(wrapper.text()).not.toContain('前回の続きから再開する');
    });

    it('進行中セッションがある場合「再開」リンクが表示される', () => {
        const wrapper = makeWrapper({
            assessmentId: 'saved-id',
            completedSubtests: ['A', 'B'],
        });
        expect(wrapper.text()).toContain('前回の続きから再開する');
    });

    it('全完了セッションは「再開」リンクを表示しない', () => {
        const wrapper = makeWrapper({
            assessmentId: 'done-id',
            completedSubtests: ['A', 'B', 'C', 'D'],
        });
        expect(wrapper.text()).not.toContain('前回の続きから再開する');
    });

    it('所要時間の目安が表示される', () => {
        const wrapper = makeWrapper();
        expect(wrapper.text()).toContain('10〜20分');
    });

    it('「検査を始める」クリックでstoreがリセットされdisclaimerへ遷移する', async () => {
        const pinia = createPinia();
        setActivePinia(pinia);
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [
                { path: '/', name: 'home', component: HomeView },
                { path: '/disclaimer', name: 'disclaimer', component: { template: '<div />' } },
            ],
        });

        const store = useAssessmentStore();
        store.assessmentId = 'old-id';
        const resetSpy = vi.spyOn(store, 'reset');

        const wrapper = mount(HomeView, {
            global: { plugins: [pinia, router] },
        });
        await router.isReady();

        await wrapper.find('button').trigger('click');
        expect(resetSpy).toHaveBeenCalled();
    });
});
