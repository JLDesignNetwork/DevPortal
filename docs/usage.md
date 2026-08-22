# DevPortal Usage & Operational Guide

> **Document:** `docs/usage.md`  
> **Generation Epoch:** `2605.4.3-bs`  
> **Classification:** Internal User & Operations Manual  

---

## 1. Directory Structure Conventions

DevPortal organizes projects by **Watch Locations** and **Category Subfolders**:

```
[Watch Location (e.g. /Volumes/Kingston-256/_DevSites)]
├── Active/                      # Actively maintained projects
│   ├── DevPortal/
│   └── Todo-Schema/
├── Archived/                     # Archived, read-only, or legacy projects
│   └── LegacyApp/
└── Sandboxed/                     # Exploratory prototypes and scratch builds
    └── PrototypeX/
```

---

## 2. Configuration via Settings Drawer

Click the **Settings** icon in the top right corner of the dashboard to configure:

1. **Scan Locations:**
   - Add absolute disk paths to scan (e.g., `/Volumes/Kingston-256/_DevSites`, `/Users/username/Code`).
   - Multiple watch locations are supported simultaneously.
2. **Local Domain TLD:**
   - Configure your local server domain suffix (e.g., `test` for Laravel Herd/Valet, `local`, or `localhost`).
   - "Open Site" links in project cards automatically compile to `http://[project-name].[tld]`.
3. **Scanner Caching & TTL:**
   - Toggle directory scan caching on or off.
   - Set cache TTL in seconds (default: 60 seconds) to balance performance against real-time file updates.
4. **Dashboard Widget Limits:**
   - Set maximum item limits for the "Recently Updated" and "Most Active (30 Days)" widgets.

---

## 3. Project Management Workflows

### Relocating Projects Across Categories
- Click the **Move** button on any project card.
- Select the destination watch location and category (`Active`, `Archived`, `Sandboxed`).
- DevPortal verifies destination non-collision, performs the atomic directory move, and invalidates the scanner cache.

### Safe Project Deletion
- Click the **Delete** button on a project card.
- Confirm the action in the double-confirmation safety dialog.
- The backend verifies allowlisted boundaries and completely removes the directory from disk.

---

## 4. Maintenance & Version Synchronization Tools

Navigate to the **Maintenance** tab in the dashboard to access automated utilities:
- **Version Cascade Sync:** Audits and aligns `package.json`, `composer.json`, and `CHANGELOG.md` version tags.
- **Entry Points Testing:** Pings local development HTTP entrypoints across all active projects to verify server availability and detect unhandled exceptions.
