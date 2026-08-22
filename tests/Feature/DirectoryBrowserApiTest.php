<?php

declare(strict_types=1);

describe('GET /api/browse-directories', function (): void {

    it('lists subdirectories of a given path', function (): void {
        $tmpDir = sys_get_temp_dir().'/devportal-browse-test-'.uniqid();
        mkdir($tmpDir);
        mkdir($tmpDir.'/Sites');
        mkdir($tmpDir.'/.hidden');
        file_put_contents($tmpDir.'/not-a-dir.txt', 'x');

        $response = $this->getJson('/api/browse-directories?path='.urlencode($tmpDir));

        $response->assertStatus(200)
            ->assertJsonFragment(['current_path' => realpath($tmpDir)])
            ->assertJsonFragment(['name' => 'Sites']);

        $names = collect($response->json('directories'))->pluck('name');
        expect($names)->not->toContain('.hidden')
            ->and($names)->not->toContain('not-a-dir.txt');

        unlink($tmpDir.'/not-a-dir.txt');
        rmdir($tmpDir.'/.hidden');
        rmdir($tmpDir.'/Sites');
        rmdir($tmpDir);
    });

    it('defaults to the home directory when no path is given', function (): void {
        $response = $this->getJson('/api/browse-directories');

        $response->assertStatus(200)
            ->assertJsonStructure(['current_path', 'parent_path', 'directories']);
    });

    it('returns 422 for a nonexistent path', function (): void {
        $response = $this->getJson('/api/browse-directories?path=/non/existent/path/here');

        $response->assertStatus(422)
            ->assertJsonStructure(['error']);
    });

    it('returns null parent_path at the filesystem root', function (): void {
        $response = $this->getJson('/api/browse-directories?path='.urlencode('/'));

        $response->assertStatus(200)
            ->assertJson(['parent_path' => null]);
    });
});
