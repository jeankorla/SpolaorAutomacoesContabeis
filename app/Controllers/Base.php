<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;

class Base extends BaseController
{
public function index()
{
    // Verifica se o usuário está autenticado
    if (!session()->get('isLoggedIn')) {
        // Caso não esteja autenticado, redireciona para a tela de login
        return redirect()->to('home/login');
    }
    
    echo view('common/header');
    echo view('index');
    echo view('common/footer');
}
}
