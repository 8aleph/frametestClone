<?php

declare(strict_types = 1);

namespace Example\Tests\Unit\Controller;

use Example\Tests\BaseCase;
use Mini\Http\Request;

/**
 * Home entrypoint logic test.
 */
class HomeControllerTest extends BaseCase
{
    /**
     * Test showing the default page.
     * 
     * @return void
     */
    public function testIndex(): void
    {
        $response = $this->getClass('Example\Controller\HomeController')->index(new Request);

        $this->assertNotEmpty($response);
        $this->assertIsString($response);

        // Just look for a portion of the HTML view
        $this->assertStringContainsString('Create Example', $response);
    }
}
