<?php

declare(strict_types = 1);

namespace Example\Tests\Unit\Controller;

use Example\Tests\BaseCase;
use Mini\Controller\Exception\BadInputException;
use Mini\Http\Request;
use Mini\Util\DateTime;

/**
 * Example entrypoint logic test.
 */
class ExampleControllerTest extends BaseCase
{
    /**
     * Test creating an example and displaying its data.
     * 
     * @return void
     */
    public function testCreateExample(): void
    {
        $this->mockDatabaseCreateProcess();

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
    }

    /**
     * Test creating an example errors on a missing example code.
     * 
     * @return void
     */
    public function testCreateExampleErrorsOnMissingCode(): void
    {
        $this->expectException(BadInputException::class);

        $request = new Request([], ['description' => 'Test description']);

        $this->getClass('Example\Controller\ExampleController')->createExample($request);
    }

    /**
     * Test creating an example errors on a missing example description.
     * 
     * @return void
     */
    public function testCreateExampleErrorsOnMissingDescription(): void
    {
        $this->expectException(BadInputException::class);

        $request = new Request([], ['code' => 'TESTCODE']);

        $this->getClass('Example\Controller\ExampleController')->createExample($request);
    }

    /**
     * Mock the database process for the example create endpoint.
     *
     * @return void
     */
    protected function mockDatabaseCreateProcess(): void
    {
        // Override the created column input set by `now()`
        DateTime::setTestNow(DateTime::create(2020, 7, 14, 12, 00, 00));

        $database = $this->getMock('Mini\Database\Database');

        // Setup the database mock
        $database->shouldReceive('statement')
            ->once()
            ->withArgs($this->withDatabaseInput(['2020-07-14 12:00:00', 'TESTCODE', 'Test description']))
            ->andReturn(1);

        $database->shouldReceive('validateAffected')->once();

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
}
