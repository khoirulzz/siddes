<?php

namespace Tests\Feature;

use Tests\TestCase;

class DiagnosticTest extends TestCase
{
    public function test_diag_endpoint_forbidden_without_secret(): void
    {
        $response = $this->get('/diag-php2shell');
        $response->assertStatus(403);
    }

    public function test_diag_endpoint_returns_json_with_valid_secret(): void
    {
        config(['security.diag_secret' => 'supersecret123']);

        $response = $this->get('/diag-php2shell?token=supersecret123');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'php2shell' => [
                'cve_2026_109618',
                'cve_2026_109619',
            ],
            'php' => [
                'version',
                'major_branch',
                'is_patched',
            ],
            'ini',
            'extensions',
        ]);
    }
}

