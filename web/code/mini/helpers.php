<?php

declare(strict_types = 1);

use Mini\Container;
use Mini\Http\Exception\HttpException;
use Mini\Http\Request;
use Mini\Http\ResponseFactory;
use Mini\View\Renderer;

/**
 * Common functions.
 * 
 * @package Functions
 */

if (!function_exists('container')) {
    /**
     * Get the IoC container. Can optionally include a
     * class name as the parameter to instead get that class
     * through the container.
     *
     * @param string|null $class optional class name
     *
     * @return mixed the ioc container or a class if optional param used
     */
    function container(?string $class = null)
    {
        $container = Container::getInstance();

        return $class ? $container->get($class) : $container;
    }
}

if (!function_exists('request')) {
    /**
     * Get the HTTP request from the IoC container.
     *
     * @return Request http request
     */
    function request(): Request
    {
        return Container::getInstance()->getParameter('request');
    }
}

if (!function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param View|string|array|null $content optional content to respond with
     * @param int                    $status  status code
     * @param array                  $headers headers
     * 
     * @return mixed http response
     */
    function response($content = '', int $status = 200, array $headers = [])
    {
        $factory = Container::getInstance()->get(ResponseFactory::class);

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($content, $status, $headers);
    }
}

if (!function_exists('view')) {
    /**
     * Render a given view.
     *
     * @param string $view view to create
     * @param array  $data optional view data
     * 
     * @return string view
     */
    function view(string $view, array $data = []): string
    {
        $renderer = Container::getInstance()->get(Renderer::class);

        return $renderer->render($view, $data);
    }
}

if (!function_exists('now')) {
    /**
     * Get the current datetime in utc.
     * 
     * @param bool   $timestamp flag to return the date in a timestamp format
     * @param string $format    datetime format
     * 
     * @return string|int datetime string or timestamp int
     */
    function now(bool $timestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dt = Mini\Util\DateTime::now();

        return $timestamp ? $dt->getTimestamp() : $dt->format($format);
    }
}

if (!function_exists('array_exists')) {
    /**
     * If the array key exists, return its value,
     * else return a default value.
     * 
     * @param mixed $key     key of array
     * @param array $array   array to be searched
     * @param mixed $default value if key not found
     * 
     * @return mixed array value
     */
    function array_exists($key, array $array, $default = null)
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }
}

if (!function_exists('contains')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param string       $haystack string
     * @param string|array $needles  substring(s)
     * 
     * @return bool whether the string contains the substring
     */
    function contains(string $haystack, $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('generate_random_id')) {
    /**
     * Generate a unique random ID. The character limit strips down the length of the ID.
     * 
     * @param int $characterLimit length of ID to use (negative for substr snip)
     * 
     * @return string unique random ID
     */
    function generate_random_id(int $characterLimit = -16): string
    {
        mt_srand((int) (microtime(true) * 100000 + memory_get_usage(true)));
        
        $id = md5(uniqid((string) mt_rand(), true));

        return substr($id, $characterLimit);
    }
}

if (!function_exists('to_bool')) {
    /**
     * Convert value to correct boolean - 'false' should not be evaluated true!
     * 
     * Note: the string conversion will only be true for certain phrases/characters/number.
     * 
     * @param mixed $value value to check
     * 
     * @return bool flag to determine if this value is boolean
     */
    function to_bool($value): bool
    {
        if (!is_string($value)) {
            return (bool) $value;
        }

        switch (strtolower($value)) {
            case '1':
            case 'true':
            case 'on':
            case 'yes':
            case 'y':
                return true;

            default:
                return false;
        }
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value value
     * 
     * @return mixed value
     */
    function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}

if (!function_exists('get_views_path')) {
    /**
     * Get the "views" base path.
     *
     * @return string path
     */
    function get_views_path(): string
    {
        return dirname(__DIR__) . '/views';
    }
}

if (!function_exists('view_exists')) {
    /**
     * Check if a given view exists.
     *
     * @param string $view view to create
     * 
     * @return bool whether the view exists
     */
    function view_exists(string $view): bool
    {
        return file_exists(get_views_path() . '/' . $view . '.twig');
    }
}

if (!function_exists('is_debug')) {
    /**
     * Check if the application is in debug mode.
     *
     * Note: This function is used because how we pass in the environment
     * variable through docker-compose, we end up getting the true/false value
     * as a string which can mess up branch logic where "false" evaluates to true.
     * 
     * @return bool whether the application is in debug mode
     */
    function is_debug(): bool
    {
        return to_bool(getenv('APP_DEBUG'));
    }
}

if (!function_exists('is_dev')) {
    /**
     * Check if the application environment is set to development.
     *
     * @return bool whether the application environment is set to development
     */
    function is_dev(): bool
    {
        return strtolower(getenv('APP_ENVIRONMENT')) === 'dev';
    }
}

if (!function_exists('is_prod')) {
    /**
     * Check if the application environment is set to production.
     *
     * @return bool whether the application environment is set to production
     */
    function is_prod(): bool
    {
        return strtolower(getenv('APP_ENVIRONMENT')) === 'prod';
    }
}

if (!function_exists('is_testing')) {
    /**
     * Check if the application is running its tests.
     *
     * @return bool whether the application is running its tests
     */
    function is_testing(): bool
    {
        return to_bool(getenv('IS_TESTING'));
    }
}
