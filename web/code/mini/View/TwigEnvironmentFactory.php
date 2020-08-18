<?php

declare(strict_types = 1);

namespace Mini\View;

use Mini\Http\Request;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Twig environment setup.
 */
class TwigEnvironmentFactory
{
	/**
	 * Setup the twig environment.
	 * 
	 * @return Environment $twig environment
	 */
    public function __invoke(): Environment
    {
        $twig = new Environment(
            new FilesystemLoader(get_views_path()),
            [
                'cache' => dirname(dirname(__DIR__)) . '/cache/views',
                'debug' => is_debug()
            ]
        );

        if (is_debug()) {
            $twig->addExtension(new DebugExtension);
        }

        return $twig;
    }
}
