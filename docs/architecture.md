# DevPortal Architecture & Subsystems

> **Document:** `docs/architecture.md`  
> **Generation Epoch:** `2605.4.3-bs`  
> **Classification:** Internal System Blueprint  

---

## 1. High-Level Architectural Blueprint

DevPortal is structured as a **Lean Laravel Application** utilizing modern PHP 8.4/8.5 strict typing, skinny HTTP controllers, and dedicated Action/Service classes.

```
                              SYSTEM COMPONENT TOPOLOGY
 ┌────────────────────────────────────────────────────────────────────────────────────────┐
 │ HTTP Layer: ProjectController, SettingsController, MaintenanceController               │
 ├────────────────────────────────────────────────────────────────────────────────────────┤
 │ Business Actions: MoveProject, DeleteProject, SyncProjectVersion, TestEntryPoints     │
 ├────────────────────────────────────────────────────────────────────────────────────────┤
 │ Service Engine: ProjectScanner (Stat, Git, Manifests), SettingsService (Config & Cache)│
 ├────────────────────────────────────────────────────────────────────────────────────────┤
 │ Persistence & Cache: SQLite database (database/database.sqlite), Laravel Cache Store   │
 └────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Directory Scanner Pipeline (`ProjectScanner.php`)

The `ProjectScanner` service executes a resilient 4-stage pipeline for each discovered subfolder:

1. **Category Directory Discovery:**
   - Resolves all configured watch paths from `SettingsService`.
   - Discovers subdirectories matching `Active/`, `Archive/`, and `Sandbox/`.
2. **Manifest & Version Parsing:**
   - **PHP / Composer:** Inspects `composer.json` for framework tags (`laravel/framework`, `illuminate/support`) and package requirements.
   - **Node / PNPM:** Inspects `package.json` for frontend framework indicators (`next`, `vue`, `react`, `vite`, `tailwindcss`).
   - **GVS / CHANGELOG:** Parses `CHANGELOG.md` version headers (`## [2605.X.Y-tag]`) and Git tags to determine active version.
3. **Git Forensic Analysis:**
   - Executes non-blocking, parameter-escaped shell commands (`git branch --show-current`, `git status --porcelain`, `git log -n 5`).
   - Calculates dirty file counts (modified, added, untracked) and extracts author, hash, relative date, and commit messages.
4. **Resilience & Fault Isolation:**
   - Every project directory inspection is wrapped in isolated `try-catch` blocks.
   - Broken manifests or unreadable directories log warnings without interrupting dashboard rendering.

---

## 3. Mathematical Security Boundaries & Path Traversal Prevention

Mutating operations (`DeleteProject` and `MoveProject`) are governed by strict multi-tier path guards in `ProjectController`:

```php
$realProjectPath = realpath($projectPath);
$categoryPath = dirname($realProjectPath);
$basePath = dirname($categoryPath);
$category = basename($categoryPath);

// Tier 1: Category Invariant
if (! in_array($category, ['Active', 'Archive', 'Sandbox'], true)) {
    return response()->json(['error' => 'Invalid category structure'], 422);
}

// Tier 2: Allowlisted Watch Path Invariant
$isAllowlisted = array_any($allowlistedPaths, fn($path) => realpath($path) === $basePath);
if (! $isAllowlisted) {
    return response()->json(['error' => 'Target not in allowlisted paths'], 422);
}
```

This ensures that system roots (`/`, `/etc`, `/usr`), user home roots, or arbitrary disk locations can never be targeted for deletion or relocation.

---

## 4. Frontend Design System & Custom Web Components

- **CSS Design Tokens:** Semantic custom properties in `resources/css/app.css` using the HSL color model, with automatic light/dark mode contrast adaptation.
- **Custom Element (`JLMeter`):** High-performance web component rendering multi-layer progress meters for version progress and project health without external JavaScript libraries.
- **Asset Pipeline:** Vite handles asset bundling and livereloading during development.
