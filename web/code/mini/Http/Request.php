<?php

declare(strict_types = 1);

namespace Mini\Http;

use Mini\Util\Json;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as BaseRequest;

/**
 * Http request wrapper.
 */
class Request extends BaseRequest
{
    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @return Request $request request
     */
    public static function createFromGlobals(): Request
    {
        $request = parent::createFromGlobals();

        if (!$request->request->count()
            && 0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/json')
            && $request->getContent()
        ) {
            // POST data is in the input stream, decode the JSON and stuff into our "post"
            $request->request = new ParameterBag(Json::decode($request->getContent()));
        }

        parent::setTrustedProxies(
            // Trust *all* requests (dynamic LB)
            ['127.0.0.1', 'REMOTE_ADDR'],
            // Trust *all* "X-Forwarded-*" headers
            parent::HEADER_X_FORWARDED_ALL
        );

        return $request;
    }

    /**
     * Check if the request expects a JSON response.
     *
     * @return bool whether it expects json
     */
    public function expectsJson(): bool
    {
        return ($this->isXmlHttpRequest() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    /**
     * Check if the request is asking for JSON.
     *
     * @return bool wheter it wants json
     */
    public function wantsJson(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return isset($acceptable[0]) && contains($acceptable[0], ['/json', '+json']);
    }

    /**
     * Check if the request accepts any content type.
     *
     * @return bool whether it accepts any content type
     */
    public function acceptsAnyContentType(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return count($acceptable) === 0 || (
            isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*')
        );
    }
}
