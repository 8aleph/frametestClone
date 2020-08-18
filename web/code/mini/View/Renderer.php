<?php

declare(strict_types = 1);

namespace Mini\View;

/**
 * HTML template builder.
 */
interface Renderer
{
    /**
     * Build a html section with data.
     * 
     * @param string $template path to the view template
     * @param array  $data     optional data to be used in the template
     * 
     * @return string view template
     */
    public function render(string $template, array $data = []): string;
}
