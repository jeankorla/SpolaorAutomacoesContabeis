<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Gerador extends BaseController
{
    public function index($nome_temporario)
{
    $data['nome_temporario'] = $nome_temporario;
    echo view('common/header');
    echo view('download_page', $data);
    echo view('common/footer');
}

public function downloadFile($nome_temporario)
{
    $file = WRITEPATH . 'uploads/' . $nome_temporario;

    if (!is_file($file)) {
        die('O arquivo não existe');
    }

    return $this->response->download($file, null);
}


}