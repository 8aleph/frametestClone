<?php

declare(strict_types = 1);

namespace Example\Controller;

use Mini\Controller\Controller;
use Mini\Http\Request;

/**
 * Home entrypoint logic.
 */
class HomeController extends Controller
{
    /**
     * Show the default page.
     * 
     * @param Request $request http request
     * 
     * @return string view template
     */
    public function index(Request $request): string
    {
        return view('app/home/home', ['version' => getenv('APP_VERSION')]);
    }
}
