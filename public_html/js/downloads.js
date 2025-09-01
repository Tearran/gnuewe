(async function () {
        const container = document.getElementById("downloads-container");
        const url = "/json/downloads.json"; // your JSON file path

        try {
                const res = await fetch(url);
                if (!res.ok) throw new Error("HTTP " + res.status);
                const data = await res.json();

                container.innerHTML = ""; // clear fallback text

                data.forEach(item => {
                        const card = document.createElement("div");
                        card.className = "card";

                        card.innerHTML = `
                    <h2>${item.name} ${item.version ? "(" + item.version + ")" : ""}</h2>
                    <p>${item.description}</p>
                    ${item.size ? `<p><small>Size: ${item.size}</small></p>` : ""}
                    <a href="${item.url}" target="_blank">Download</a>
                `;

                        container.appendChild(card);
                });

        } catch (err) {
                console.error("Failed to load downloads.json:", err);
                container.innerHTML = `<div class="card"><h2>Error</h2><pre>${err}</pre></div>`;
        }
})();
