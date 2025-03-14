// resources/js/components/DateNavigation.spec.js
import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import DateNavigation from "./DateNavigation.vue";

describe("DateNavigation.vue", () => {
    it("renders the formatted date correctly", () => {
        const formattedDate = "2025年3月14日（金）";
        const wrapper = mount(DateNavigation, {
            props: {
                formattedDate,
            },
        });

        // Check if the date is displayed
        expect(wrapper.find("h2").text()).toBe(formattedDate);
    });

    it("emits previous-day event when previous button is clicked", async () => {
        const wrapper = mount(DateNavigation, {
            props: {
                formattedDate: "Today",
            },
        });

        // Find and click the previous day button
        await wrapper.findAll("button")[0].trigger("click");

        // Check if the event was emitted
        expect(wrapper.emitted()["previous-day"]).toBeTruthy();
        expect(wrapper.emitted()["previous-day"].length).toBe(1);
    });

    it("emits next-day event when next button is clicked", async () => {
        const wrapper = mount(DateNavigation, {
            props: {
                formattedDate: "Today",
            },
        });

        // Find and click the next day button
        await wrapper.findAll("button")[1].trigger("click");

        // Check if the event was emitted
        expect(wrapper.emitted()["next-day"]).toBeTruthy();
        expect(wrapper.emitted()["next-day"].length).toBe(1);
    });

    it("displays special date formats correctly", () => {
        // Test with "今日" (Today)
        let wrapper = mount(DateNavigation, {
            props: {
                formattedDate: "今日",
            },
        });
        expect(wrapper.find("h2").text()).toBe("今日");

        // Test with "明日" (Tomorrow)
        wrapper = mount(DateNavigation, {
            props: {
                formattedDate: "明日",
            },
        });
        expect(wrapper.find("h2").text()).toBe("明日");
    });
});
