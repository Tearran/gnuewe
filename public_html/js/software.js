async function loadArmbianTools() {
        const url = "https://raw.githubusercontent.com/armbian/configng/refs/heads/main/tools/json/config.software.json";
        const grid = document.getElementById("cardGrid");

        // Utility to safely escape single quotes for onclick
        const escapeQuotes = str => str.replace(/'/g, "\\'");

        // Recursive function to render menu/submenu
        function renderMenu(items, container) {
                items.forEach(item => {
                        if (item.sub && item.sub.length > 0) {
                                // Create collapsible section for submenu
                                const section = document.createElement("div");
                                section.className = "card";

                                const header = document.createElement("h3");
                                header.textContent = item.id;
                                header.style.cursor = "pointer";
                                section.appendChild(header);

                                const content = document.createElement("div");
                                content.style.display = "none";
                                content.style.marginTop = "0.5rem";
                                section.appendChild(content);

                                // Toggle visibility on click
                                header.addEventListener("click", () => {
                                        content.style.display = content.style.display === "none" ? "block" : "none";
                                });

                                container.appendChild(section);

                                // Recursive call for sub items
                                renderMenu(item.sub, content);

                        } else {
                                // Leaf tool: render as card
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
                console.error("Failed to load JSON:", err);
                grid.innerHTML = `<div class="card"><h2>Error</h2><pre>${err}</pre></div>`;
        }
}

loadArmbianTools();