<?php

declare(strict_types = 1);

namespace Mini\Http;

use Mini\Http\CsvResponse;
use Mini\Http\JsonResponse;
use Mini\Http\Response;
use Mini\View\Renderer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * HTTP response builder.
 */
class ResponseFactory
{
    /**
     * The view builder.
     *
     * @var Renderer|null
     */
    protected $renderer = null;

    /**
     * Create a new response factory instance.
     *
     * @param Renderer $renderer view builder
     */
    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Create a new response instance.
     *
     * @param string $content optional content to send
     * @param int    $status  status code
     * @param array  $headers optional headers
     * 
     * @return Response response
     */
    public function make(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Create a new "no content" response.
     *
     * @param int   $status  status code
     * @param array $headers optional headers
     * 
     * @return Response response
     */
    public function noContent(int $status = 204, array $headers = []): Response
    {
        return $this->make('', $status, $headers);
    }

    /**
     * Create a new response for a given view.
     *
     * @param string $view    view to create
     * @param array  $data    optional view data
     * @param int    $status  status code
     * @param array  $headers headers
     * 
     * @return Response response
     */
    public function view($view, array $data = [], int $status = 200, array $headers = []): Response
    {
        return $this->make($this->renderer->render($view, $data), $status, $headers);
    }

    /**
     * Create a new JSON response instance.
     *
     * @param mixed $data    optional content to send
     * @param int   $status  status code
     * @param array $headers optional headers
     * @param int   $options json options
     * 
     * @return JsonResponse response
     */
    public function json($data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    /**
     * Create a new streamed response instance.
     *
     * @param callable $callback callback
     * @param int      $status   status code
     * @param array    $headers  optional headers
     * 
     * @return StreamedResponse response
     */
    public function stream(callable $callback, int $status = 200, array $headers = []): StreamedResponse
    {
        return new StreamedResponse($callback, $status, $headers);
    }

    /**
     * Create a new streamed response instance and download it to the browser.
     *
     * @param callable $callback    callback
     * @param string   $name        download name
     * @param array    $headers     optional headers
     * @param string   $disposition content disposition
     * 
     * @return StreamedResponse response
     */
    public function streamDownload(
        callable $callback,
        string $name,
        array $headers = [],
        string $disposition = 'attachment'
    ): StreamedResponse {
        $response = new StreamedResponse($callback, 200, $headers);

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition($disposition, $name));

        return $response;
    }

    /**
     * Create a new file download response.
     *
     * @param SplFileInfo|string $file        file
     * @param string             $name        optional download name
     * @param array              $headers     optional headers
     * @param string             $disposition content disposition
     *
     * @return BinaryFileResponse response
     */
    public function download(
        $file,
        string $name = null,
        array $headers = [],
        string $disposition = 'attachment'
    ): BinaryFileResponse {
        $response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

        // Default to basename of actual file if download name is not specified
        return $response->setContentDisposition($disposition, $name ?? basename($file));
    }

    /**
     * Create a new file response.
     *
     * @param SplFileInfo|string $file    file
     * @param array              $headers optional headers
     *
     * @return BinaryFileResponse response
     */
    public function file($file, array $headers = []): BinaryFileResponse
    {
        return new BinaryFileResponse($file, 200, $headers);
    }

    /**
     * Create a new streamed response instance and download it to the browser.
     *
     * @param array       $data    data
     * @param string|null $name    optional download name
     * @param array       $options optional csv generator options
     * @param array       $headers optional headers
     * 
     * @return CsvResponse response
     */
    public function csv(array $data, ?string $name = null, array $options = [], array $headers = []): CsvResponse
    {
        return new CsvResponse($name, $data, $options, $headers);
    }
}
