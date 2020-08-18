<?php

declare(strict_types = 1);

namespace Mini\Http;

use InvalidArgumentException;
use JsonSerializable;
use Mini\Util\Json;
use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

/**
 * HTTP JSON response.
 */
class JsonResponse extends BaseJsonResponse
{
    /**
     * Original content pre-modification.
     * 
     * @var array
     */
    protected $original = null;

    /**
     * Flag to allow converting the JSON response into a CSV downloadable
     * response if the user includes an optional "csv" query parameter.
     * 
     * @var bool
     */
    protected $allowCsv = false;

    /**
     * Setup.
     *
     * @param mixed $data    optional content to send
     * @param int   $status  status code
     * @param array $headers headers
     * @param int   $options encoding options
     */
    public function __construct($data = null, int $status = 200, array $headers = [], int $options = 0)
    {
        $this->encodingOptions = $options;

        parent::__construct($data, $status, $headers);
    }

    /**
     * Get the JSON decoded data from the response.
     *
     * @return array response data
     */
    public function getData(): array
    {
        return Json::decode($this->data);
    }

    /**
     * Sets the data to be sent as JSON.
     *
     * @param mixed $data optional data to encode
     *
     * @return self $this self for chaining
     */
    public function setData($data = []): self
    {
        $this->original = $data;

        if ($data instanceof JsonSerializable) {
            $this->data = Json::encode($data->jsonSerialize(), $this->encodingOptions);
        } else {
            $this->data = Json::encode($data, $this->encodingOptions);
        }

        return $this->update();
    }

    /**
     * Sets options used while encoding data to JSON.
     *
     * @param mixed $options encoding options
     * 
     * @return self $this self for chaining
     */
    public function setEncodingOptions($options): self
    {
        $this->encodingOptions = (int) $options;

        return $this->setData($this->getData());
    }

    /**
     * Allow this JSON response to be auto-converted into a CSV downloadable
     * response if the user includes an optional query parameter.
     *
     * @return self $this self for chaining
     */
    public function allowCsv(): self
    {
        $this->allowCsv = true;

        return $this;
    }

    /**
     * Check if the JSON response allows CSV.
     *
     * @return bool whether the response allows csv
     */
    public function allowsCsv(): bool
    {
        return $this->allowCsv;
    }

    /**
     * Get the orignial response payload pre-encode.
     *
     * @return array original response data
     */
    public function getOriginal(): array
    {
        return $this->original;
    }
}
