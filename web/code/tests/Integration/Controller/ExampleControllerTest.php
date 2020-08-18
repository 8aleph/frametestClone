<?php

declare(strict_types = 1);

namespace Example\Tests\Integration\Controller;

use Example\Tests\BaseCase;
use Mini\Http\Request;

/**
 * Example entrypoint logic test.
 */
class ExampleControllerTest extends BaseCase
{
    /**
     * Refresh test table.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->truncateTable('master_example');
    }

    /**
     * Test creating an example and displaying its data.
     * 
     * @return void
     */
    public function testCreateExample(): void
    {
        $request = new Request([], [
            'code'        => 'TESTCODE',
            'description' => 'Test description'
        ]);

        $response = $this->getClass('Example\Controller\ExampleController')->createExample($request);

        $this->assertNotEmpty($response);
        $this->assertIsString($response);

        // Look for the newly created example
        $this->assertStringContainsString('TESTCODE', $response);
        $this->assertStringContainsString('Test description', $response);

        $results = $this->getDatabase()->select([
            'sql' => 'SELECT * FROM ' . getenv('DB_SCHEMA') . '.master_example',
        ]);

        $this->assertNotEmpty($results);
        $this->assertSame(1, $results['example_id']);
        $this->assertIsString($results['created']);
        $this->assertNotEmpty($results['created']);
        $this->assertSame('TESTCODE', $results['code']);
        $this->assertSame('Test description', $results['description']);
    }
}
