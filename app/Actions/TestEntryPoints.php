<?php

/**
 * @since 1.2.0
 *
 * @version 1.2.0
 */

declare(strict_types=1);

namespace App\Actions;

use App\Services\ProjectScanner;
use App\Services\SettingsService;
use Illuminate\Support\Facades\File;

class TestEntryPoints
{
    public function __construct(
        private readonly ProjectScanner $scanner,
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Test all allowlisted projects that have a web entry point.
     * Writes results to .scratches/project_errors.md and returns the markdown.
     */
    public function execute(): string
    {
        $projects = $this->scanner->scan($this->settingsService->getAllowlistedPaths());
        $domainExtension = $this->settingsService->get('domain_extension', 'test');

        $excludeCats = array_map('strtolower', $this->settingsService->getArray('entry_exclude_categories', ['Archive']));
        $excludeProjs = array_map('strtolower', $this->settingsService->getArray('entry_exclude_projects', []));
        $includeCats = array_map('strtolower', $this->settingsService->getArray('entry_include_categories', []));
        $includeProjs = array_map('strtolower', $this->settingsService->getArray('entry_include_projects', []));

        $results = [];

        foreach ($projects as $project) {
            $cat = strtolower($project['category'] ?? '');
            $name = strtolower($project['name'] ?? '');

            // 1. Project-level rules take ultimate precedence
            if (in_array($name, $includeProjs, true)) {
                // allowed
            } elseif (in_array($name, $excludeProjs, true)) {
                continue; // Skip
            }
            // 2. Category-level rules take secondary precedence
            elseif (in_array($cat, $includeCats, true)) {
                // allowed
            } elseif (in_array($cat, $excludeCats, true)) {
                continue; // Skip
            }
            // 3. Determine default behavior for items not explicitly matched
            else {
                $hasIncludeRules = ! empty($includeCats) || ! empty($includeProjs);
                $hasExcludeRules = ! empty($excludeCats) || ! empty($excludeProjs);

                // If they provided category whitelists, default to DENY for unmatched categories
                if (! empty($includeCats)) {
                    continue;
                }

                // If they ONLY provided whitelists, strict default DENY
                if ($hasIncludeRules && ! $hasExcludeRules) {
                    continue;
                }
            }

            // Only test projects with entry points
            if (empty($project['has_web_entry'])) {
                continue;
            }

            $parts = explode('/', $project['relative_path']);
            $folderName = $parts[1] ?? null;

            if (! $folderName) {
                continue;
            }

            // Convert folder name to lowercase as Valet/dnsmasq is case-sensitive and will 404 otherwise
            $domainPrefix = strtolower($folderName);
            // Encode spaces in folder name to avoid cURL errors (if any spaces exist)
            $encodedFolderName = str_replace(' ', '%20', $domainPrefix);
            $url = "http://{$encodedFolderName}.{$domainExtension}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $status = 'Pass';
            $errorMessage = '';

            if ($curlError) {
                $status = 'Fail';
                $errorMessage = 'cURL Error: '.$curlError;
            } elseif ($httpCode >= 400) {
                $status = 'Fail';
                $errorMessage = 'HTTP '.$httpCode;
                if ($content && preg_match('/<title>(.*?)<\/title>/', $content, $matches)) {
                    $title = trim(strip_tags($matches[1]));
                    if ($title !== '' && $title !== 'Error') {
                        $errorMessage .= ' - '.$title;
                    }
                }
            } elseif ($content) {
                // Strip HTML tags so that <b>Fatal error</b> becomes Fatal error
                $cleanContent = strip_tags($content);

                // Scan the content for common PHP/Laravel errors even if it returned HTTP 200
                $errorSignatures = [
                    'Fatal error:' => 'Fatal PHP Error',
                    'Parse error:' => 'PHP Parse Error',
                    'Warning:' => 'PHP Warning',
                    'SQLSTATE[' => 'Database/SQL Error',
                    'Whoops! There was an error.' => 'Laravel Exception',
                    'Illuminate\Database\QueryException' => 'Laravel Query Exception',
                    'Uncaught Error:' => 'Uncaught PHP Error',
                    'Uncaught RuntimeException:' => 'Uncaught Runtime Exception',
                    'Ignition' => 'Laravel Ignition Error',
                ];

                foreach ($errorSignatures as $signature => $humanReadable) {
                    if (stripos($cleanContent, $signature) !== false) {
                        $status = 'Fail';
                        $errorMessage = 'Page loaded but contains error: '.$humanReadable;
                        break;
                    }
                }
            }

            $catKey = $project['category'] ?? 'Uncategorized';
            if (! isset($results[$catKey])) {
                $results[$catKey] = [];
            }

            $results[$catKey][] = [
                'name' => $project['name'],
                'url' => urldecode($url),
                'status' => $status,
                'error' => $errorMessage,
            ];
        }

        $markdown = "# Project Entry Page Status\n\n";

        if (empty($results)) {
            $markdown .= "No projects found matching the criteria.\n";
        } else {
            foreach ($results as $category => $items) {
                $markdown .= "## {$category}\n\n";
                $markdown .= "| Project Name | URL | Status | Error Details |\n";
                $markdown .= "| :--- | :--- | :--- | :--- |\n";
                foreach ($items as $result) {
                    $errorEscaped = str_replace('|', '&#124;', $result['error']);
                    $markdown .= "| {$result['name']} | `{$result['url']}` | {$result['status']} | {$errorEscaped} |\n";
                }
                $markdown .= "\n";
            }
        }

        // Ensure .scratches directory exists
        $scratchesPath = base_path('.scratches');
        if (! File::exists($scratchesPath)) {
            File::makeDirectory($scratchesPath, 0755, true);
        }

        File::put($scratchesPath.'/project_errors.md', $markdown);

        return $markdown;
    }
}
