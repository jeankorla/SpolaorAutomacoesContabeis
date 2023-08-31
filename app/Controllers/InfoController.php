<?php

namespace App\Controllers;

class InfoController extends BaseController
{
    public function index(): string
    {
        return view('info');
    }
}
