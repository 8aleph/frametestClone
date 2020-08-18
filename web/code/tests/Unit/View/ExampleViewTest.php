<?php

declare(strict_types = 1);

namespace Example\Tests\Unit\Controller;

use Example\Tests\BaseCase;
use Mini\Controller\Exception\BadInputException;

/**
 * Example view builder test.
 */
class ExampleViewTest extends BaseCase
{
    /**
     * Test getting an example view to display its data.
     * 
     * @return void
     */
    public function testGet(): void
    {
        $this->mockDatabaseGetProcess();

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
        
        $this->mockDatabaseGetUnkownIdProcess();

        $this->getClass('Example\View\ExampleView')->get(2);
    }

    /**
     * Mock the database process for the example create endpoint.
     *
     * @return void
     */
    protected function mockDatabaseGetProcess(): void
    {
        $database = $this->getMock('Mini\Database\Database');

        // Setup the database mock
        $database->shouldReceive('select')
            ->once()
            ->withArgs($this->withDatabaseInput([1]))
            ->andReturn([
                'id'          => 1,
                'created'     => '2020-07-14 12:00:00',
                'code'        => 'TESTCODE',
                'description' => 'Test description'
            ]);

        $this->setMockDatabase($database);
    }

    /**
     * Mock the database process for the example create endpoint.
     *
     * @return void
     */
    protected function mockDatabaseGetUnkownIdProcess(): void
    {
        $database = $this->getMock('Mini\Database\Database');

        // Setup the database mock
        $database->shouldReceive('select')
            ->once()
            ->withArgs($this->withDatabaseInput([2]))
            ->andReturn([]);

        $this->setMockDatabase($database);
    }
}
