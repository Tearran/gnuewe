
(function () {
        // Create styles dynamically
        const style = document.createElement("style");
        style.textContent = `
    #fullscreen-container {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 9999;
    }

    #fullscreen-btn {
      padding: 0.5rem 1rem;
      font-size: 1rem;
      border: none;
      border-radius: 0.5rem;
      background: #444;
      color: #fff;
      cursor: pointer;
      transition: background 0.3s;
    }

    #fullscreen-btn:hover {
      background: #666;
    }
  `;
        document.head.appendChild(style);

        const btn = document.getElementById("fullscreen-btn");

        if (btn) {
                btn.addEventListener("click", () => {
                        if (!document.fullscreenElement) {
                                document.documentElement.requestFullscreen().catch(err => {
                                        console.error(`Fullscreen error: ${err.message}`);
                                });
                                btn.textContent = "🗗 Exit Fullscreen";
                        } else {
                                document.exitFullscreen();
                                btn.textContent = "⛶ Fullscreen";
                        }
                });

                // Reset text when ESC or system exits fullscreen
                document.addEventListener("fullscreenchange", () => {
                        if (!document.fullscreenElement) {
                                btn.textContent = "⛶ Fullscreen";
                        }
                });
        }
})();

