<?php

declare(strict_types = 1);

namespace Mini\Util;

use Mini\Log\Logger;
use Throwable;

/**
 * Wrapper for sending curl requests.
 */
class Curl
{
    /**
     * Holds an instance of the cURL resource.
     * 
     * @var Object|null
     */
    protected $handle = null;

    /**
     * Request headers.
     * 
     * @var array
     */
    protected $requestHeaders = [];

    /**
     * The headers of the most recent response.
     * 
     * @var array
     */
    protected $responseHeaders = [];

    /**
     * Status code of the most recent response.
     * 
     * @var int|null
     */
    protected $statusCode = null;

    /**
     * Last error for current session.
     * 
     * @var string|null
     */
    protected $error = null;

    /**
     * Initialize the curl request with where we are sending
     * and some basic hardcoded config settings.
     * 
     * @param string $url request location
     * 
     * @return self $this self for chaining
     */
    public function init(string $url)
    {
        $this->handle = curl_init($url);

        $this->error            = null;
        $this->responseHeaders  = [];
        $this->statusCode       = null;
        $this->requestHeaders[] = 'Accept: application/json';

        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->handle, CURLOPT_HEADER, 1);

        $this->setTimeout();

        return $this;
    }

    /**
     * Get the status code of the most recent response.
     * 
     * @return int|null response status code if sent
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the headers of the most recent response.
     * 
     * @return array headers of the most recent response.
     */
    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    /**
     * Get the last error for this session.
     * 
     * @return string|null error
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Set the authentication config setting.
     * 
     * @param string $username authentication username
     * @param string $password authentication password
     * 
     * @return self $this self for chaining
     */
    public function setAuth(string $username, string $password): self
    {
        curl_setopt($this->handle, CURLOPT_USERPWD, "$username:$password");
    
        return $this;
    }

    /**
     * Set the headers.
     * 
     * @param array $headers request headers
     * 
     * @return self $this self for chaining
     */
    public function setHeaders(array $headers): self
    {
        $this->requestHeaders = array_merge($this->requestHeaders, $headers);
        
        return $this;
    }

    /**
     * Set the POST data config setting.
     * 
     * @param mixed $data optional data to send
     * 
     * @return self $this self for chaining
     */
    public function setPost($data = null): self
    {
        if ($data && is_array($data)) {
            $data = Json::encode($data);
        }

        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'POST');

        if ($data) {
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, $data);

            $this->requestHeaders = array_merge($this->requestHeaders, ['Content-Type: application/json']);
        }

        return $this;
    }

    /**
     * Set a custom request type.
     * 
     * @param string $requestType type of request
     *
     * @return self $this self for chaining
     */
    public function setRequestType(string $requestType): self
    {
        curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, $requestType);

        return $this;
    }

    /**
     * Set the SSL certificate for the request.
     * 
     * @param string $cert ssl certificate location
     * 
     * @return self $this self for chaining
     */
    public function setSslCert(string $cert): self
    {
        curl_setopt($this->handle, CURLOPT_CAINFO, $cert);

        return $this;
    }

    /**
     * Set the length for attempting the request.
     * 
     * @param int $timeout max time to wait for request
     * 
     * @return self $this self for chaining
     */
    public function setTimeout(int $timeout = 30): self
    {
        curl_setopt($this->handle, CURLOPT_TIMEOUT, $timeout);

        return $this;
    }

    /**
     * Send the request.
     * 
     * @param bool $decode optional flag to determine if we should decode the response
     * 
     * @return mixed response from contactee
     */
    public function send(bool $decode = true)
    {
        $response = null;

        try {
            curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->requestHeaders);

            $response = $this->processRequest(curl_exec($this->handle), $decode);
        } catch (Throwable $e) {
            $this->processRequestException($e, $rawResponse);
        } finally {
            curl_close($this->handle);
            $this->handle = null;
        }

        return $response;
    }

    /**
     * Process the cURL request.
     *
     * @param mixed $response response of the server
     * @param bool  $decode   optional flag to determine if we should decode the response
     * 
     * @return mixed response
     */
    protected function processRequest($response, bool $decode)
    {
        $this->statusCode = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $this->error = curl_error($this->handle);
            return null;
        }

        $headerSize = curl_getinfo($this->handle, CURLINFO_HEADER_SIZE);
        $headers    = substr($response, 0, $headerSize);
        $body       = substr($response, $headerSize);

        // Convert to array
        $this->responseHeaders = array_filter(explode("\r\n", $headers));

        return $decode ? Json::decode($body) : $body;
    }

    /**
     * Process the cURL request exception.
     * 
     * @param Throwable $e exception
     * 
     * @return void
     */
    protected function processRequestException(Throwable $e): void
    {
        $error = 'Curl Request - ' . $e->getFile() . ' - ' . $e->getLine() . ' - ' . $e->getMessage();

        if ($this->error = curl_error($this->handle)) {
            $error .= ' :: Curl Error = ' . $this->error;
        }

        (new Logger)->error($error);
    }
}