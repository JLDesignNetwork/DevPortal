document.addEventListener('DOMContentLoaded', () => {
    // State management
    let projects = [];
    let activeCategory = 'Dashboard';
    let searchQuery = '';
    let settings = {
        cache_enabled: false,
        cache_ttl: 300,
        allowlisted_paths: [],
        splash_recent_count: 5,
        splash_active_count: 5,
        domain_extension: 'test'
    };

    // Cache DOM elements
    const projectListContainer = document.getElementById('project-list');
    const tabButtons = document.querySelectorAll('.tab-button');
    const searchInput = document.getElementById('search-input');
    const statsActiveCount = document.getElementById('stats-active-count');
    const statsArchiveCount = document.getElementById('stats-archive-count');
    const statsSandboxCount = document.getElementById('stats-sandbox-count');

    // Settings Modal Elements
    const settingsModal = document.getElementById('settings-modal');
    const settingsToggleBtn = document.getElementById('settings-toggle-btn');
    const settingsCloseBtn = document.getElementById('settings-modal-close');
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
            const response = await fetch('/api/projects');
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
        
        // Hide search wrapper when dashboard is active
        if (searchInput) {
            searchInput.parentElement.style.display = 'none';
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

        // Show search wrapper when dashboard is not active
        if (searchInput) {
            searchInput.parentElement.style.display = 'block';
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
                        <span class="project-version-badge">v${project.version || 'N/A'}</span>
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
                        <a href="http://${project.relative_path.split('/')[1]}.${settings.domain_extension || 'test'}" target="_blank" class="btn btn-primary" title="Open site in browser">
                            Open Site
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
                        </a>
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
            const response = await fetch('/api/settings');
            if (!response.ok) throw new Error('Failed to load settings.');
            settings = await response.json();
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

    // Open Settings Modal
    function openSettingsModal() {
        loadSettings();
        if (settingsModal) {
            settingsModal.classList.add('open');
            settingsModal.setAttribute('aria-hidden', 'false');
        }
    }

    // Close Settings Modal
    function closeSettingsModal() {
        if (settingsModal) {
            settingsModal.classList.remove('open');
            settingsModal.setAttribute('aria-hidden', 'true');
        }
    }

    // Bind settings buttons if they exist
    if (settingsToggleBtn) {
        settingsToggleBtn.addEventListener('click', openSettingsModal);
    }
    if (settingsCloseBtn) {
        settingsCloseBtn.addEventListener('click', closeSettingsModal);
    }
    if (settingsCancelBtn) {
        settingsCancelBtn.addEventListener('click', closeSettingsModal);
    }
    if (settingsModal) {
        settingsModal.addEventListener('click', (e) => {
            if (e.target === settingsModal) {
                closeSettingsModal();
            }
        });
    }

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

            const payload = {
                cache_enabled: settingsCacheEnabled ? settingsCacheEnabled.checked : false,
                cache_ttl: settingsCacheTtl ? parseInt(settingsCacheTtl.value, 10) : 300,
                allowlisted_paths: settings.allowlisted_paths,
                splash_recent_count: settingsSplashRecentCount ? parseInt(settingsSplashRecentCount.value, 10) : 5,
                splash_active_count: settingsSplashActiveCount ? parseInt(settingsSplashActiveCount.value, 10) : 5,
                domain_extension: settingsDomainExtension ? settingsDomainExtension.value.trim() : 'test'
            };

            try {
                const response = await fetch('/api/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Failed to save settings.');
                }

                showToast(result.message || 'Settings saved successfully.');
                closeSettingsModal();
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
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            activeCategory = button.dataset.category;
            renderProjects();
        });
    });

    // Set up search filter event
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            renderProjects();
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
