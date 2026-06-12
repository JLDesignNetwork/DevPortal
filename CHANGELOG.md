# Changelog

All notable changes to **DevPortal** will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html).

## [1.2.2] — 2026-06-12

### Security
- **Dependabot Critical Fix**: Added a `pnpm.overrides` instruction in `package.json` to force the resolution of `shell-quote` to `^1.8.4` due to a critical vulnerability in `concurrently`'s dependency tree (GHSA-w7jw-789q-3m8p).

## [1.2.1] — 2026-06-12

### Changed
- **Housekeeping & Standardization**: Enforced rigorous compliance with the `GOLD_STANDARD.md` and `ANTIGRAVITY.md` blueprints.
- **File-Level Versioning**: Injected `@since 1.2.0` and `@version 1.2.0` tracking tags across all core PHP, JS, and CSS files.
- **README Standardization**: Rebuilt the README structure to feature Shields.io technology badges and an auto-linked Table of Contents.
- **Composer Metadata**: Updated `composer.json` name to `jldn/devportal` and refined description keywords to reflect its local dashboard purpose.

## [1.2.0] — 2026-05-28

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

## [1.1.0] — 2026-05-28

### Added
- **Detailed Project Drawer**: Introduced an immersive metadata overlay drawer showing created/updated times, framework version, production version, features list, commit timelines, and dependencies (with NPM vs. Composer tabs).
- **Production Version Checker**: Configured a cascade check to identify production versions checking `package.json`, `composer.json`, `git describe` tags, and `CHANGELOG.md` version blocks.
- **Settings page custom scan paths**: Exposes settings to toggle cache, set cache TTL, and define multiple watch directories to scan.
- **Key Features parser**: Added markdown scanning for lists under "Features" headers in project READMEs.
- **Timestamps**: Added macOS stat-compatible creation dates extraction.

## [1.0.0] — 2026-05-28

### Added
- **Core scanning engine**: Automatically scans watch directories for project folders categorized under `Active`, `Archive`, and `Sandbox`.
- **Project Card UI**: Renders cards displaying parsed project name, framework version, Git branch, Git dirty file status, and last modified dates.
- **Action utilities**: Quick button to open the `.test` domain in a local browser, copy the absolute path, and move project categories.