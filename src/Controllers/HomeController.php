<?php
declare(strict_types=1);

namespace GamesPool\Controllers;

class HomeController
{
    public function index(): string
    {
        return view('home', []);
    }
}
