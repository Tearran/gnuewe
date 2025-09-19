<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GNU EWE</title>
  <style>
    :root {
      --color-bg: #fff;
      --color-text: #000;
      --color-header-bg: #222;
      --color-header-text: #fff;
      --color-nav-bg: #f3f3f3;
      --color-main-bg: #fff;
      --color-aside-bg: #fafafa;
      --color-border: #ccc;
      --color-btn-bg: #fff;
      --color-btn-hover: #0077cc;
      --color-btn-text: #444;
      --icon-stroke-width: 1.6;
      --icon-transition: 120ms;
    }

    body.dark-mode {
      --color-bg: #181a1b;
      --color-text: #ececec;
      --color-header-bg: #23272e;
      --color-header-text: #f6f6f6;
      --color-nav-bg: #23272e;
      --color-main-bg: #181a1b;
      --color-aside-bg: #1e2124;
      --color-border: #333a41;
      --color-btn-bg: #23272e;
      --color-btn-hover: #58aaff;
      --color-btn-text: #ececec;
      --icon-color-muted: #888b92;
      --icon-color-danger: #ff7675;
      --icon-color-warn: #ffe066;
      --icon-color-success: #2ecc71;
      --color-link: #41aaff;
      --color-link-hover: #82cfff;
      --color-link-active: #1a8cff;
      --color-link-visited: #cabfff;
      --color-link-focus: #80e1ff;
    }

    html,
    body {
      margin: 0;
    }

    body {
      font-family: sans-serif;
      background: var(--color-bg);
      color: var(--color-text);
    }

    a {
      color: var(--color-link);
      text-decoration: underline;
      transition: color 120ms;
    }

    a:hover,
    a:focus {
      color: var(--color-link-hover);
    }

    a:active {
      color: var(--color-link-active);
    }

    a:visited {
      color: var(--color-link-visited);
    }

    a:focus-visible {
      outline: 2px solid var(--color-link-focus);
      outline-offset: 2px;
    }

    header {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 1rem;
      background: var(--color-header-bg);
      color: var(--color-header-text);
    }

    .actions {
      display: flex;
      gap: 0.5rem;
    }

    button {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 0.8rem;
      background: var(--color-btn-bg);
      border: 1px solid var(--color-border);
      border-radius: 0.4rem;
      color: var(--color-btn-text);
      cursor: pointer;
      font-size: 0.9rem;
      transition: background 0.2s, border-color 0.2s, color 0.2s;
    }

    button:hover {
      background: var(--color-btn-hover);
      border-color: var(--color-btn-hover);
      color: #fff;
    }

    .layout {
      display: flex;
      flex-direction: row;
      min-height: 100vh;
    }

    nav {
      flex: 0 0 250px;
      padding: 1rem;
      border: 1px solid var(--color-border);
      background: var(--color-nav-bg);
    }

    nav ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    nav li {
      margin: 0.25rem 0;
    }

    main {
      flex: 1;
      border: 1px solid var(--color-border);
      background: var(--color-main-bg);
      padding: 0;
    }
    .container,
    .page,
    article{
	padding: 1rem;
    }

    aside {
      flex: 0 0 250px;
      padding: 1rem;
      border: 1px solid var(--color-border);
      background: var(--color-aside-bg);
    }

    footer {
      padding: 1rem;
      border-top: 1px solid var(--color-border);
      text-align: center;
    }

    svg.icon {
      width: 1em;
      height: 1em;
      display: inline-block;
      vertical-align: middle;
      flex-shrink: 0;
      color: currentColor;
      stroke: currentColor;
      stroke-width: var(--icon-stroke-width);
      stroke-linecap: round;
      stroke-linejoin: round;
      fill: none;
      vector-effect: non-scaling-stroke;
      transition: color var(--icon-transition), stroke var(--icon-transition),
        fill var(--icon-transition), transform var(--icon-transition),
        opacity var(--icon-transition);
    }

    /* Mobile layout */
    @media (max-width: 768px) {
      .layout {
        flex-direction: column;
      }

      nav,
      aside {
        flex: none;
        order: 0;
      }

      main {
        order: 2;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="actions">
      <button onclick="togglePanel('#tag-links')" title="Toggle Outline">
        <svg class="icon">
          <use href="/images/icons.svg#i-tag"></use>
        </svg>
      </button>
    </div>
    <div class="actions">
      <button onclick="toggleDarkMode()" title="Toggle Dark Mode" aria-pressed="false">
        <svg id="darkIcon" class="icon">
          <path d="M12 3a9 9 0 0 0 0 18 9 9 0 0 1 0-18z" />
        </svg>
      </button>
    </div>
  </header>

  <div class="layout">
	<nav id="tag-links" aria-label="Site Navigation">
		<ul>
			<li><a href="?page=markdown">HOME</a></li>
			<!-- Internal link (to a PHP page inside your site)
			<li><a href="htmlplay.php">Playground (Standalone)</a></li> -->
			<!-- Internal link with include (loads into main page) 
			<li><a href="?page=html">Playground (Included)</a></li>-->
			<!-- External link -->
			<li><a href="playhtml.php" target="_blank" rel="noopener">Play - HTML (js,css)</a></li>
			<li><a href="playmd.php" target="_blank" rel="noopener">Play - MarkDown (MD)</a></li>

		</ul>
	</nav>


    <main>
  <?php
  $page = $_GET['page'] ?? 'markdown';

switch ($page) {
	case 'markdown':
		include "./markdown.php";
		break;
	case 'html':
		include "./htmlplay.php";
		break;
	case 'scan':
		include "./scan.php";
		break;
    	default:
      		echo "<p>Page not found.</p>";
  }
  ?>
</main>


    <!-- Optional sidebar -->
    <aside hidden>
      json metadata
      <?php include "./scan.php"; ?>
    </aside>
  </div>

  <footer>
    Footer text or links
  </footer>
</body>

</html>
