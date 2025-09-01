(async function fetchBashCgiInfoOnce() {
        try {
                const res = await fetch('/cgi-bin/info.cgi', { cache: 'no-store' });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();

                const container = document.getElementById('bash-cgi-cards');
                container.innerHTML = ''; // clear fallback text

                function createCard(title, content) {
                        const div = document.createElement('div');
                        div.className = 'card';

                        div.innerHTML = `
<h2 class="card-header" style="cursor:pointer;">${title}</h2>
<div class="card-content" style="display:none; white-space: pre-wrap;">${content}</div>
`;

                        const header = div.querySelector('.card-header');
                        const contentDiv = div.querySelector('.card-content');

                        header.addEventListener('click', () => {
                                contentDiv.style.display = contentDiv.style.display === 'none' ? 'block' : 'none';
                        });

                        return div;
                }

                // System
                container.appendChild(createCard('System', `System: ${data.system}\nUptime: ${data.uptime}`));
                container.appendChild(createCard('OS Release', data.os_release.join('\n')));

                // Bash / Shell
                container.appendChild(createCard('Bash Version', data.bash.version));
                container.appendChild(createCard('Shells', data.bash.shells.join('\n')));

                // CPU / Memory / Disk
                container.appendChild(createCard('CPU Info', data.cpu_memory_disk.cpu.join('\n')));
                container.appendChild(createCard('Memory Info', data.cpu_memory_disk.memory.join('\n')));
                container.appendChild(createCard('Disk Info', data.cpu_memory_disk.disk.join('\n')));

                // Environment
                container.appendChild(createCard('Environment Variables', data.environment.join('\n')));

                // Tools
                const toolsContent = data.tools.map(tool => {
                        const key = Object.keys(tool)[0];
                        return `${key}: ${tool[key]}`;
                }).join('\n');
                container.appendChild(createCard('Installed Tools', toolsContent));

                // Networking
                const netContent = `Hostname/IP: ${data.networking.hostname_ip}\nConnections:\n${data.networking.connections.join('\n')}`;
                container.appendChild(createCard('Networking', netContent));

                // MOTD
                if (data.motd && data.motd.length > 0) {
                        container.appendChild(createCard('Message of the Day', data.motd.join('\n')));
                }

        } catch (err) {
                const container = document.getElementById('bash-cgi-cards');
                container.innerHTML = `<div class="card"><h2>Error</h2><pre>${err}</pre></div>`;
                console.error(err);
        }
})();
