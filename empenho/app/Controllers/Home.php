<?php

namespace App\Controllers;
use App\Controllers\BaseController;
use App\Models\UsuarioModel;

class Home extends BaseController
{
    public function index()
    {   
        echo view('common/header');
        echo view('Login');
        echo view('common/footer');

        //teste
    }
    public function login()
    {
        $username = $this->request->getPost('NAME');
        $password = $this->request->getPost('PASSWORD');

        $model = new UsuarioModel();

        $user = $model->getUser($username, $password);

        if ($user) {
            // Define a variável de sessão 'isLoggedIn' como true
            session()->set('isLoggedIn', true);
            // Limpa todas as outras variáveis de sessão
            session()->remove(['otherSessionVariable1', 'otherSessionVariable2']);
            // Redireciona para a tela "index"
            return redirect()->to('base/index');
        } else {
            // Caso o login falhe, redireciona de volta para a tela de login
            return redirect()->back()->with('error', 'Credenciais inválidas.')->withInput();
        }
}
}
