import type { PaginationMeta } from "./fetch-helpers.js";

export {};

/**
 * Pagination — reusable helper
 * Renders pagination state into standard DOM elements and wires prev/next buttons.
 *
 * Expected HTML:
 *   <nav id="pagination">
 *     <span id="pagination-info"></span>
 *     <div class="pagination-controls">
 *       <button id="btn-prev">Previous</button>
 *       <button id="btn-next">Next</button>
 *     </div>
 *   </nav>
 *
 * Usage:
 *   import { renderPagination, bindPaginationButtons } from "./pagination.js";
 *
 *   renderPagination(meta, (page) => fetchItems(page));
 *   bindPaginationButtons((page) => fetchItems(page), () => currentPage, () => currentMeta);
 */

const paginationEl = document.getElementById('pagination')!;
const paginInfo    = document.getElementById('pagination-info')!;
const btnPrev      = document.getElementById('btn-prev')! as HTMLButtonElement;
const btnNext      = document.getElementById('btn-next')! as HTMLButtonElement;

/**
 * Renders pagination info text and enables/disables prev/next buttons.
 */
export function renderPagination(meta: PaginationMeta | null): void {
    if (meta === null) {
        paginationEl.hidden = true;
        return;
    }

    paginationEl.hidden = false;
    paginInfo.textContent = `Showing ${meta.page} of ${meta.totalPages} (${meta.total} players)`;
    btnPrev.disabled = meta.page <= 1;
    btnNext.disabled = meta.page >= meta.totalPages;
}

/**
 * Wires prev/next buttons to a page-change callback.
 * @param onPageChange - called with the new page number
 * @param getPage      - getter for the current page
 * @param getMeta      - getter for the current PaginationMeta
 */
export function bindPaginationButtons(
    onPageChange: (page: number) => Promise<void>,
    getPage: () => number,
    getMeta: () => PaginationMeta | null
): void {
    btnPrev.addEventListener('click', async () => {
        const page = getPage();
        if (page > 1) await onPageChange(page - 1);
    });

    btnNext.addEventListener('click', async () => {
        const meta = getMeta();
        const page = getPage();
        if (meta && page < meta.totalPages) await onPageChange(page + 1);
    });
}