<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Gerador extends BaseController
{
    public function index($nome_temporario)
    {
        
        $file = WRITEPATH . 'uploads/' . $nome_temporario;

        if (!is_file($file)) {
            die('O arquivo não existe');
        }
        
        return $this->response->download($file, null);

    }

}