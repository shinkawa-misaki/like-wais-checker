import { config } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, vi } from 'vitest';

// Pinia をテストごとに初期化
beforeEach(() => {
    setActivePinia(createPinia());
});

// sessionStorage をグローバルモック
const sessionStorageMock = (() => {
    let store = {};
    return {
        getItem: vi.fn((key) => store[key] ?? null),
        setItem: vi.fn((key, value) => { store[key] = String(value); }),
        removeItem: vi.fn((key) => { delete store[key]; }),
        clear: vi.fn(() => { store = {}; }),
    };
})();
Object.defineProperty(globalThis, 'sessionStorage', { value: sessionStorageMock });

// Vue Test Utils のグローバル設定
config.global.plugins = [];
