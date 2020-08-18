<?php

declare(strict_types = 1);

namespace Mini\Http;

use ArrayObject;
use JsonSerializable;
use Mini\Util\Json;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

/**
 * Http response wrapper.
 */
class Response extends BaseResponse
{
	/**
	 * Original content pre-modification.
	 * 
	 * @var mixed
	 */
	protected $original = null;

    /**
     * Setup.
     * 
     * @param string|null $content optional response content
     * @param int         $status  response status code
     * @param array       $headers response headers
     */
    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        // Default to html if no content-type specified
        if (!in_array('Content-Type', $headers)) {
            $headers['Content-Type'] = 'text/html';
        }

        parent::__construct($content, $status, $headers);
    }

    /**
     * Set the content on the response.
     *
     * @param mixed $content content
     * 
     * @return self $this self for chaining
     */
    public function setContent($content): self
    {
        $this->original = $content;

        if ($this->shouldBeJson($content)) {
            $this->header('Content-Type', 'application/json');

            $content = Json::encode($content);
        }

        parent::setContent($content);

        return $this;
    }

    /**
     * Attach an exception to the response.
     *
     * @param Throwable $e exception
     * 
     * @return self $this self for chaining
     */
    public function setException(Throwable $e): self
    {
        $this->exception = $e;

        return $this;
    }

    /**
     * Determine if the content should become JSON.
     *
     * @param mixed $content content
     * 
     * @return bool whether it should be json
     */
    protected function shouldBeJson($content): bool
    {
        return $content instanceof ArrayObject ||
               $content instanceof JsonSerializable ||
               is_array($content);
    }
}
