# DevPortal Core Features & Capabilities

> **Document:** `docs/features.md`  
> **Generation Epoch:** `2605.4.3-bs`  
> **Classification:** Internal Technical Reference  

---

## Web GUI Features

The DevPortal is the central intelligence and management web interface for the JLDN development environment. It acts as the primary GUI for monitoring and interacting with all other projects across the container.

### Core Capabilities

* **Dashboard:** Surfaces the most recently modified projects and the most Git-active projects (last 30 days), with configurable widget limits via Settings.
* **Multi-Category Scanning:** Automatically scans `Active/`, `Archived/`, and `Sandboxed/` across all allowlisted root paths. Results are cached with a configurable TTL.
* **Rich Metadata Extraction:** Parses `README.md` (project name, description, version, features list), `CHANGELOG.md` (latest release version, date, content), `package.json`, and `composer.json` (dependencies, split by standard vs dev).
* **GVS & Version Tracking:** Determines the canonical production version by inspecting Git tags, `CHANGELOG.md` headers, `package.json`, and `composer.json`.
* **Git Integration:** Monitors the active branch, dirty state (untracked/modified file count), and lists the 5 most recent commits with author, hash, and relative timestamps.
* **Web Entry Point Detection:** Identifies whether a project is web-accessible by scanning for standard entrypoints (`index.php`, `public/index.php`, `src/main.js`, `src/App.vue`, etc.).
* **Timestamp Tracking:** Uses macOS native `stat` for folder birth dates and PHP `filemtime` for last-modified timestamps.
* **Version Sync (Single & Batch):** One-click action to propagate the highest detected version across `CHANGELOG.md`, `package.json`, `composer.json`, `README.md`, and all `@version` DocBlock headers. Auto-commits and pushes to `origin`. Batch mode respects per-category include/exclude rules.
* **Entry Points Testing:** cURL-pings all active project URLs, checks HTTP status codes, and deep-scans page content for PHP errors, SQL errors, Laravel Whoops/Ignition exceptions — even on HTTP 200 responses. Outputs a Markdown report.
* **Move Project:** Relocates a project to a different category via a UI dropdown. Handles directory creation and collision resolution.
* **Delete Project:** Permanently removes a project directory, guarded by a double-confirmation modal.
* **Mathematical Security Boundaries:** All move and delete actions enforce strict path-traversal shielding — operations can never escape allowlisted container bounds.
* **Configurable Domain Extension:** Set local TLD (`.test`, `.local`, `.localhost`) to power "Open Site" buttons.

---

## Frontmatter Integration & Inference

DevPortal's `ProjectScanner` actively consumes the JLDN Metadata Frontmatter block to display project-type badge pills, power filtering and sorting, and smart-default platform selections in future wizards.

### Type Inference Fallback

If a project does not declare a `type` in its frontmatter, DevPortal will automatically infer its type using file signature detection:
- `web-app` → Detected `laravel/framework` in composer, or `next`/`vite` in package.json.
- `plugin` → Detected `keymaps/` or `menus/` directory (Pulsar IDE plugin).
- `book` → Detected a `book.css` file or any CSS file containing an `@page` declaration.
- `library` → Detected composer or npm dependencies without any known web entry point (like `public/index.php` or `src/App.vue`).
- `docs` → Detected multiple Markdown files without a `src/` or `public/` directory.
- `general` → Fallback if no other signatures match.

---

## Future Features

* **Gist Activation:** A per-project feature that allows initializing and syncing a project as a GitHub Gist instead of a full Git repository (ideal for micro-scripts, `cli`, and `sandbox` snippets).
* **True Sandboxed Network Isolation:** Implement a tiered sandboxing approach (`.env` flag + disabling PHP network functions per project) for `Sandboxed/` via DevPortal-managed config files to block outbound traffic from test scripts.
* **Shell-Triggered Project Scaffolding:** A "Create New Project" modal form in DevPortal that triggers a shell script to scaffold the project structure per JLDN standards, replacing the stale `_DevDocs/` template folder.
* **Project Notes & Internal Wiki:** A per-project `NOTES.md` editable text area surfaced in the DevPortal UI for storing local env setup instructions or dev gotchas.
* **Local Notification System:** Wire macOS native notifications (`osascript` or `terminal-notifier`) for version sync completion, entry point test failures, or scaffolding completion.
* **Project Health Score:** Compute a composite badge (🟢 Healthy / 🟡 Needs Attention / 🔴 Critical) based on the presence of required JLDN files (AGENTS, README, CHANGELOG, etc.), Git state, and test status.
* **Git Pull / Batch Sync Button:** A UI action to run `git pull` sequentially across all `Active/` projects with a live streaming output log.
* **Inactivity Detection:** Automatically suggest archiving projects in `Active/` that haven't received a Git commit in a configurable number of days.
* **Environment Variable Viewer:** A read-only UI inspector for a project's `.env` file (masking secrets) for quickly checking `APP_URL` or DB configs.
* **Artisan Command Runner:** A UI panel allowing execution of allowlisted artisan commands (e.g., `migrate`, `optimize`) against any selected Laravel project without terminal access.
* **GitHub/GitLab Repo Creation Wizard:** A step-by-step modal wizard that smart-defaults platform routing (GitLab vs GitHub) based on the project's tech stack, creates remote repos via `gh` or `glab` CLI, and configures the initial origin tracking.
