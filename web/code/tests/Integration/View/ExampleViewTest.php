<?php

declare(strict_types = 1);

namespace Example\Tests\Integration\View;

use Example\Tests\BaseCase;
use Mini\Controller\Exception\BadInputException;

/**
 * Example view builder test.
 */
class ExampleViewTest extends BaseCase
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
     * Test getting an example view to display its data.
     * 
     * @return void
     */
    public function testGet(): void
    {
        $this->loadDatabaseData('master_example', [
            [
                1,
                '2020-07-14 12:00:00',
                'TESTCODE',
                'Test description'
            ]
        ]);

        $view = $this->getClass('Example\View\ExampleView')->get(1);

        $this->assertNotEmpty($view);
        $this->assertIsString($view);

        // Look for the newly created example
        $this->assertStringContainsString('TESTCODE', $view);
        $this->assertStringContainsString('Test description', $view);
    }

    /**
     * Test getting an example view errors on unknown example ID.
     * 
     * @return void
     */
    public function testGetErrorsOnUnknownExampleId(): void
    {
        $this->expectException(BadInputException::class);

        $this->getClass('Example\View\ExampleView')->get(1);
    }
}
