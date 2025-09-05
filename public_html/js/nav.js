(function () {
    const container = document.getElementById('nav-container');
    if (!container) return;

    fetch('/json/nav.json',
        {
            cache: 'no-cache'
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(navItems => {
            if (!Array.isArray(navItems) || navItems.length === 0) {
                container.innerHTML = '<p>No navigation items found.</p>';
                return;
            }

            const ul = document.createElement('ul');
            ul.className = 'nav-list';

            navItems.forEach(item => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.className = 'nav-link';
                a.href = item.href || '#';
                a.textContent = item.label || '';
                li.appendChild(a);
                ul.appendChild(li);
            });

            container.innerHTML = '';
            container.appendChild(ul);
        })
        .catch(err => {
            console.error('Nav load error', err);
            container.innerHTML = '<p>Error loading navigation.</p>';
        });
})();

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('pre > code').forEach(function (code) {
        // Create wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'code-block-wrapper';

        // Insert wrapper before <pre>
        const pre = code.parentElement;
        pre.parentNode.insertBefore(wrapper, pre);

        // Move <pre> into wrapper
        wrapper.appendChild(pre);

        // Create button
        const btn = document.createElement('button');
        btn.className = 'copy-btn';
        btn.textContent = 'Copy';

        btn.onclick = function () {
            const codeText = code.innerText || code.textContent;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(codeText).then(function () {
                    btn.textContent = "Copied!";
                    setTimeout(() => btn.textContent = "Copy", 1200);
                });
            } else {
                // Fallback for older browsers
                const textarea = document.createElement("textarea");
                textarea.value = codeText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
                btn.textContent = "Copied!";
                setTimeout(() => btn.textContent = "Copy", 1200);
            }
        };

        // Add button to wrapper
        wrapper.appendChild(btn);
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const menuBtn = document.getElementById('menu-toggle');
    const nav = document.getElementById('site-nav');
    const navLinks = document.querySelectorAll('.nav-link');

    function closeMenu() {
        nav.classList.remove('open');
        menuBtn.classList.remove('open');
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.setAttribute('aria-label', 'Open navigation');
    }

    if (menuBtn && nav) {
        menuBtn.addEventListener('click', function (e) {
            const isOpen = nav.classList.toggle('open');
            menuBtn.classList.toggle('open', isOpen);
            menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            menuBtn.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            e.stopPropagation();
        });

        // Click-away handler
        document.addEventListener('click', function (e) {
            if (!nav.classList.contains('open')) return;
            if (!nav.contains(e.target) && !menuBtn.contains(e.target)) {
                closeMenu();
            }
        });

        // On navigation link click, close menu
        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                closeMenu();
            });
        });
    }
});

