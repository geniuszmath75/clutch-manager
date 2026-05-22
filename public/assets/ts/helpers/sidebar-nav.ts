export {};

/**
 * Sidebar Nav — Mobile toggle
 * Handles hamburger open/close, overlay click and Escape key for the mobile drawer.
 * Requires: #sidebar-nav, #sidebar-overlay, #btn-nav-toggle, #nav-toggle-icon
 */

const nav     = document.getElementById('sidebar-nav')!;
const overlay = document.getElementById('sidebar-overlay')!;
const toggle  = document.getElementById('btn-nav-toggle') as HTMLButtonElement;
const icon    = document.getElementById('nav-toggle-icon')!;

function openNav(): void {
    nav.classList.add('is-open');
    overlay.classList.add('is-visible');
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close navigation');
    icon.className = 'fa-solid fa-xmark';
}

function closeNav(): void {
    nav.classList.remove('is-open');
    overlay.classList.remove('is-visible');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open navigation');
    icon.className = 'fa-solid fa-bars';
}

toggle.addEventListener('click', () => {
    nav.classList.contains('is-open') ? closeNav() : openNav();
});

overlay.addEventListener('click', closeNav);

document.addEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && nav.classList.contains('is-open')) closeNav();
});