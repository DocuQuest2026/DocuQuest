{{-- Applies the saved theme (light, dark or follow the device) before the page paints, so there is no flash. --}}
<script data-navigate-once>
    (function () {
        var mode = null;
        try { mode = localStorage.getItem('theme'); } catch (e) {}
        var media = window.matchMedia('(prefers-color-scheme: dark)');

        function apply() {
            var dark = mode === 'dark' || (mode !== 'light' && media.matches);
            document.documentElement.classList.toggle('dark', dark);
        }

        window.setTheme = function (value) {
            mode = value === 'system' ? null : value;
            try { mode ? localStorage.setItem('theme', mode) : localStorage.removeItem('theme'); } catch (e) {}
            apply();
        };
        window.getTheme = function () { return mode || 'system'; };

        media.addEventListener('change', apply);

        // Page changes (wire:navigate) swap in the new page's <html> attributes, which drops the
        // class, so apply the theme again every time a navigation finishes.
        document.addEventListener('livewire:navigated', apply);

        apply();
    })();
</script>
