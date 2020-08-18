<?php

declare(strict_types = 1);

namespace Example\Tests\Functional\Controller;

use Example\Tests\BaseCase;

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
        $curl = $this->curl();

        // Send the request to the server
        $response = $curl->init(getenv('TEST_URL'))->send(false);

        $this->assertSame($curl->getStatusCode(), 200);
        $this->assertNull($curl->getError());
        $this->assertNotEmpty($response);
        $this->assertIsString($response);

        // Just look for a portion of the HTML view
        $this->assertStringContainsString('Create Example', $response);
    }
}
