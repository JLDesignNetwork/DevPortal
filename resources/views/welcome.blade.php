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
                <div class="stats">
                    <div class="stat-badge">
                        Active <span class="count" id="stats-active-count">…</span>
                    </div>
                    <div class="stat-badge">
                        Archive <span class="count" id="stats-archive-count">…</span>
                    </div>
                    <div class="stat-badge">
                        Sandbox <span class="count" id="stats-sandbox-count">…</span>
                    </div>
                </div>
                <button id="settings-toggle-btn" class="btn" title="Open Settings" aria-haspopup="dialog">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Settings
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
                >Active</button>
                <button
                    class="tab-button"
                    data-category="Archive"
                    role="tab"
                    aria-selected="false"
                    id="tab-archive"
                    aria-controls="project-list"
                >Archive</button>
                <button
                    class="tab-button"
                    data-category="Sandbox"
                    role="tab"
                    aria-selected="false"
                    id="tab-sandbox"
                    aria-controls="project-list"
                >Sandbox</button>
            </nav>

            <div class="search-wrapper" role="search">
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

        {{-- Project List --}}
        <main id="project-list" role="tabpanel" aria-labelledby="tab-dashboard">
            {{-- Cards rendered by JavaScript --}}
        </main>

    </div>

    {{-- Settings Modal Overlay --}}
    <div id="settings-modal" class="modal-overlay" aria-hidden="true" role="dialog" aria-labelledby="settings-modal-title">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="settings-modal-title">Portal Settings</h2>
                <button id="settings-modal-close" class="btn-close" aria-label="Close Settings">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <form id="settings-form" novalidate>
                <div class="modal-body">
                    {{-- Cache Settings Section --}}
                    <div class="settings-section">
                        <h3>Scanning Cache</h3>
                        <p class="section-desc">Improve dashboard loading performance by caching the directory scanning results.</p>
                        
                        <div class="form-group-checkbox">
                            <label class="switch" for="settings-cache-enabled">
                                <input type="checkbox" id="settings-cache-enabled">
                                <span class="slider"></span>
                            </label>
                            <label for="settings-cache-enabled" class="checkbox-label">Enable scan results cache</label>
                        </div>
                        
                        <div class="form-group" id="cache-ttl-group">
                            <label for="settings-cache-ttl">Cache Expiry (TTL in seconds)</label>
                            <input type="number" id="settings-cache-ttl" class="form-input" min="0" value="300">
                            <span class="form-help">Time in seconds before the directory scanner runs again to refresh data.</span>
                        </div>
                    </div>

                    {{-- Dashboard Settings Section --}}
                    <div class="settings-section">
                        <h3>Dashboard Widgets</h3>
                        <p class="section-desc">Configure the splash/dashboard display widget limits.</p>
                        
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="settings-splash-recent-count">Recently Updated Projects Limit</label>
                                <input type="number" id="settings-splash-recent-count" class="form-input" min="1" value="5">
                            </div>
                            <div class="form-group" style="margin-top: 1rem;">
                                <label for="settings-splash-active-count">Most Active Projects (Git) Limit</label>
                                <input type="number" id="settings-splash-active-count" class="form-input" min="1" value="5">
                            </div>
                        </div>
                    </div>

                    {{-- URL Settings Section --}}
                    <div class="settings-section">
                        <h3>Local Domain</h3>
                        <p class="section-desc">Configure the domain suffix used when launching your local sites.</p>
                        
                        <div class="form-group">
                            <label for="settings-domain-extension">Domain Extension (TLD)</label>
                            <input type="text" id="settings-domain-extension" class="form-input" placeholder="e.g., test, local" value="test">
                            <span class="form-help">Sites will open at <code>http://[project-name].[extension]</code>.</span>
                        </div>
                    </div>

                    {{-- Allowlisted Paths Section --}}
                    <div class="settings-section">
                        <h3>Scan Locations</h3>
                        <p class="section-desc">Specify directory paths to scan. Each location should contain <code>Active</code>, <code>Archive</code>, or <code>Sandbox</code> subfolders.</p>
                        
                        <div class="path-list" id="settings-path-list">
                            {{-- Rendered dynamically by JavaScript --}}
                        </div>
                        
                        <div class="add-path-row">
                            <input type="text" id="settings-add-path-input" class="form-input" placeholder="e.g., /Users/username/Sites" autocomplete="off" aria-label="New scan directory path">
                            <button type="button" id="settings-add-path-btn" class="btn btn-primary">Add Path</button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="settings-modal-cancel" class="btn">Cancel</button>
                    <button type="submit" id="settings-modal-save" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
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
