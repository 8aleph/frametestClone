<?php

declare(strict_types = 1);

namespace Mini\Util;

/**
 * Wrapper for decoding/encoding JSON.
 */
class Json
{
    /**
     * Decode a JSON string into an array of content.
     * 
     * @param string $json  json to decode
     * @param bool   $throw optional flag to throw an exception on error
     * 
     * @return mixed $content json decode of content
     */
    public static function decode(string $json, bool $throw = true)
    {
        if ($throw) {
            $options = JSON_THROW_ON_ERROR;
        }

        return json_decode($json, true, 512, $options);
    }

    /**
     * Encode content into a JSON string.
     * 
     * @param mixed $content content to encode
     * @param int   $options optional encoding options (bitmasks)
     * @param bool  $throw   optional flag to throw an exception on error
     * 
     * @return string $json json encoded string
     */
    public static function encode(
        $content,
        int $options = 0,
        bool $throw = true
    ): string {
        $options |= JSON_UNESCAPED_SLASHES;

        if ($throw) {
            $options |= JSON_THROW_ON_ERROR;
        }

        return json_encode($content, $options, 512);
    }
}
