// resources/js/test/utils.js
import { mount, shallowMount } from "@vue/test-utils";
import { vi } from "vitest";

// Helper to create common mocks for components
export function createCommonMocks() {
    return {
        $router: {
            push: vi.fn(),
            replace: vi.fn(),
        },
        $route: {
            params: {},
            query: {},
            path: "/",
        },
        $notify: vi.fn(),
    };
}

// Helper to mock API services
export function mockApiService(methodName, returnValue) {
    return {
        [methodName]: vi.fn().mockResolvedValue(returnValue),
    };
}

// Helper to mount components with common configurations
export function mountComponent(Component, options = {}) {
    const { props = {}, mocks = {}, stubs = {}, shallow = false } = options;

    const commonMocks = createCommonMocks();

    const mountFunction = shallow ? shallowMount : mount;

    return mountFunction(Component, {
        props,
        global: {
            mocks: {
                ...commonMocks,
                ...mocks,
            },
            stubs: {
                ...stubs,
            },
        },
    });
}

// Helper to wait for component updates
export async function waitForComponentToUpdate(wrapper) {
    await wrapper.vm.$nextTick();
}
