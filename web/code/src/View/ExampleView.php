<?php

declare(strict_types = 1);

namespace Example\View;

use Example\Model\ExampleModel;
use Mini\Controller\Exception\BadInputException;

/**
 * Example view builder.
 */
class ExampleView
{
    /**
     * Example data.
     * 
     * @var Example\Model\ExampleModel|null
     */
    protected $model = null;

    /**
     * Setup.
     * 
     * @param ExampleModel $model example data
     */
    public function __construct(ExampleModel $model)
    {
        $this->model = $model;
    }

    /**
     * Get the example view to display its data.
     * 
     * @param int $id example id
     * 
     * @return string view template
     *
     * @throws BadInputException if no example data is returned
     */
    public function get(int $id): string
    {
        $data = $this->model->get($id);

        if (!$data) {
            throw new BadInputException('Unknown example ID');
        }

        return view('app/example/detail', $data);
    }
}
