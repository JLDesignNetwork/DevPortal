# Dev Portal

Dev Portal is a local developer dashboard built with Laravel, Vanilla CSS, and modern JavaScript. It allows developers to browse, search, configure, and manage local development directories (projects) across multiple watch locations and categories on their machine.

---

## Key Features

1. **Dashboard Home View**:
   - **Recently Updated**: Displays the last $x$ modified projects, sorted dynamically.
   - **Most Active (30 Days)**: Displays the top $x$ projects sorted by their Git commit counts in the last 30 days.
   - Limit configurations can be customized in the portal settings.

2. **Multi-Category Project Scanning**:
   - Automatically scans allowlisted watch directories for subfolders categorized as `Active`, `Archive`, or `Sandbox`.
   - Caches scanning results to maximize page loading speeds (fully customizable TTL).

3. **Rich Metadata Extraction**:
   - **Framework version**: Detects composer.json declarations (e.g. Laravel versions) and version tags.
   - **Production version**: Automatically inspects `package.json`, `composer.json`, git tags, and `CHANGELOG.md` version headers.
   - **Changelog preview**: Displays logs from `CHANGELOG.md` (filtering out `[Unreleased]` sections).
   - **Timestamps**: Identifies folder creation (birth) dates using macOS native stat parameters alongside last modified timestamps.
   - **Key Features checklist**: Scrapes list items listed under a `Features` header inside the project's README.
   - **Dependencies mapping**: Compiles dependency packages into organized tabs for NPM and Composer (grouped by standard vs dev requirements).
   - **Git Integration**: Retrieves the active Git branch name, count of modified/untracked files (dirty state), and lists the last 5 commits (author, message, hash, and relative dates).

4. **Location-Aware Moving**:
   - Allows relocating project directories to different watch locations and categories via simple dropdown selection.
   - Automatically handles directory creation and collision check routines.

5. **Secure Project Deletion**:
   - Permanent project directory removal directly from the portal UI.
   - Guarded by a double-confirmation prompt to prevent accidental loss.
   - **Strict Security Verification**: The backend strictly validates that the project is nested exactly at `{allowlisted_path}/{category}/{projectName}`. System roots and directories outside allowlisted paths are mathematically shielded from execution.

6. **Configurable Domain Extension**:
   - Set the local TLD suffix (e.g., `.test`, `.local`, or `.localhost`) under portal settings.
   - The "Open Site" buttons automatically compile to use your configured suffix.

---

## Configuration & Usage

Once the Dev Portal is running, click the **Settings** icon in the top right header to customize your environment:

1. **Scan Locations**:
   - Add one or more watch directories on your local drive (e.g., `/Users/username/Sites` or `/Users/username/Code`).
   - Inside each watch directory, Dev Portal expects three category subfolders: `Active/`, `Archive/`, and `Sandbox/`.
2. **Local Domain**:
   - Set the local domain extension (TLD) corresponding to your local server setup (e.g. `test` for Laravel Valet/Herd, `local`, or `localhost`).
3. **Scanning Cache**:
   - Toggle directory scanner results caching to optimize dashboard load performance.
   - Customize the cache TTL (Time to Live) value in seconds.
4. **Dashboard Widgets**:
   - Adjust limits for the "Recently Updated" and "Most Active (Git)" dashboard widgets.

---

## Technology Stack

- **Backend Framework**: Laravel 11/12+
- **PHP Version**: PHP 8.5+ strictly typed (typed properties, constructor promotion, match expressions)
- **Frontend Layer**: Semantic HTML5 & Vanilla Javascript (Vite build pipeline)
- **Styles**: Custom CSS variables with HSL color-scheme pairing (native dark mode support)
- **Database**: SQLite (local settings persistence)
- **Package Managers**: Composer, `pnpm`
- **Test Runner**: Pest PHP

---

## Setup Instructions

### Prerequisites
- PHP 8.5+
- Composer
- `pnpm` (Node.js)

### 1. Installation
Clone the repository, enter the directory, and install dependencies:
```bash
composer install
pnpm install
```

### 2. Configure Environment
Copy `.env.example` to `.env` and set up database parameters:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Migration
Run migrations to initialize settings tables:
```bash
php artisan migrate
```

### 4. Build Frontend Assets
Compile stylesheets and javascript bundles:
```bash
pnpm run build
```

### 5. Start Development Server
Start the local server:
```bash
php artisan serve
```
Open `http://localhost:8000` in your web browser.

---

## Testing

Pest PHP is configured for all unit and feature tests:
```bash
./vendor/bin/pest
```
We enforce strict style checks using Laravel Pint:
```bash
./vendor/bin/pint
```

