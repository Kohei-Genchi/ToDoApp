// resources/js/test/setup.js
import { beforeAll, afterAll, vi } from "vitest";

// Mock global variables and browser APIs
global.Laravel = {
    user: {
        id: 1,
        name: "Test User",
        email: "test@example.com",
    },
};

// Mock window.location
beforeAll(() => {
    Object.defineProperty(window, "location", {
        writable: true,
        value: { pathname: "/" },
    });

    // Mock axios
    vi.mock("axios", () => ({
        default: {
            get: vi.fn(() => Promise.resolve({ data: {} })),
            post: vi.fn(() => Promise.resolve({ data: {} })),
            put: vi.fn(() => Promise.resolve({ data: {} })),
            delete: vi.fn(() => Promise.resolve({ data: {} })),
            defaults: {
                headers: {
                    common: {},
                },
                withCredentials: true,
            },
            interceptors: {
                request: { use: vi.fn(), eject: vi.fn() },
                response: { use: vi.fn(), eject: vi.fn() },
            },
        },
    }));

    // Mock document.querySelector for CSRF token
    document.querySelector = vi.fn().mockImplementation((selector) => {
        if (selector === 'meta[name="csrf-token"]') {
            return { getAttribute: () => "test-csrf-token" };
        }
        return null;
    });
});

afterAll(() => {
    vi.clearAllMocks();
});
