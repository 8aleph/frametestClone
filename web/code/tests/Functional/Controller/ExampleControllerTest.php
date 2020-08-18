<?php

declare(strict_types = 1);

namespace Example\Tests\Functional\Controller;

use Example\Tests\BaseCase;

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
        $curl = $this->curl();

        // Send the request to the server
        $response = $curl->init(getenv('TEST_URL') . '/example/create')
            ->setPost([
                'code'        => 'TESTCODE',
                'description' => 'Test description'
            ])
            ->send(false);

        $this->assertSame($curl->getStatusCode(), 200);
        $this->assertNull($curl->getError());
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
        $curl = $this->curl();

        // Send the request to the server
        $response = $curl->init(getenv('TEST_URL') . '/example/create')
            ->setPost(['description' => 'Test description'])
            ->send();

        $this->assertSame($curl->getStatusCode(), 400);
        $this->assertNull($curl->getError());
        $this->assertNotEmpty($response);
        $this->assertSame('Example code missing', $response['message']);
    }

    /**
     * Test creating an example errors on a missing example description.
     * 
     * @return void
     */
    public function testCreateExampleErrorsOnMissingDescription(): void
    {
        $curl = $this->curl();

        // Send the request to the server
        $response = $curl->init(getenv('TEST_URL') . '/example/create')
            ->setPost(['code' => 'TESTCODE'])
            ->send();

        $this->assertSame($curl->getStatusCode(), 400);
        $this->assertNull($curl->getError());
        $this->assertNotEmpty($response);
        $this->assertSame('Example description missing', $response['message']);
    }

    /**
     * Test creating an example errors on an invalid request type (route expects POST).
     * 
     * @return void
     */
    public function testCreateExampleErrorsOnInvalidRequestType(): void
    {
        $curl = $this->curl();

        // Send the request to the server
        $response = $curl->init(getenv('TEST_URL') . '/example/create')->send();

        $this->assertSame($curl->getStatusCode(), 405);
        $this->assertNull($curl->getError());
        $this->assertNotEmpty($response);
        $this->assertStringContainsString('Method Not Allowed', $response['message']);
    }

    /**
     * Test creating an example errors on a duplicate entry.
     * 
     * @return void
     */
    public function testCreateExampleErrorsOnDuplicateEntry(): void
    {
        $curl = $this->curl();

        // Send the request to the server
        $response = $curl->init(getenv('TEST_URL') . '/example/create')
            ->setPost([
                'code'        => 'TESTCODE',
                'description' => 'Test description'
            ])
            ->send(false);

        $response = $curl->init(getenv('TEST_URL') . '/example/create')
            ->setPost([
                'code'        => 'TESTCODE',
                'description' => 'Test description'
            ])
            ->send();

        $this->assertSame($curl->getStatusCode(), 409);
        $this->assertNull($curl->getError());
        $this->assertNotEmpty($response);
        $this->assertStringContainsString('Failed to save. Record already exists', $response['message']);
    }
}
