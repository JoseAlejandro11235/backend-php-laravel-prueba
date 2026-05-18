<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    public function test_openapi_ui_is_available_in_local_environment(): void
    {
        $this->get('/docs/api')
            ->assertSuccessful();
    }

    public function test_openapi_json_spec_is_available(): void
    {
        $response = $this->get('/docs/api.json');

        $response->assertSuccessful();
        $response->assertJsonStructure(['openapi', 'info', 'paths']);
    }
}
