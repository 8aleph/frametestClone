<?php

declare(strict_types = 1);

namespace Mini\View;

use Twig\Environment;

/**
 * Templating engine to build HTML.
 */
class TwigRenderer implements Renderer
{
    /**
     * Twig HTML view builder.
     * 
     * @var Twig_Environment|null
     */
    protected $renderer = null;

    /**
     * Setup.
     * 
     * @param Environment $renderer html builder
     */
    public function __construct(Environment $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Parse a twig template to HTML.
     * 
     * @param string $template html template file
     * @param array  $data     optional data for the template to sue
     * 
     * @return string view template
     */
    public function render(string $template, array $data = []): string
    {
        return $this->renderer->render("$template.twig", $data);
    }
}
