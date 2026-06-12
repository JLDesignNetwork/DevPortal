/**
 * @since 1.2.0
 * @version 1.2.0
 */
import { JLMeter } from './components/meter.js';

if (!customElements.get('jl-meter')) {
    customElements.define('jl-meter', JLMeter);
}

document.addEventListener('DOMContentLoaded', () => {
    // State management
    let projects = [];
    let activeCategory = 'Dashboard';
    let searchQuery = '';
    let sortMode = 'date-desc';
    let settings = {
        cache_enabled: false,
        cache_ttl: 300,
        allowlisted_paths: [],
        splash_recent_count: 5,
        splash_active_count: 5,
        domain_extension: 'test',
        sync_exclude_categories: ['Sandbox'],
        sync_exclude_projects: [],
        sync_include_categories: [],
        sync_include_projects: [],
        entry_exclude_categories: ['Archive'],
        entry_exclude_projects: [],
        entry_include_categories: [],
        entry_include_projects: [],
        default_sort: 'date-desc'
    };

    // Cache DOM elements
    const projectListContainer = document.getElementById('project-list');
    const tabButtons = document.querySelectorAll('.controls-bar .tab-button');
    const searchInput = document.getElementById('search-input');
    const statsActiveCount = document.getElementById('stats-active-count');
    const statsArchiveCount = document.getElementById('stats-archive-count');
    const statsSandboxCount = document.getElementById('stats-sandbox-count');

    // Settings View Elements
    const settingsView = document.getElementById('settings-view');
    const maintenanceView = document.getElementById('maintenance-view');
    const controlsBar = document.querySelector('.controls-bar');
    const controlsActions = document.getElementById('controls-actions');
    const settingsToggleBtn = document.getElementById('settings-toggle-btn');
    const maintenanceToggleBtn = document.getElementById('maintenance-toggle-btn');
    const settingsCancelBtn = document.getElementById('settings-modal-cancel');
    const settingsForm = document.getElementById('settings-form');
    const settingsCacheEnabled = document.getElementById('settings-cache-enabled');
    const settingsCacheTtl = document.getElementById('settings-cache-ttl');
    const settingsPathList = document.getElementById('settings-path-list');
    const settingsAddPathInput = document.getElementById('settings-add-path-input');
    const settingsAddPathBtn = document.getElementById('settings-add-path-btn');
    const cacheTtlGroup = document.getElementById('cache-ttl-group');
    const settingsSplashRecentCount = document.getElementById('settings-splash-recent-count');
    const settingsSplashActiveCount = document.getElementById('settings-splash-active-count');
    const settingsDomainExtension = document.getElementById('settings-domain-extension');
    const settingsSyncExcludeCategories = document.getElementById('settings-sync-exclude-categories');
    const settingsSyncExcludeProjects = document.getElementById('settings-sync-exclude-projects');
    const settingsSyncIncludeCategories = document.getElementById('settings-sync-include-categories');
    const settingsSyncIncludeProjects = document.getElementById('settings-sync-include-projects');
    const settingsEntryExcludeCategories = document.getElementById('settings-entry-exclude-categories');
    const settingsEntryExcludeProjects = document.getElementById('settings-entry-exclude-projects');
    const settingsEntryIncludeCategories = document.getElementById('settings-entry-include-categories');
    const settingsEntryIncludeProjects = document.getElementById('settings-entry-include-projects');
    const settingsDefaultSort = document.getElementById('settings-default-sort');

    // Project Details Modal Elements
    const projectDetailsModal = document.getElementById('project-details-modal');
    const projectDetailsClose = document.getElementById('project-details-close');
    const projectDetailsCloseBtn = document.getElementById('details-modal-close-btn');
    const detailsModalTitle = document.getElementById('details-modal-title');
    const detailsCategoryBadge = document.getElementById('details-category-badge');
    const detailsDescription = document.getElementById('details-description');
    const detailsFrameworkVersion = document.getElementById('details-framework-version');
    const detailsProductionVersion = document.getElementById('details-production-version');
    const detailsCreatedAt = document.getElementById('details-created-at');
    const detailsUpdatedAt = document.getElementById('details-updated-at');
    const detailsFeaturesSection = document.getElementById('details-features-section');
    const detailsFeaturesList = document.getElementById('details-features-list');
    const detailsGitCommitsSection = document.getElementById('details-git-commits-section');
    const detailsCommitTimeline = document.getElementById('details-commit-timeline');
    const detailsComposerReq = document.getElementById('details-composer-req');
    const detailsComposerDev = document.getElementById('details-composer-dev');
    const detailsNpmReq = document.getElementById('details-npm-req');
    const detailsNpmDev = document.getElementById('details-npm-dev');
    const depTabBtns = document.querySelectorAll('.dep-tab-btn');
    const depTabContents = document.querySelectorAll('.dep-tab-content');

    // Fetch CSRF Token
    const getCsrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    // Show toast message
    const showToast = (message, type = 'success') => {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        // Icon
        const iconSvg = type === 'success' 
            ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4L12 14.01l-3-3" /></svg>`
            : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

        toast.innerHTML = `
            ${iconSvg}
            <span>${message}</span>
        `;

        container.appendChild(toast);

        // Force reflow for transitions
        toast.offsetHeight;

        toast.classList.add('show');

        // Remove after delay
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    };

    // Fetch projects from backend
    async function loadProjects() {
        projectListContainer.innerHTML = `
            <div class="empty-state">
                <svg class="spinner" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite; margin: 0 auto 1rem; display: block;">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25" />
                    <path d="M12 2a10 10 0 0 1 10 10" />
                </svg>
                <h3>Scanning Sites...</h3>
                <p>Reading readme, dependencies, git repositories, and commits.</p>
            </div>
        `;

        try {
            const response = await fetch('/api/projects', {
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            });
            if (!response.ok) throw new Error('Failed to fetch projects.');
            projects = await response.json();
            updateStats();
            renderProjects();
        } catch (error) {
            console.error(error);
            projectListContainer.innerHTML = `
                <div class="empty-state" style="border-color: var(--danger);">
                    <h3 style="color: var(--danger);">Scanning Failed</h3>
                    <p>${error.message || 'Could not load projects. Please verify your local web server configuration.'}</p>
                    <button class="btn btn-primary" id="retry-btn" style="margin-top: 1rem;">Retry Scan</button>
                </div>
            `;
            const retryBtn = document.getElementById('retry-btn');
            if (retryBtn) retryBtn.addEventListener('click', loadProjects);
        }
    }

    // Update category badge counters
    function updateStats() {
        const counts = { Active: 0, Archive: 0, Sandbox: 0 };
        projects.forEach(p => {
            if (counts[p.category] !== undefined) {
                counts[p.category]++;
            }
        });
        if (statsActiveCount) statsActiveCount.textContent = String(counts.Active);
        if (statsArchiveCount) statsArchiveCount.textContent = String(counts.Archive);
        if (statsSandboxCount) statsSandboxCount.textContent = String(counts.Sandbox);
    }

    // Move project category & base path via AJAX
    async function moveProject(sourcePath, targetBasePath, targetCategory) {
        try {
            const response = await fetch('/api/projects/move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    source_path: sourcePath,
                    target_base_path: targetBasePath,
                    target_category: targetCategory
                })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to move project.');
            }

            showToast(result.message || 'Project relocated successfully.');
            // Reload all projects to update scanning details and location
            await loadProjects();
        } catch (error) {
            showToast(error.message, 'error');
            // Re-render to reset dropdown state on failure
            renderProjects();
        }
    }

    // Delete project completely from disk via AJAX
    async function deleteProject(projectPath, projectName) {
        try {
            const response = await fetch('/api/projects', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({
                    path: projectPath
                })
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Failed to delete project.');
            }

            showToast(result.message || `Project "${projectName}" was deleted successfully.`);
            // Reload all projects to update scanning details
            await loadProjects();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    // Render dashboard widgets
    function renderDashboard() {
        projectListContainer.innerHTML = '';
        
        const controlsActions = document.getElementById('controls-actions');
        if (controlsActions) {
            controlsActions.style.display = 'none';
        }

        const dashboardGrid = document.createElement('div');
        dashboardGrid.className = 'dashboard-grid';

        // 1. Recently Updated Projects
        const recentProjects = [...projects]
            .sort((a, b) => b.last_modified_timestamp - a.last_modified_timestamp)
            .slice(0, settings.splash_recent_count);

        const recentWidget = document.createElement('div');
        recentWidget.className = 'dashboard-widget';
        recentWidget.innerHTML = `
            <div class="widget-header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <h3>Recently Updated</h3>
            </div>
            <div class="widget-list" id="recent-widget-list"></div>
        `;

        const recentList = recentWidget.querySelector('#recent-widget-list');
        if (recentProjects.length === 0) {
            recentList.innerHTML = '<div class="widget-empty">No projects found.</div>';
        } else {
            recentProjects.forEach(project => {
                const item = document.createElement('div');
                item.className = 'widget-item';
                
                let categoryClass = 'badge-git-branch';
                if (project.category === 'Active') categoryClass = 'badge-git-clean';
                else if (project.category === 'Archive') categoryClass = 'badge-time';

                item.innerHTML = `
                    <div class="widget-item-header">
                        <button type="button" class="project-title-btn widget-item-title" data-project-path="${project.path}">
                            ${project.name}
                        </button>
                        <span class="badge ${categoryClass}">${project.category}</span>
                    </div>
                    <p class="widget-item-desc">${project.description || 'No description found in README.md'}</p>
                    <div class="widget-item-meta">
                        <span>Updated: ${project.last_modified}</span>
                    </div>
                `;
                recentList.appendChild(item);
            });
        }

        // 2. Most Active Projects
        const activeProjects = [...projects]
            .sort((a, b) => b.git_activity_count - a.git_activity_count)
            .slice(0, settings.splash_active_count);

        const activeWidget = document.createElement('div');
        activeWidget.className = 'dashboard-widget';
        activeWidget.innerHTML = `
            <div class="widget-header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                <h3>Most Active (30 Days)</h3>
            </div>
            <div class="widget-list" id="active-widget-list"></div>
        `;

        const activeList = activeWidget.querySelector('#active-widget-list');
        if (activeProjects.length === 0) {
            activeList.innerHTML = '<div class="widget-empty">No projects found.</div>';
        } else {
            activeProjects.forEach(project => {
                const item = document.createElement('div');
                item.className = 'widget-item';
                
                let gitHtml = '';
                if (project.git_branch) {
                    gitHtml = `
                        <span class="badge badge-git-branch" style="margin-right: 0.5rem;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 3v12M18 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM18 9a9 9 0 0 1-9 9" /></svg>
                            ${project.git_branch}
                        </span>
                    `;
                }

                item.innerHTML = `
                    <div class="widget-item-header">
                        <button type="button" class="project-title-btn widget-item-title" data-project-path="${project.path}">
                            ${project.name}
                        </button>
                        <span class="badge badge-activity-count">${project.git_activity_count} commits</span>
                    </div>
                    <p class="widget-item-desc">${project.description || 'No description found in README.md'}</p>
                    <div class="widget-item-meta">
                        ${gitHtml}
                        <span>Category: ${project.category}</span>
                    </div>
                `;
                activeList.appendChild(item);
            });
        }

        dashboardGrid.appendChild(recentWidget);
        dashboardGrid.appendChild(activeWidget);
        projectListContainer.appendChild(dashboardGrid);

        // Bind title button listeners in widgets
        document.querySelectorAll('.widget-item-title').forEach(btn => {
            btn.addEventListener('click', () => {
                const path = btn.dataset.projectPath;
                const project = projects.find(p => p.path === path);
                if (project) {
                    openProjectDetails(project);
                }
            });
        });
    }

    // Render cards list based on filters
    function renderProjects() {
        if (activeCategory === 'Dashboard') {
            renderDashboard();
            return;
        }

        const controlsActions = document.getElementById('controls-actions');
        if (controlsActions) {
            controlsActions.style.display = 'flex';
        }

        projectListContainer.innerHTML = '';

        // Filter projects
        const filtered = projects.filter(project => {
            const matchesCategory = project.category === activeCategory;
            const matchesSearch = searchQuery === '' || 
                project.name.toLowerCase().includes(searchQuery) ||
                (project.description && project.description.toLowerCase().includes(searchQuery)) ||
                project.relative_path.toLowerCase().includes(searchQuery);
            return matchesCategory && matchesSearch;
        });

        // Sort projects
        filtered.sort((a, b) => {
            const cleanNameA = (a.name || '').replace(/^[^a-zA-Z0-9]+/, '').trim().toLowerCase();
            const cleanNameB = (b.name || '').replace(/^[^a-zA-Z0-9]+/, '').trim().toLowerCase();
            
            switch (sortMode) {
                case 'date-asc':
                    return a.last_modified_timestamp - b.last_modified_timestamp;
                case 'created-desc':
                    return b.created_at_timestamp - a.created_at_timestamp;
                case 'created-asc':
                    return a.created_at_timestamp - b.created_at_timestamp;
                case 'alpha-asc':
                    return cleanNameA.localeCompare(cleanNameB);
                case 'alpha-desc':
                    return cleanNameB.localeCompare(cleanNameA);
                case 'date-desc':
                default:
                    return b.last_modified_timestamp - a.last_modified_timestamp;
            }
        });

        if (filtered.length === 0) {
            projectListContainer.innerHTML = `
                <div class="empty-state">
                    <h3>No Projects Found</h3>
                    <p>${searchQuery ? 'Adjust your search terms and try again.' : 'This category contains no project directories.'}</p>
                </div>
            `;
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'projects-grid';

        filtered.forEach(project => {
            const card = document.createElement('div');
            card.className = 'project-card';
            
            // Build Git Badge
            let gitBadgeHtml = '';
            if (project.git_branch !== null) {
                const dirtyClass = project.git_dirty_count > 0 ? 'badge-git-dirty' : 'badge-git-clean';
                const dirtyLabel = project.git_dirty_count > 0 ? `${project.git_dirty_count} files modified` : 'Clean';
                
                gitBadgeHtml = `
                    <span class="badge badge-git-branch">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 3v12M18 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6 21a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM18 9a9 9 0 0 1-9 9" /></svg>
                        ${project.git_branch}
                    </span>
                    <span class="badge ${dirtyClass}">
                        ${dirtyLabel}
                    </span>
                `;
            }

            // Build Changelog Preview
            let changelogPreviewHtml = '';
            if (project.changelog_version) {
                const dateStr = project.changelog_date ? ` (${project.changelog_date})` : '';
                changelogPreviewHtml = `
                    <div class="changelog-preview">
                        <div class="changelog-preview-header">
                            <span>Changelog: v${project.changelog_version}${dateStr}</span>
                        </div>
                        <div class="changelog-preview-content">${project.changelog_content || 'No detailed logs.'}</div>
                    </div>
                `;
            }

            // Options for Move select dropdown - Location-aware
            const projectDirName = project.path.split('/').pop();
            const selectOptionsHtml = settings.allowlisted_paths.flatMap(basePath => {
                const baseLabel = basePath.split('/').pop() || basePath;
                return ['Active', 'Archive', 'Sandbox'].map(cat => {
                    const optionDest = `${basePath}/${cat}/${projectDirName}`;
                    const isSelected = (project.path === optionDest);
                    return `<option value="${basePath}|${cat}" ${isSelected ? 'selected' : ''}>Move to ${baseLabel}: ${cat}</option>`;
                });
            }).join('');

            card.innerHTML = `
                <div>
                    <div class="project-header">
                        <h2 class="project-title">
                            <button type="button" class="project-title-btn" data-project-path="${project.path}">
                                ${project.name}
                            </button>
                        </h2>
                        <span class="project-version-badge">${
                            (() => {
                                let v = project.production_version && project.production_version !== 'N/A' 
                                    ? project.production_version 
                                    : (project.version || 'N/A');
                                if (v !== 'N/A' && !v.toLowerCase().startsWith('v') && !v.toLowerCase().startsWith('laravel')) {
                                    return 'v' + v;
                                }
                                return v;
                            })()
                        }</span>
                    </div>
                    <p class="project-description">${project.description || 'No description found in README.md'}</p>
                    
                    <div class="project-badges">
                        ${gitBadgeHtml}
                        <span class="badge badge-time" title="Last Updated">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2" /></svg>
                            Updated: ${project.last_modified}
                        </span>
                    </div>
                    
                    ${changelogPreviewHtml}
                </div>

                <div class="project-actions">
                    <div class="action-buttons">
                        ${project.has_web_entry ? `
                        <a href="http://${project.relative_path.split('/')[1].toLowerCase()}.${settings.domain_extension || 'test'}" target="_blank" class="btn btn-primary" title="Open site in browser">
                            Open Site
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
                        </a>
                        ` : ''}
                        <button class="btn btn-icon copy-path-btn" data-path="${project.path}" title="Copy absolute path to clipboard">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                        <button class="btn btn-icon btn-danger delete-project-btn" data-path="${project.path}" data-name="${project.name}" title="Delete project from disk">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>

                    <div class="select-category-wrapper">
                        <select class="select-category" data-source-path="${project.path}">
                            ${selectOptionsHtml}
                        </select>
                        <span class="select-arrow">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </div>
                </div>
            `;

            grid.appendChild(card);
        });

        projectListContainer.appendChild(grid);

        // Add Event Listeners to Card buttons/controls
        addCardEventListeners();
    }

    // Bind events for copy path buttons and category selects
    function addCardEventListeners() {
        // Click on project title to open details view
        document.querySelectorAll('.project-title-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const path = btn.dataset.projectPath;
                const project = projects.find(p => p.path === path);
                if (project) {
                    openProjectDetails(project);
                }
            });
        });

        // Copy Path Button
        document.querySelectorAll('.copy-path-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const path = btn.dataset.path;
                try {
                    await navigator.clipboard.writeText(path);
                    btn.classList.add('tooltip-success', 'active');
                    setTimeout(() => {
                        btn.classList.remove('tooltip-success', 'active');
                    }, 1500);
                } catch (err) {
                    console.error('Failed to copy text: ', err);
                }
            });
        });

        // Category Select Dropdown Change
        document.querySelectorAll('.select-category').forEach(select => {
            select.addEventListener('change', (e) => {
                const sourcePath = select.dataset.sourcePath;
                const [targetBasePath, targetCategory] = e.target.value.split('|');
                const projectDirName = sourcePath.split('/').pop();

                if (confirm(`Are you sure you want to move project "${projectDirName}" to "${targetCategory}"?`)) {
                    moveProject(sourcePath, targetBasePath, targetCategory);
                } else {
                    // Reset dropdown back to original
                    renderProjects();
                }
            });
        });

        // Delete Project Button Click
        document.querySelectorAll('.delete-project-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const path = btn.dataset.path;
                const name = btn.dataset.name;

                if (confirm(`Are you absolutely sure you want to permanently delete the project "${name}" and all of its files from disk?\n\nThis action CANNOT be undone.`)) {
                    deleteProject(path, name);
                }
            });
        });
    }

    // --- Settings Modal Logic ---

    // Load settings from backend
    async function loadSettings() {
        try {
            const response = await fetch('/api/settings', {
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            });
            if (!response.ok) throw new Error('Failed to load settings.');
            settings = await response.json();
            
            // Apply default sort if sortSelect exists
            if (settings.default_sort) {
                sortMode = settings.default_sort;
                const sortSelect = document.getElementById('sort-select');
                if (sortSelect) sortSelect.value = sortMode;
            }

            populateSettingsForm();
        } catch (error) {
            console.error(error);
            showToast('Could not load portal settings.', 'error');
        }
    }

    // Populate the form fields with current settings state
    function populateSettingsForm() {
        if (settingsCacheEnabled) {
            settingsCacheEnabled.checked = settings.cache_enabled;
        }
        if (settingsCacheTtl) {
            settingsCacheTtl.value = settings.cache_ttl;
        }
        if (settingsSplashRecentCount) {
            settingsSplashRecentCount.value = settings.splash_recent_count;
        }
        if (settingsSplashActiveCount) {
            settingsSplashActiveCount.value = settings.splash_active_count;
        }
        if (settingsDomainExtension) {
            settingsDomainExtension.value = settings.domain_extension || 'test';
        }
        if (settingsSyncExcludeCategories) settingsSyncExcludeCategories.value = (settings.sync_exclude_categories || []).join(', ');
        if (settingsSyncExcludeProjects) settingsSyncExcludeProjects.value = (settings.sync_exclude_projects || []).join(', ');
        if (settingsSyncIncludeCategories) settingsSyncIncludeCategories.value = (settings.sync_include_categories || []).join(', ');
        if (settingsSyncIncludeProjects) settingsSyncIncludeProjects.value = (settings.sync_include_projects || []).join(', ');
        if (settingsEntryExcludeCategories) settingsEntryExcludeCategories.value = (settings.entry_exclude_categories || []).join(', ');
        if (settingsEntryExcludeProjects) settingsEntryExcludeProjects.value = (settings.entry_exclude_projects || []).join(', ');
        if (settingsEntryIncludeCategories) settingsEntryIncludeCategories.value = (settings.entry_include_categories || []).join(', ');
        if (settingsEntryIncludeProjects) settingsEntryIncludeProjects.value = (settings.entry_include_projects || []).join(', ');
        if (settingsDefaultSort) settingsDefaultSort.value = settings.default_sort || 'date-desc';
        toggleCacheTtlVisibility();
        renderSettingsPaths();
    }

    // Toggle cache TTL visibility based on cache checkbox
    function toggleCacheTtlVisibility() {
        if (!cacheTtlGroup || !settingsCacheEnabled) return;
        if (settingsCacheEnabled.checked) {
            cacheTtlGroup.style.display = 'flex';
        } else {
            cacheTtlGroup.style.display = 'none';
        }
    }

    // Render allowlisted scan paths inside settings list
    function renderSettingsPaths() {
        if (!settingsPathList) return;
        settingsPathList.innerHTML = '';

        settings.allowlisted_paths.forEach((path, index) => {
            const pathItem = document.createElement('div');
            pathItem.className = 'path-item';
            pathItem.innerHTML = `
                <div class="path-details">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span class="path-text" title="${path}">${path}</span>
                </div>
                <button type="button" class="btn-delete" data-index="${index}" aria-label="Delete path">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            `;
            settingsPathList.appendChild(pathItem);
        });

        // Delete button listener
        settingsPathList.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index, 10);
                settings.allowlisted_paths.splice(idx, 1);
                renderSettingsPaths();
            });
        });
    }

    // Open Settings View
    function openSettingsView() {
        loadSettings();
        if (settingsView && projectListContainer) {
            projectListContainer.style.display = 'none';
            maintenanceView.style.display = 'none';
            if (controlsActions) controlsActions.style.display = 'none';
            settingsView.style.display = 'block';
            
            // Deactivate all tab buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
        }
    }

    // Open Maintenance View
    function openMaintenanceView() {
        if (maintenanceView && projectListContainer) {
            projectListContainer.style.display = 'none';
            settingsView.style.display = 'none';
            if (controlsActions) controlsActions.style.display = 'none';
            maintenanceView.style.display = 'block';
            
            // Deactivate all tab buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
        }
    }

    // Return to Dashboard View
    function closeSpecialViews() {
        if (settingsView && maintenanceView && projectListContainer) {
            settingsView.style.display = 'none';
            maintenanceView.style.display = 'none';
            if (controlsActions) controlsActions.style.display = 'flex';
            projectListContainer.style.display = 'block';
            
            // Activate the correct tab based on activeCategory
            tabButtons.forEach(btn => {
                if (btn.dataset.category === activeCategory) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            renderProjects();
        }
    }

    // Bind view toggles
    if (settingsToggleBtn) {
        settingsToggleBtn.addEventListener('click', openSettingsView);
    }
    if (maintenanceToggleBtn) {
        maintenanceToggleBtn.addEventListener('click', openMaintenanceView);
    }
    if (settingsCancelBtn) {
        settingsCancelBtn.addEventListener('click', closeSpecialViews);
    }

    // Settings Sidebar Nav Logic
    const settingsNavBtns = document.querySelectorAll('.settings-nav-btn');
    const settingsPanels = document.querySelectorAll('.settings-panel');
    settingsNavBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetSection = btn.dataset.section;
            
            // Update active states
            settingsNavBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            settingsPanels.forEach(panel => {
                if (panel.id === targetSection) {
                    panel.style.display = 'block';
                    panel.classList.add('active');
                } else {
                    panel.style.display = 'none';
                    panel.classList.remove('active');
                }
            });
        });
    });

    if (settingsCacheEnabled) {
        settingsCacheEnabled.addEventListener('change', toggleCacheTtlVisibility);
    }

    // Add scanning path to settings
    if (settingsAddPathBtn && settingsAddPathInput) {
        const addPathAction = () => {
            const path = settingsAddPathInput.value.trim();
            if (path === '') {
                showToast('Please enter a directory path.', 'error');
                return;
            }
            if (settings.allowlisted_paths.includes(path)) {
                showToast('This scan location is already added.', 'error');
                return;
            }
            settings.allowlisted_paths.push(path);
            settingsAddPathInput.value = '';
            renderSettingsPaths();
        };

        settingsAddPathBtn.addEventListener('click', addPathAction);
        settingsAddPathInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addPathAction();
            }
        });
    }

    // Save Settings
    if (settingsForm) {
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const saveBtn = document.getElementById('settings-modal-save');
            const originalText = saveBtn ? saveBtn.textContent : 'Save Settings';
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }

            const parseCommaList = (input) => input ? input.value.split(',').map(s => s.trim()).filter(s => s) : [];

            const payload = {
                cache_enabled: settingsCacheEnabled ? settingsCacheEnabled.checked : false,
                cache_ttl: settingsCacheTtl ? parseInt(settingsCacheTtl.value, 10) : 300,
                allowlisted_paths: settings.allowlisted_paths,
                splash_recent_count: settingsSplashRecentCount ? parseInt(settingsSplashRecentCount.value, 10) : 5,
                splash_active_count: settingsSplashActiveCount ? parseInt(settingsSplashActiveCount.value, 10) : 5,
                domain_extension: settingsDomainExtension ? settingsDomainExtension.value.trim() : 'test',
                sync_exclude_categories: parseCommaList(settingsSyncExcludeCategories),
                sync_exclude_projects: parseCommaList(settingsSyncExcludeProjects),
                sync_include_categories: parseCommaList(settingsSyncIncludeCategories),
                sync_include_projects: parseCommaList(settingsSyncIncludeProjects),
                entry_exclude_categories: parseCommaList(settingsEntryExcludeCategories),
                entry_exclude_projects: parseCommaList(settingsEntryExcludeProjects),
                entry_include_categories: parseCommaList(settingsEntryIncludeCategories),
                entry_include_projects: parseCommaList(settingsEntryIncludeProjects),
                default_sort: settingsDefaultSort ? settingsDefaultSort.value : 'date-desc'
            };

            try {
                const response = await fetch('/api/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Failed to save settings.');
                }

                showToast(result.message || 'Settings saved successfully.');
                await loadSettings();
                closeSpecialViews();
                loadProjects(); // Reload projects list with new locations/cache settings
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = originalText;
                }
            }
        });
    }

    // --- Project Details Modal Logic ---

    // Render dependency badges grid
    function renderDependencyGrid(container, depsObject) {
        container.innerHTML = '';
        if (!depsObject || Object.keys(depsObject).length === 0) {
            return;
        }

        Object.entries(depsObject).forEach(([name, version]) => {
            const badge = document.createElement('div');
            badge.className = 'dep-badge';
            badge.innerHTML = `
                <span class="dep-name" title="${name}">${name}</span>
                <span class="dep-version" title="${version}">${version}</span>
            `;
            container.appendChild(badge);
        });
    }

    // Open project details modal with all metadata
    function openProjectDetails(project) {
        if (!projectDetailsModal) return;

        projectDetailsModal.dataset.currentProject = project.path;
        detailsModalTitle.textContent = project.name;
        detailsCategoryBadge.textContent = project.category;
        
        // Style category badge nicely
        detailsCategoryBadge.className = 'badge';
        if (project.category === 'Active') {
            detailsCategoryBadge.classList.add('badge-git-clean');
        } else if (project.category === 'Archive') {
            detailsCategoryBadge.classList.add('badge-time');
        } else {
            detailsCategoryBadge.classList.add('badge-git-branch');
        }

        detailsDescription.textContent = project.description || 'No description found in README.md';
        
        // Framework / App version
        detailsFrameworkVersion.textContent = project.version || 'N/A';
        
        // Production version
        detailsProductionVersion.textContent = project.production_version || 'N/A';
        
        // Timestamps
        detailsCreatedAt.textContent = project.created_at || 'N/A';
        detailsUpdatedAt.textContent = project.last_modified || 'N/A';

        // Features Checklist
        if (project.features && project.features.length > 0) {
            detailsFeaturesSection.style.display = 'block';
            detailsFeaturesList.innerHTML = '';
            project.features.forEach(feat => {
                const li = document.createElement('li');
                li.textContent = feat;
                detailsFeaturesList.appendChild(li);
            });
        } else {
            detailsFeaturesSection.style.display = 'none';
        }

        // Git Commits
        if (project.git_commits && project.git_commits.length > 0) {
            detailsGitCommitsSection.style.display = 'block';
            detailsCommitTimeline.innerHTML = '';
            project.git_commits.forEach(commit => {
                const item = document.createElement('div');
                item.className = 'commit-item';
                item.innerHTML = `
                    <div class="commit-header">
                        <span class="commit-author">${commit.author}</span>
                        <span class="commit-time">${commit.date}</span>
                        <span class="commit-hash" title="${commit.hash}">${commit.short_hash}</span>
                    </div>
                    <div class="commit-msg">${commit.message}</div>
                `;
                detailsCommitTimeline.appendChild(item);
            });
        } else {
            detailsGitCommitsSection.style.display = 'none';
        }

        // Build Dependencies
        const deps = project.dependencies || {};
        renderDependencyGrid(detailsComposerReq, deps.composer);
        renderDependencyGrid(detailsComposerDev, deps.composer_dev);
        renderDependencyGrid(detailsNpmReq, deps.npm);
        renderDependencyGrid(detailsNpmDev, deps.npm_dev);

        // Show modal
        projectDetailsModal.classList.add('open');
        projectDetailsModal.setAttribute('aria-hidden', 'false');

        // Reset tabs (show composer active, npm hidden)
        depTabBtns.forEach(btn => {
            if (btn.dataset.depTab === 'composer') {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        depTabContents.forEach(content => {
            if (content.id === 'dep-tab-content-composer') {
                content.classList.add('active');
            } else {
                content.classList.remove('active');
            }
        });
    }

    function closeProjectDetails() {
        if (projectDetailsModal) {
            projectDetailsModal.classList.remove('open');
            projectDetailsModal.setAttribute('aria-hidden', 'true');
        }
    }

    // Bind details modal close actions
    if (projectDetailsClose) {
        projectDetailsClose.addEventListener('click', closeProjectDetails);
    }
    if (projectDetailsCloseBtn) {
        projectDetailsCloseBtn.addEventListener('click', closeProjectDetails);
    }
    if (projectDetailsModal) {
        projectDetailsModal.addEventListener('click', (e) => {
            if (e.target === projectDetailsModal) {
                closeProjectDetails();
            }
        });
    }

    // Hook up dependencies tab toggles
    depTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            depTabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const tabName = btn.dataset.depTab;
            depTabContents.forEach(content => {
                if (content.id === `dep-tab-content-${tabName}`) {
                    content.classList.add('active');
                } else {
                    content.classList.remove('active');
                }
            });
        });
    });

    // Set up tabs click events
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.category;
            closeSpecialViews();
        });
    });

    // Set up search filter event
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            renderProjects();
        });
    }

    // Set up sort event
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            sortMode = e.target.value;
            renderProjects();
        });
    }

    // Maintenance: Sync All
    const maintenanceSyncAllBtn = document.getElementById('maintenance-sync-all-btn');
    const maintenanceSyncLog = document.getElementById('maintenance-sync-log');
    if (maintenanceSyncAllBtn) {
        maintenanceSyncAllBtn.addEventListener('click', async () => {
            if (projects.length === 0) return;

            const excludeCats = (settings.sync_exclude_categories || []).map(s => s.toLowerCase());
            const excludeProjs = (settings.sync_exclude_projects || []).map(s => s.toLowerCase());
            const includeCats = (settings.sync_include_categories || []).map(s => s.toLowerCase());
            const includeProjs = (settings.sync_include_projects || []).map(s => s.toLowerCase());

            const filteredProjects = projects.filter(project => {
                const cat = (project.category || '').toLowerCase();
                const name = (project.name || '').toLowerCase();

                // 1. Project-level rules take ultimate precedence
                if (includeProjs.includes(name)) return true;
                if (excludeProjs.includes(name)) return false;
                
                // 2. Category-level rules take secondary precedence
                if (includeCats.includes(cat)) return true;
                if (excludeCats.includes(cat)) return false;

                // 3. Determine default behavior for items not explicitly matched
                const hasIncludeRules = includeCats.length > 0 || includeProjs.length > 0;
                const hasExcludeRules = excludeCats.length > 0 || excludeProjs.length > 0;

                // If they provided category whitelists, default to DENY for unmatched categories
                if (includeCats.length > 0) return false;
                
                // If they ONLY provided whitelists, strict default DENY
                if (hasIncludeRules && !hasExcludeRules) return false;

                // Otherwise (they provided blacklists, or hybrid where whitelist acts as exception), default ALLOW
                return true;
            });

            if (filteredProjects.length === 0) {
                maintenanceSyncLog.innerHTML = `<div style="color: var(--text-muted);">No projects matched the configured Whitelist/Blacklist criteria.</div>`;
                return;
            }
            
            maintenanceSyncAllBtn.disabled = true;
            maintenanceSyncAllBtn.textContent = 'Syncing...';
            
            maintenanceSyncLog.innerHTML = `
                <div style="margin-bottom: 1rem;">
                    <div style="margin-bottom: 0.5rem; color: var(--text-main); font-weight: bold;" id="sync-meter-label">Syncing Projects (0/${filteredProjects.length})</div>
                    <jl-meter id="sync-meter" value="0" max="${filteredProjects.length}" animated="true" variant="glassmorphic" stripes="true" theme="primary"></jl-meter>
                </div>
                <div id="sync-log-details"></div>
            `;
            
            try {
                let logHtml = '';
                let successCount = 0;
                let failCount = 0;
                
                const meter = document.getElementById('sync-meter');
                const meterLabel = document.getElementById('sync-meter-label');
                const logDetails = document.getElementById('sync-log-details');
                
                for (let i = 0; i < filteredProjects.length; i++) {
                    const project = filteredProjects[i];
                    try {
                        const response = await fetch('/api/maintenance/sync-version', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'Accept': 'application/json', 
                                'X-CSRF-TOKEN': getCsrfToken() 
                            },
                            body: JSON.stringify({ path: project.path })
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            successCount++;
                            logHtml += `<div style="margin-bottom: 0.5rem;"><strong style="color:var(--text-main);">${project.name}</strong> -> <span style="color:var(--success);">${result.data.version}</span><br><small style="color:var(--text-muted);">${result.data.updated_files.join(', ')}</small></div>`;
                        } else {
                            failCount++;
                            const errorMessage = result.error || result.message || 'Error';
                            logHtml += `<div style="color: var(--danger); margin-bottom: 0.5rem;"><strong>${project.name}</strong>: ${errorMessage}</div>`;
                        }
                    } catch (e) {
                        failCount++;
                        logHtml += `<div style="color: var(--danger); margin-bottom: 0.5rem;"><strong>${project.name}</strong>: Fetch failed</div>`;
                    }
                    
                    meter.setAttribute('value', i + 1);
                    meterLabel.textContent = `Syncing Projects (${i + 1}/${filteredProjects.length})`;
                    logDetails.innerHTML = logHtml;
                }
                
                logHtml = `<div style="color: var(--success); margin-bottom: 1rem; font-size: 1.1rem; font-weight: bold;">Sync complete! ${successCount} successful, ${failCount} failed.</div>` + logHtml;
                logDetails.innerHTML = logHtml;
                loadProjects();
            } catch (error) {
                maintenanceSyncLog.innerHTML = `<div style="color: var(--danger);">Critical Error: ${error.message}</div>`;
            } finally {
                maintenanceSyncAllBtn.disabled = false;
                maintenanceSyncAllBtn.textContent = 'Sync All Project Versions';
            }
        });
    }

    // Maintenance: Sync Single Project
    const detailsModalSyncBtn = document.getElementById('details-modal-sync-btn');
    if (detailsModalSyncBtn) {
        detailsModalSyncBtn.addEventListener('click', async () => {
            const projectPath = document.getElementById('project-details-modal').dataset.currentProject;
            if (!projectPath) return;
            
            detailsModalSyncBtn.disabled = true;
            detailsModalSyncBtn.textContent = 'Syncing...';
            
            try {
                const response = await fetch('/api/maintenance/sync-version', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json', 
                        'X-CSRF-TOKEN': getCsrfToken() 
                    },
                    body: JSON.stringify({ path: projectPath })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(result.message);
                    document.getElementById('details-production-version').textContent = result.data.version;
                    loadProjects(); // reload in background
                } else {
                    showToast(result.error || 'Failed to sync version.', 'error');
                }
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                detailsModalSyncBtn.disabled = false;
                detailsModalSyncBtn.textContent = 'Sync Version';
            }
        });
    }

    // Maintenance Subtabs
    const maintenanceSubtabs = document.querySelectorAll('#maintenance-view .tab-button[data-subtab]');
    maintenanceSubtabs.forEach(btn => {
        btn.addEventListener('click', () => {
            maintenanceSubtabs.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            const subtabId = btn.getAttribute('data-subtab');
            document.getElementById('maintenance-tab-version-sync').style.display = subtabId === 'version-sync' ? 'block' : 'none';
            document.getElementById('maintenance-tab-entry-points').style.display = subtabId === 'entry-points' ? 'block' : 'none';
        });
    });

    // Maintenance: Test Entry Points
    const maintenanceTestEntryBtn = document.getElementById('maintenance-test-entry-btn');
    const maintenanceTestLog = document.getElementById('maintenance-test-log');
    
    if (maintenanceTestEntryBtn) {
        maintenanceTestEntryBtn.addEventListener('click', async () => {
            maintenanceTestEntryBtn.disabled = true;
            maintenanceTestEntryBtn.textContent = 'Testing Entry Points...';
            maintenanceTestLog.innerHTML = `<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding: 2rem;">
                <jl-meter value="50" max="100" style="margin-bottom:1rem;"></jl-meter>
                <div style="color:var(--text-muted);">Pinging project URLs. This may take a few moments...</div>
            </div>`;
            
            try {
                const response = await fetch('/api/maintenance/test-entry-points', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json', 
                        'X-CSRF-TOKEN': getCsrfToken() 
                    }
                });
                const result = await response.json();
                
                if (result.success) {
                    maintenanceTestLog.innerHTML = `<div class="markdown-table-wrapper" style="width:100%; overflow-x:auto;">${result.html}</div>`;
                    // Basic styling for the returned markdown table
                    const tables = maintenanceTestLog.querySelectorAll('table');
                    tables.forEach(table => {
                        table.style.width = '100%';
                        table.style.borderCollapse = 'collapse';
                        table.querySelectorAll('th, td').forEach(cell => {
                            cell.style.padding = '0.75rem';
                            cell.style.borderBottom = '1px solid var(--border)';
                            cell.style.textAlign = 'left';
                        });
                        table.querySelectorAll('th').forEach(th => {
                            th.style.backgroundColor = 'rgba(0,0,0,0.2)';
                        });
                    });
                } else {
                    maintenanceTestLog.innerHTML = `<div style="color: var(--danger);">Failed to run tests: ${result.error || 'Unknown Error'}</div>`;
                }
            } catch (error) {
                maintenanceTestLog.innerHTML = `<div style="color: var(--danger);">Critical Error: ${error.message}</div>`;
            } finally {
                maintenanceTestEntryBtn.disabled = false;
                maintenanceTestEntryBtn.textContent = 'Test Entry Points';
            }
        });
    }

    // Initial load
    async function init() {
        try {
            await loadSettings();
        } catch (e) {
            console.error('Failed to load settings on startup', e);
        }
        await loadProjects();
    }
    init();
});
