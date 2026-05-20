/**
 * Custom Select - Initialize all custom-select components on the page
 */

export function initCustomSelects(): void {
    document.querySelectorAll(".custom-select").forEach((select) => {
        const trigger = select.querySelector(".custom-select__trigger") as HTMLButtonElement;
        const hiddenInput = select.querySelector(
            "input[type='hidden']"
        ) as HTMLInputElement;
        const options = select.querySelectorAll(".custom-select__option");

        if (!trigger || !hiddenInput) return;

        // Toggle dropdown on trigger click
        trigger.addEventListener("click", () => {
            select.classList.toggle("custom-select--open");
            trigger.setAttribute(
                "aria-expanded",
                select.classList.contains("custom-select--open") ? "true" : "false"
            );
        });

        // Handle option selection
        options.forEach((option) => {
            option.addEventListener("click", () => {
                const value = option.getAttribute("data-value");
                const label = option.textContent;

                if (!value || !label || !trigger.firstChild) return;

                // Update hidden input value
                hiddenInput.value = value;

                // Update trigger text (first text node of trigger)
                trigger.firstChild.textContent = label;

                // Close dropdown
                select.classList.remove("custom-select--open");
                trigger.setAttribute("aria-expanded", "false");
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", (e) => {
            if (!select.contains(e.target as Node)) {
                select.classList.remove("custom-select--open");
                trigger.setAttribute("aria-expanded", "false");
            }
        });

        // Close dropdown on Escape key
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                select.classList.remove("custom-select--open");
                trigger.setAttribute("aria-expanded", "false");
            }
        });
    });
}

// Auto-initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCustomSelects);
} else {
    initCustomSelects();
}

