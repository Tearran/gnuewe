// /js/network.js
async function loadNetworkTools() {
        const url = "https://raw.githubusercontent.com/armbian/configng/refs/heads/main/tools/json/config.network.json";
        const grid = document.getElementById("network-list");

        // Recursive function to render menu/submenu
        function renderMenu(items, container) {
                items.forEach(item => {
                        if (item.sub && item.sub.length > 0) {
                                const section = document.createElement("div");
                                section.className = "card";

                                const header = document.createElement("h3");
                                header.textContent = item.short || item.id;
                                header.style.cursor = "pointer";
                                section.appendChild(header);

                                const content = document.createElement("div");
                                content.style.display = "none";
                                content.style.marginTop = "0.5rem";
                                section.appendChild(content);

                                header.addEventListener("click", () => {
                                        content.style.display = content.style.display === "none" ? "block" : "none";
                                });

                                container.appendChild(section);

                                renderMenu(item.sub, content);

                        } else {
                                const card = document.createElement("div");
                                card.className = "card";

                                card.innerHTML = `
    <h4>${item.short || item.id}</h4>
    <p>${item.description}</p>
    ${item.about ? `<p><em>${item.about}</em></p>` : ""}
    <p><small>Status: ${item.status || ""} | Author: ${item.author || ""}</small></p>
    ${item.command && item.command.length ? `<pre>${item.command.join(" && ")}</pre>` : ""}
    `;

                                container.appendChild(card);
                        }
                });
        }

        try {
                const res = await fetch(url);
                if (!res.ok) throw new Error("HTTP " + res.status);
                const json = await res.json();

                grid.innerHTML = ""; // Clear previous content
                renderMenu(json.menu, grid);

        } catch (err) {
                console.error("Failed to load network JSON:", err);
                grid.innerHTML = `<div class="card"><h2>Error</h2><pre>${err}</pre></div>`;
        }
}

document.addEventListener("DOMContentLoaded", loadNetworkTools);
