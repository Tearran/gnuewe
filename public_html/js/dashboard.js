function loadClientInfo() {
    const container = document.getElementById('client-info');

    const ua = navigator.userAgent;
    const platform = navigator.platform;
    const language = navigator.language;
    const screenSize = `${window.screen.width}x${window.screen.height}`;
    const viewportSize = `${window.innerWidth}x${window.innerHeight}`;
    const cookiesEnabled = navigator.cookieEnabled;
    const online = navigator.onLine;
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    container.innerHTML = `
            <ul>
                <li><strong>User Agent:</strong> ${ua}</li>
                <li><strong>Platform:</strong> ${platform}</li>
                <li><strong>Language:</strong> ${language}</li>
                <li><strong>Screen Size:</strong> ${screenSize}</li>
                <li><strong>Viewport Size:</strong> ${viewportSize}</li>
                <li><strong>Cookies Enabled:</strong> ${cookiesEnabled}</li>
                <li><strong>Online:</strong> ${online}</li>
                <li><strong>Timezone:</strong> ${timezone}</li>
            </ul>
        `;
}

// Optional: update viewport size on resize
window.addEventListener('resize', loadClientInfo);

// Initial load
loadClientInfo();
