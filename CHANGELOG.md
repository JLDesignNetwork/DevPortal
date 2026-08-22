# Changelog

All notable changes to **DevPortal** will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to the [JLDN Generational Versioning Schema (GVS)](https://github.com/JLDesignNetwork).

## [2605.4.3-bs] - 2026-08-18

### Added
- **In-Repo Documentation Wiki (`docs/`)**: Initialized internal wiki hub containing `docs/index.md` (Table of Contents), `docs/architecture.md` (System Subsystems & Security Guards), and `docs/usage.md` (Operations & Workflows).
- **GitHub Governance & Community Suite**: Scaffolded `.github/FUNDING.yml`, `.github/SECURITY.md`, `.github/CONTRIBUTING.md`, `.github/CODE_OF_CONDUCT.md`, `.github/PULL_REQUEST_TEMPLATE.md`, `.github/copilot-instructions.md`, and structured `.github/ISSUE_TEMPLATE/` forms (`bug_report.yml`, `feature_request.yml`, `config.yml`).
- **Automated CI/CD Workflows**: Implemented `.github/workflows/ci.yml` (matrix Pest/Pint/Vite test runner with concurrency cancellation) and `.github/workflows/codeql.yml` (SAST security analysis).

## [2605.4.2-bs] - 2026-08-18

### Added
- **MCP Server Manifest**: Scaffolded `.mcp.json` configured with `laravel-boost` and `herd` MCP servers per Global Rule 13.3.
- **Universal Scratch Space**: Initialized `.agents/scratch/` workspace lifecycle directory.

### Removed
- **Legacy Artifacts & Redundancy**: Purged dead `/.antigravitycli/` directory and dangling symlink, removed legacy `/.scratches/` root folder, consolidated standalone `pnpm-workspace.yaml`, and cleaned redundant `.agents/rules/laravel-standards.md`.

## [2605.4.1-bs] - 2026-08-18

### Changed
- **Tooling & Dependency Group Updates**: Integrated Dependabot group PR #8 (`concurrently: ^10.0.5`) and PR #9 (`fakerphp/faker`, `laravel/pail`, `laravel/pao`, `laravel/pint`, `mockery/mockery`, `pestphp/pest-plugin-laravel`, `rector/rector`).
- **Composer Runtime Hardening**: Suppressed obsolete `pestphp/pest-plugin` autoload injection in `composer.json` allow-plugins.

## [2605.4.0-bs] - 2026-08-18

### Added
- **Generational Development Hub**: Scaffolded root `.dev/` tree containing multi-generational `ROADMAP.md`, `backlog.json`, `2605/backlog.json`, and `2605/ideas.json`.
- **Security Boundaries**: Scaffolded `.aiexclude` (hard token boundary) and `.aiignore` (token hygiene).
- **Dependabot CI Configuration**: Implemented `.github/dependabot.yml` for automated grouped weekly security audits.

### Fixed
- **Composer Vulnerabilities**: Upgraded `guzzlehttp/guzzle` to `7.15.2`, `guzzlehttp/psr7` to `2.13.0`, and `league/commonmark` to `2.9.0`, resolving 17 security advisories (CVE-2026-71488, CVE-2026-69246, etc.).
- **NPM / PNPM Vulnerabilities**: Upgraded `vite` to `8.2.1`, `laravel-vite-plugin` to `3.2.0`, and resolved `postcss >= 8.5.23` and `shell-quote >= 1.8.4` overrides, eliminating all 7 JS security advisories.
- **Global Scripting Compliance**: Replaced prohibited `npx` script with `pnpm dlx only-allow pnpm` in `package.json`.

## [2605.3.2-bs] - 2026-06-12

### Security
- **Dependabot Critical Fix**: Added a `pnpm.overrides` instruction in `package.json` to force the resolution of `shell-quote` to `^1.8.4` due to a critical vulnerability in `concurrently`'s dependency tree (GHSA-w7jw-789q-3m8p).

## [2605.3.1-bs] - 2026-06-12

### Changed
- **Housekeeping & Standardization**: Enforced rigorous compliance with the `GOLD_STANDARD.md` and `ANTIGRAVITY.md` blueprints.
- **File-Level Versioning**: Injected `@since 2605.3.0-bs` and `@version 2605.3.1-bs` tracking tags across all core PHP, JS, and CSS files.
- **README Standardization**: Rebuilt the README structure to feature Shields.io technology badges and an auto-linked Table of Contents.
- **Composer Metadata**: Updated `composer.json` name to `jldn/devportal` and refined description keywords to reflect its local dashboard purpose.

## [2605.3.0-bs] - 2026-05-28

### Added
- **Dashboard widgets**: Introduced "Recently Updated" and "Most Active" widgets on a new default home Dashboard tab, with configurable limits.
- **Git activity scanner**: Parsed Git commit counts within the last 30 days (`git_activity_count`) to track project activity.
- **Location-aware project move**: Expanded project relocation action to allow moving directories across any combination of watch locations and category subfolders.
- **Secure Project Deletion**: Added a deletion action in the UI with a double-confirmation prompt, backed by strict controller validations ensuring deletions are confined to allowlisted directories.
- **Pest test coverage**: Implemented new unit and feature tests in `DeleteProjectTest.php` and `ProjectApiTest.php` to verify deletion security and movement validations.
- **Configurable Domain Extension**: Added settings configuration to let developers change the local TLD suffix (e.g. `.test`, `.local`) rather than hardcoding it in project card launch actions.

### Fixed
- **Settings modal scroll**: Added flexbox styling constraints in `app.css` to enable vertical scrolling in the settings modal, preventing inputs from being cut off.
- **Dropdown load state**: Corrected initialization sequence in `app.js` to fetch settings before rendering project lists, preventing move dropdowns from rendering blank.
- **Grid layout crowding**: Changed the main project cards grid from a 3-column auto-fill layout to a clean 2-column layout to prevent buttons and dropdowns from bunching.

## [2605.2.0-bs] - 2026-05-28

### Added
- **Detailed Project Drawer**: Introduced an immersive metadata overlay drawer showing created/updated times, framework version, production version, features list, commit timelines, and dependencies (with NPM vs. Composer tabs).
- **Production Version Checker**: Configured a cascade check to identify production versions checking `package.json`, `composer.json`, `git describe` tags, and `CHANGELOG.md` version blocks.
- **Settings page custom scan paths**: Exposes settings to toggle cache, set cache TTL, and define multiple watch directories to scan.
- **Key Features parser**: Added markdown scanning for lists under "Features" headers in project READMEs.
- **Timestamps**: Added macOS stat-compatible creation dates extraction.

## [2605.1.0-bs] - 2026-05-28

### Added
- **Core scanning engine**: Automatically scans watch directories for project folders categorized under `Active`, `Archived`, and `Sandboxed`.
- **Project Card UI**: Renders cards displaying parsed project name, framework version, Git branch, Git dirty file status, and last modified dates.
- **Action utilities**: Quick button to open the `.test` domain in a local browser, copy the absolute path, and move project categories.