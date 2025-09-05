// SPA-style hash navigation: show/hide sections based on fragment

function showSectionFromHash() {
        // Get the current hash (without the #)
        const hash = window.location.hash ? window.location.hash.substring(1) : "home";
        // Find all page sections
        const sections = document.querySelectorAll('.page-section');
        let found = false;
        sections.forEach(section => {
                if (section.id === hash) {
                        section.hidden = false;
                        section.setAttribute('aria-hidden', 'false');
                        // Optionally scroll to the section (or to top)
                        section.scrollIntoView({ behavior: "instant" });
                        found = true;
                } else {
                        section.hidden = true;
                        section.setAttribute('aria-hidden', 'true');
                }
        });
        // Fallback to home if hash does not match any section
        if (!found) {
                const home = document.getElementById('home');
                if (home) {
                        home.hidden = false;
                        home.setAttribute('aria-hidden', 'false');
                        home.scrollIntoView({ behavior: "instant" });
                }
        }
}

// On page load
window.addEventListener('DOMContentLoaded', showSectionFromHash);
// On hash change (browser navigation)
window.addEventListener('hashchange', showSectionFromHash);

// OPTIONAL: Prevent default anchor jumps and close menu on nav-link click
document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
                const link = e.target.closest('.nav-link');
                if (link && link.getAttribute('href') && link.getAttribute('href').startsWith('#')) {
                        e.preventDefault();
                        if (link.hash) {
                                window.location.hash = link.hash;
                        }
                        // If you want to close the menu, call your closeMenu() function here if available
                }
        });
});