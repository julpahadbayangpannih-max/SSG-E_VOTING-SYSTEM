import './bootstrap';
import Alpine from 'alpinejs';

// Shared dark-mode toggle: used by the voter dashboard and the public
// /verify page. Persisted per-browser via localStorage; falls back to the
// visitor's OS preference the first time they show up so nobody has to
// manually pick a mode that already matches their system.
Alpine.data('darkMode', () => ({
    dark: localStorage.getItem('jrmsu-theme')
        ? localStorage.getItem('jrmsu-theme') === 'dark'
        : window.matchMedia('(prefers-color-scheme: dark)').matches,

    init() {
        this.apply();
    },

    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('jrmsu-theme', this.dark ? 'dark' : 'light');
        this.apply();
    },

    apply() {
        document.documentElement.classList.toggle('dark', this.dark);
    },
}));

window.Alpine = Alpine;
Alpine.start();
