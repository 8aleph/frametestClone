<?php

declare(strict_types = 1);

namespace Mini\Http;

use Mini\Util\DateTime;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * HTTP CSV response.
 */
class CsvResponse extends StreamedResponse
{
    /**
     * Setup.
     *
     * @param array       $data    data
     * @param string|null $name    optional download name
     * @param array       $options optional csv generator options
     * @param array       $headers optional headers
     */
    public function __construct(array $data, ?string $name = null, array $options = [], array $headers = [])
    {
        parent::__construct(
            $this->buildCsvGeneratorStream($data, $this->buildGeneratorOptions($options)),
            200,
            $this->buildHeaders($this->buildDownloadFileName($name), $headers)
        );
    }

    /**
     * Create a StreamedResponse callback that will generate a CSV
     * in memory and then echo it out.
     *
     * @param array $data    data
     * @param array $options csv options to override
     * 
     * @return callable csv generation callback
     */
    protected function buildCsvGeneratorStream(array $data, array $options): callable
    {
        return function () use ($data, $options) {
            $file = fopen('php://output', 'w');

            foreach ($data as $item) {
                fputcsv($file, $item, $options['delimiter']);
            }

            fclose($file);
        };
    }

    /**
     * Build out the CSV generator options.
     *
     * @param array $options optional csv generator options to override
     * 
     * @return array options
     */
    protected function buildGeneratorOptions(array $options): array
    {
        return array_replace($this->getDefaultGeneratorOptions(), $options);
    }

    /**
     * Build out the headers for the CSV response.
     *
     * @param string $name    download name
     * @param array  $headers optional headers to add/override
     * 
     * @return array headers
     */
    protected function buildHeaders(string $name, array $headers): array
    {
        return array_replace($this->getDefaultHeaders($name), $headers);
    }

    /**
     * Get the default CSV generator options.
     *
     * @return array options
     */
    protected function getDefaultGeneratorOptions(): array
    {
        return [
            'delimiter' => ','
        ];
    }

    /**
     * Get the default CSV response headers.
     *
     * @param string $name download name
     * 
     * @return array headers
     */
    protected function getDefaultHeaders(string $name): array
    {
        return [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $name),
            'Pragma'              => 'no-cache',
            'Expires'             => 0
        ];
    }

    /**
     * Get the download file name.
     *
     * @param string|null $name optional download name
     * 
     * @return string file name
     */
    protected function buildDownloadFileName(?string $name): string
    {
        if ($name) {
            // They are overriding the default naming, append the csv file type if not included
            return contains($name, '.csv') ? $name : $name . '.csv';
        }

        return $this->getDefaultDownloadFileName();
    }

    /**
     * Get the default CSV file download name.
     *
     * @return string file name
     */
    protected function getDefaultDownloadFileName(): string
    {
        // Grab the URI and chop off the first slash
        $uri = substr(explode('?', request()->getPathInfo())[0], 1);

        // Replace all slashes with underscores
        $uri = str_replace('/', '_', $uri);

        return $uri . '_' . DateTime::now()->timestamp . '.csv';
    }
}
