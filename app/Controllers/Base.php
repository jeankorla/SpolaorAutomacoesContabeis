<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;

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

    public function fiscal()
    {
        echo view('common/header');
        echo view('pdf_upload');
        echo view('common/footer');
    }
public function convertPdfToText()
{
    $file = $this->request->getFile('pdf_file');
    if ($file->isValid() && !$file->hasMoved()) {
        $file->move('./public/uploads', $file->getName());
        $newName = './public/uploads/' . $file->getName();

        if (!is_readable($newName)) {
            return view('pdf_error', ['error' => 'File not found or not readable: ' . $newName]);
        }

        // Vamos definir o nome do arquivo de texto de saída
        $textFileName = './public/uploads/' . pathinfo($file->getName(), PATHINFO_FILENAME) . '.txt';

        $command = 'pdftotext ' . escapeshellarg($newName) . ' ' . escapeshellarg($textFileName) . ' 2>&1';
        $output = shell_exec($command);

        if (strpos($output, 'Error') !== false) {
            // Se houver um erro na saída, mostra a mensagem de erro.
            return view('pdf_error', ['error' => $output]);
        } else {
            if (file_exists($textFileName)) {
                // O arquivo de texto foi criado, agora vamos ler o conteúdo.
                $extractedText = file_get_contents($textFileName);
                // Se não houver um erro, mostra o texto extraído.
                return view('pdf_result', ['result' => $extractedText]);
            } else {
                return view('pdf_error', ['error' => 'Failed to extract text.']);
            }
        }
    } else {
        return view('pdf_error', ['error' => $file->getErrorString(). ' ' .$file->getError()]);
    }
}

}