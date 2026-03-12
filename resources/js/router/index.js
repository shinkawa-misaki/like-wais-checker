import { createRouter, createWebHashHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import DisclaimerView from '../views/DisclaimerView.vue';
import SubtestView from '../views/SubtestView.vue';
import ConditionCheckView from '../views/ConditionCheckView.vue';
import ReportView from '../views/ReportView.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeView,
    },
    {
        path: '/disclaimer',
        name: 'disclaimer',
        component: DisclaimerView,
    },
    {
        path: '/subtest/:subtestType',
        name: 'subtest',
        component: SubtestView,
        props: true,
    },
    {
        path: '/condition-check',
        name: 'condition-check',
        component: ConditionCheckView,
    },
    {
        path: '/report',
        name: 'report',
        component: ReportView,
    },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

export default router;
