<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Dev Portal — a dashboard for browsing and managing your local development projects.">
    <title>Dev Portal — Local Projects</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="container">

        {{-- Header --}}
        <header>
            <div class="header-title">
                <h1>Dev Portal</h1>
                <p>Browse, search, and organise your local development sites.</p>
            </div>
            <div class="header-controls">
                <div class="stats" style="display: none;">
                    <!-- Removed: Counts moved to tabs -->
                </div>
                <button id="maintenance-toggle-btn" class="btn btn-icon" title="Maintenance Mode" aria-label="Maintenance Mode" style="padding: 0.5rem; border-radius: 50%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                </button>
                <button id="settings-toggle-btn" class="btn btn-icon" title="Open Settings" aria-label="Open Settings" style="padding: 0.5rem; border-radius: 50%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
            </div>
        </header>

        {{-- Controls Bar (Tabs + Search) --}}
        <div class="controls-bar">
            <nav class="tabs" role="tablist" aria-label="Project categories">
                <button
                    class="tab-button active"
                    data-category="Dashboard"
                    role="tab"
                    aria-selected="true"
                    id="tab-dashboard"
                    aria-controls="project-list"
                >Dashboard</button>
                <button
                    class="tab-button"
                    data-category="Active"
                    role="tab"
                    aria-selected="false"
                    id="tab-active"
                    aria-controls="project-list"
                >Active <span class="badge" id="stats-active-count" style="margin-left: 0.5rem; background: var(--bg-card); color: var(--text-main);">…</span></button>
                <button
                    class="tab-button"
                    data-category="Archive"
                    role="tab"
                    aria-selected="false"
                    id="tab-archive"
                    aria-controls="project-list"
                >Archive <span class="badge" id="stats-archive-count" style="margin-left: 0.5rem; background: var(--bg-card); color: var(--text-main);">…</span></button>
                <button
                    class="tab-button"
                    data-category="Sandbox"
                    role="tab"
                    aria-selected="false"
                    id="tab-sandbox"
                    aria-controls="project-list"
                >Sandbox <span class="badge" id="stats-sandbox-count" style="margin-left: 0.5rem; background: var(--bg-card); color: var(--text-main);">…</span></button>

            </nav>

            <div class="controls-actions" id="controls-actions" style="display: flex; gap: 0.75rem; align-items: center;">
                <div class="sort-wrapper" style="position: relative;" id="sort-wrapper">
                    <label for="sort-select" class="sr-only">Sort projects</label>
                    <select id="sort-select" class="form-control" style="appearance: none; padding-right: 2rem; border-radius: var(--radius-full); border: 1px solid var(--border); background: var(--bg-card); color: var(--text-main); font-size: 0.875rem; cursor: pointer; height: 100%;">
                        <option value="date-desc">Modified: Newest</option>
                        <option value="date-asc">Modified: Oldest</option>
                        <option value="created-desc">Created: Newest</option>
                        <option value="created-asc">Created: Oldest</option>
                        <option value="alpha-asc">Alphabetical: A-Z</option>
                        <option value="alpha-desc">Alphabetical: Z-A</option>
                    </select>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted);">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
                <div class="search-wrapper" role="search" id="search-wrapper">
                    <span class="search-icon" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                    </span>
                    <label for="search-input" class="sr-only">Search projects</label>
                    <input
                        type="search"
                        id="search-input"
                        class="search-input"
                        placeholder="Search projects…"
                        autocomplete="off"
                        spellcheck="false"
                    >
                </div>
            </div>
        </div>

        {{-- Project List --}}
        <main id="project-list" role="tabpanel" aria-labelledby="tab-dashboard">
            {{-- Cards rendered by JavaScript --}}
        </main>

        {{-- Maintenance View --}}
        <main id="maintenance-view" role="tabpanel" aria-labelledby="tab-maintenance" style="display: none; padding: 2rem 0;">
            
            <div style="max-width: 800px; margin: 0 auto 1.5rem;">
                <nav class="tabs" role="tablist" style="background: transparent; border-bottom: 1px solid var(--border); padding-bottom: 0;">
                    <button class="tab-button active" data-subtab="version-sync" role="tab" aria-selected="true" id="subtab-version-sync">Version Sync</button>
                    <button class="tab-button" data-subtab="entry-points" role="tab" aria-selected="false" id="subtab-entry-points">Entry Point Testing</button>
                </nav>
            </div>

            {{-- Version Sync Subtab --}}
            <div id="maintenance-tab-version-sync" class="settings-section" style="max-width: 800px; margin: 0 auto; background: var(--bg-card); padding: 2rem; border-radius: 12px; border: 1px solid var(--border);">
                <h2>Version Synchronization</h2>
                <p class="section-desc" style="margin-bottom: 1.5rem;">
                    Sync the absolute highest semantic version from your project's CHANGELOG across package.json, composer.json, README.md, and all script file comments.
                </p>
                <button type="button" id="maintenance-sync-all-btn" class="btn btn-primary" style="margin-bottom: 1.5rem;">
                    Sync All Project Versions
                </button>
                <div id="maintenance-sync-log" style="background: var(--bg-main); padding: 1rem; border-radius: 6px; font-family: monospace; font-size: 0.9rem; max-height: 400px; overflow-y: auto; border: 1px solid var(--border); white-space: pre-wrap;">
                    <div style="color: var(--text-muted);">Ready to sync...</div>
                </div>
            </div>

            {{-- Entry Point Testing Subtab --}}
            <div id="maintenance-tab-entry-points" class="settings-section" style="max-width: 800px; margin: 0 auto; background: var(--bg-card); padding: 2rem; border-radius: 12px; border: 1px solid var(--border); display: none;">
                <h2>Entry Point Testing</h2>
                <p class="section-desc" style="margin-bottom: 1.5rem;">
                    Test all allowlisted projects with web entry points to ensure their URLs respond correctly without 403 or 500 errors.
                </p>
                <button type="button" id="maintenance-test-entry-btn" class="btn btn-primary" style="margin-bottom: 1.5rem;">
                    Test Entry Points
                </button>
                <div id="maintenance-test-log" style="background: var(--bg-main); padding: 1rem; border-radius: 6px; font-family: sans-serif; font-size: 0.95rem; max-height: 500px; overflow-y: auto; border: 1px solid var(--border); color: var(--text-main);">
                    <div style="color: var(--text-muted);">Ready to test...</div>
                </div>
            </div>
        </main>
            
        {{-- Settings View --}}
        <main class="main-content" id="settings-view" style="display: none; padding-top: 1rem;">
            <div class="settings-header" style="max-width: 1000px; margin: 0 auto 1.5rem;">
                    <h2>Portal Settings</h2>
                    <p class="section-desc">Manage how Dev Portal operates, from directory scanning caching to default sorting behaviors.</p>
                </div>
                
                <form id="settings-form" novalidate style="max-width: 1000px; margin: 0 auto; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; display: flex; flex-direction: column;">
                    <div class="settings-layout" style="display: flex; min-height: 500px;">
                        {{-- Sidebar --}}
                        <div class="settings-sidebar" style="width: 250px; background: var(--bg-main); border-right: 1px solid var(--border); padding: 1.5rem 0;">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <li><button type="button" class="settings-nav-btn active" data-section="settings-ui">General / UI</button></li>
                                <li><button type="button" class="settings-nav-btn" data-section="settings-cache">Cache & Performance</button></li>
                                <li><button type="button" class="settings-nav-btn" data-section="settings-paths">Scan Locations</button></li>
                                <li><button type="button" class="settings-nav-btn" data-section="settings-domain">Local Domain</button></li>
                                <li><button type="button" class="settings-nav-btn" data-section="settings-maintenance">Maintenance Rules</button></li>
                            </ul>
                        </div>
                        
                        {{-- Content Area --}}
                        <div class="settings-content" style="flex: 1; padding: 2rem;">
                            
                            {{-- UI Preferences --}}
                            <div id="settings-ui" class="settings-panel active">
                                <div class="settings-section" style="margin-bottom: 2rem;">
                                    <h3 style="margin-top: 0;">Dashboard Widgets</h3>
                                    <p class="section-desc">Configure the splash/dashboard display widget limits.</p>
                                    
                                    <div class="form-group-row">
                                        <div class="form-group">
                                            <label for="settings-splash-recent-count">Recently Updated Limit</label>
                                            <input type="number" id="settings-splash-recent-count" class="form-input" min="1" value="5">
                                        </div>
                                        <div class="form-group">
                                            <label for="settings-splash-active-count">Most Active (Git) Limit</label>
                                            <input type="number" id="settings-splash-active-count" class="form-input" min="1" value="5">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="settings-section">
                                    <h3>Default Sorting</h3>
                                    <p class="section-desc">Choose the default way projects are sorted when viewing categories.</p>
                                    
                                    <div class="form-group" style="max-width: 300px;">
                                        <label for="settings-default-sort">Default Sort Mode</label>
                                        <select id="settings-default-sort" class="form-control" style="appearance: none; padding: 0.75rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); background: var(--bg-main); color: var(--text-main);">
                                            <option value="date-desc">Modified: Newest</option>
                                            <option value="date-asc">Modified: Oldest</option>
                                            <option value="created-desc">Created: Newest</option>
                                            <option value="created-asc">Created: Oldest</option>
                                            <option value="alpha-asc">Alphabetical: A-Z</option>
                                            <option value="alpha-desc">Alphabetical: Z-A</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Cache Settings Section --}}
                            <div id="settings-cache" class="settings-panel" style="display: none;">
                                <div class="settings-section">
                                    <h3 style="margin-top: 0;">Scanning Cache</h3>
                                    <p class="section-desc">Improve dashboard loading performance by caching the directory scanning results.</p>
                                    
                                    <div class="form-group-checkbox">
                                        <label class="switch" for="settings-cache-enabled">
                                            <input type="checkbox" id="settings-cache-enabled">
                                            <span class="slider"></span>
                                        </label>
                                        <label for="settings-cache-enabled" class="checkbox-label">Enable scan results cache</label>
                                    </div>
                                    
                                    <div class="form-group" id="cache-ttl-group" style="max-width: 300px; margin-top: 1rem;">
                                        <label for="settings-cache-ttl">Cache Expiry (TTL in seconds)</label>
                                        <input type="number" id="settings-cache-ttl" class="form-input" min="0" value="300">
                                        <span class="form-help">Time in seconds before the directory scanner runs again to refresh data.</span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Paths Settings Section --}}
                            <div id="settings-paths" class="settings-panel" style="display: none;">
                                <div class="settings-section">
                                    <h3 style="margin-top: 0;">Scan Locations</h3>
                                    <p class="section-desc">Specify directory paths to scan. Each location should contain <code>Active</code>, <code>Archive</code>, or <code>Sandbox</code> subfolders.</p>
                                    
                                    <div class="path-list" id="settings-path-list">
                                        {{-- Rendered dynamically by JavaScript --}}
                                    </div>
                                    
                                    <div class="add-path-row" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                        <input type="text" id="settings-add-path-input" class="form-input" placeholder="e.g., /Users/username/Sites" autocomplete="off" aria-label="New scan directory path" style="flex: 1;">
                                        <button type="button" id="settings-add-path-btn" class="btn btn-primary">Add Path</button>
                                    </div>
                                </div>
                            </div>

                            {{-- Domain Settings Section --}}
                            <div id="settings-domain" class="settings-panel" style="display: none;">
                                <div class="settings-section">
                                    <h3 style="margin-top: 0;">Local Domain</h3>
                                    <p class="section-desc">Configure the domain suffix used when launching your local sites.</p>
                                    
                                    <div class="form-group" style="max-width: 300px;">
                                        <label for="settings-domain-extension">Domain Extension (TLD)</label>
                                        <input type="text" id="settings-domain-extension" class="form-input" placeholder="e.g., test, local" value="test">
                                        <span class="form-help">Sites will open at <code>http://[project-name].[extension]</code>.</span>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Maintenance Settings Section --}}
                            <div id="settings-maintenance" class="settings-panel" style="display: none;">
                                <div class="settings-section" style="margin-bottom: 2rem;">
                                    <h3 style="margin-top: 0;">Version Sync Rules</h3>
                                    <p class="section-desc">Filter which projects are processed during bulk Version Synchronization. Use comma-separated lists.</p>
                                    
                                    <h4 style="margin: 1.5rem 0 0.5rem; font-size: 0.95rem; color: var(--text-primary);">Blacklist</h4>
                                    <div class="form-group-row">
                                        <div class="form-group">
                                            <label for="settings-sync-exclude-categories">Categories</label>
                                            <textarea id="settings-sync-exclude-categories" class="form-input" rows="2" placeholder="e.g., Sandbox, Archive"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="settings-sync-exclude-projects">Projects</label>
                                            <textarea id="settings-sync-exclude-projects" class="form-input" rows="2" placeholder="e.g., My Scratchpad"></textarea>
                                        </div>
                                    </div>
                                    
                                    <h4 style="margin: 1.5rem 0 0.5rem; font-size: 0.95rem; color: var(--text-primary);">Whitelist</h4>
                                    <div class="form-group-row">
                                        <div class="form-group">
                                            <label for="settings-sync-include-categories">Categories</label>
                                            <textarea id="settings-sync-include-categories" class="form-input" rows="2" placeholder="e.g., Active"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="settings-sync-include-projects">Projects</label>
                                            <textarea id="settings-sync-include-projects" class="form-input" rows="2" placeholder="e.g., DevPortal"></textarea>
                                        </div>
                                    </div>
                                    <span class="form-help" style="display: block; margin-top: 0.5rem;">If Whitelist is not empty, ONLY matching items are included.</span>
                                </div>

                                <div class="settings-section">
                                    <h3>Entry Point Testing Rules</h3>
                                    <p class="section-desc">Filter which projects are pinged during bulk Entry Point testing.</p>
                                    
                                    <h4 style="margin: 1.5rem 0 0.5rem; font-size: 0.95rem; color: var(--text-primary);">Blacklist</h4>
                                    <div class="form-group-row">
                                        <div class="form-group">
                                            <label for="settings-entry-exclude-categories">Categories</label>
                                            <textarea id="settings-entry-exclude-categories" class="form-input" rows="2" placeholder="e.g., Archive"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="settings-entry-exclude-projects">Projects</label>
                                            <textarea id="settings-entry-exclude-projects" class="form-input" rows="2" placeholder="e.g., Old Plugin"></textarea>
                                        </div>
                                    </div>
                                    
                                    <h4 style="margin: 1.5rem 0 0.5rem; font-size: 0.95rem; color: var(--text-primary);">Whitelist</h4>
                                    <div class="form-group-row">
                                        <div class="form-group">
                                            <label for="settings-entry-include-categories">Categories</label>
                                            <textarea id="settings-entry-include-categories" class="form-input" rows="2" placeholder="e.g., Active"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="settings-entry-include-projects">Projects</label>
                                            <textarea id="settings-entry-include-projects" class="form-input" rows="2" placeholder="e.g., DevPortal"></textarea>
                                        </div>
                                    </div>
                                    <span class="form-help" style="display: block; margin-top: 0.5rem;">If Whitelist is not empty, ONLY matching items are included.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="settings-footer" style="padding: 1.25rem 2rem; background: var(--bg-main); border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
                        <button type="button" id="settings-modal-cancel" class="btn">Discard Changes</button>
                        <button type="submit" id="settings-modal-save" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
        </main>

    </div>

    {{-- Project Details Drawer / Modal --}}
    <div id="project-details-modal" class="modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="details-modal-title">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <div class="details-header-title-group">
                    <h2 id="details-modal-title">Project Details</h2>
                    <span id="details-category-badge" class="badge">Active</span>
                </div>
                <button id="project-details-close" class="btn-close" aria-label="Close project details">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body">
                {{-- Quick Overview Row --}}
                <div class="details-section overview-section">
                    <p id="details-description" class="details-desc">No description available.</p>
                    
                    <div class="details-meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">App / Framework Version</span>
                            <span id="details-framework-version" class="meta-value">N/A</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Production Version</span>
                            <span id="details-production-version" class="meta-value">N/A</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Created Time</span>
                            <span id="details-created-at" class="meta-value">N/A</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Last Updated</span>
                            <span id="details-updated-at" class="meta-value">N/A</span>
                        </div>
                    </div>
                </div>

                {{-- Split Content (Left: Features & Commits, Right: Dependencies) --}}
                <div class="details-split-layout">
                    <div class="split-left">
                        {{-- Features Section --}}
                        <div class="details-section" id="details-features-section">
                            <h3>Key Features</h3>
                            <ul id="details-features-list" class="features-checklist">
                                {{-- Rendered dynamically by JS --}}
                            </ul>
                        </div>

                        {{-- Git Commits Section --}}
                        <div class="details-section" id="details-git-commits-section">
                            <h3>Recent Commits</h3>
                            <div class="commit-timeline" id="details-commit-timeline">
                                {{-- Rendered dynamically by JS --}}
                            </div>
                        </div>
                    </div>

                    <div class="split-right">
                        {{-- Dependencies Section --}}
                        <div class="details-section">
                            <h3>Build Dependencies</h3>
                            
                            {{-- Tabs for Composer vs NPM --}}
                            <div class="dep-tabs">
                                <button type="button" class="dep-tab-btn active" data-dep-tab="composer">Composer</button>
                                <button type="button" class="dep-tab-btn" data-dep-tab="npm">NPM</button>
                            </div>

                            <div class="dep-tab-content active" id="dep-tab-content-composer">
                                <div class="dep-subsection">
                                    <h4>Requires</h4>
                                    <div class="dep-grid" id="details-composer-req"></div>
                                </div>
                                <div class="dep-subsection">
                                    <h4>Dev Requires</h4>
                                    <div class="dep-grid" id="details-composer-dev"></div>
                                </div>
                            </div>

                            <div class="dep-tab-content" id="dep-tab-content-npm">
                                <div class="dep-subsection">
                                    <h4>Dependencies</h4>
                                    <div class="dep-grid" id="details-npm-req"></div>
                                </div>
                                <div class="dep-subsection">
                                    <h4>Dev Dependencies</h4>
                                    <div class="dep-grid" id="details-npm-dev"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="details-modal-sync-btn" class="btn btn-primary" style="margin-right: auto;">Sync Version</button>
                <button type="button" id="details-modal-close-btn" class="btn">Close</button>
            </div>
        </div>
    </div>

    {{-- Toast notification container --}}
    <div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="false"></div>

    <style>
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
