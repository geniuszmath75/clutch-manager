/**
 * Custom Select — Initialize all custom-select components within a given root.
 *
 * @param root - Element to search within (defaults to document).
 *               Pass a specific container (e.g. an open <dialog>) to re-initialize
 *               only newly injected options without double-binding existing selects.
 */
export function initCustomSelects(root: Element | Document = document): void {
    root.querySelectorAll<HTMLElement>(".custom-select").forEach((select) => {
        // Skip selects that have already been initialized
        if (select.dataset['selectInit']) return;
        select.setAttribute('data-select-init', 'true');

        const trigger     = select.querySelector<HTMLButtonElement>(".custom-select__trigger");
        const hiddenInput = select.querySelector<HTMLInputElement>("input[type='hidden']");
        const dropdown    = select.querySelector<HTMLElement>(".custom-select__dropdown");

        if (!trigger || !hiddenInput || !dropdown) return;

        // Toggle dropdown on trigger click
        trigger.addEventListener("click", () => {
            select.classList.toggle("custom-select--open");
            trigger.setAttribute(
                "aria-expanded",
                select.classList.contains("custom-select--open") ? "true" : "false"
            );
        });

        // Handle option selection — use event delegation on the dropdown so dynamically
        // injected options are also caught without re-initializing the whole select
        dropdown.addEventListener("click", (e: Event) => {
            const option = (e.target as HTMLElement).closest<HTMLElement>(".custom-select__option");
            if (!option || option.hasAttribute("disabled")) return;

            const value = option.getAttribute("data-value") ?? '';
            const label = option.textContent?.trim() ?? '';

            hiddenInput.value = value;
            hiddenInput.dispatchEvent(new Event('change'));

            if (trigger.firstChild) {
                trigger.firstChild.textContent = label;
            }

            select.classList.remove("custom-select--open");
            trigger.setAttribute("aria-expanded", "false");
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", (e: MouseEvent) => {
            if (!select.contains(e.target as Node)) {
                select.classList.remove("custom-select--open");
                trigger.setAttribute("aria-expanded", "false");
            }
        });

        // Close dropdown on Escape key
        document.addEventListener("keydown", (e: KeyboardEvent) => {
            if (e.key === "Escape") {
                select.classList.remove("custom-select--open");
                trigger.setAttribute("aria-expanded", "false");
            }
        });
    });
}

// Auto-initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => initCustomSelects());
} else {
    initCustomSelects();
}

