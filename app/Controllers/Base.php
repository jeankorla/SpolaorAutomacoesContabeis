<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use Smalot\PdfParser\Parser;
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
  public function convertPdfToText() {
    // Verifica se o arquivo foi enviado corretamente
    if ($this->request->getMethod() === 'post') {
        $pdfFile = $this->request->getFile('pdf_file');

        if ($pdfFile != null && $pdfFile->isValid() && ! $pdfFile->hasMoved()) {
            // Move o arquivo para um diretório temporário
            $newName = $pdfFile->getRandomName();
            $pdfFile->move('./uploads', $newName); // move o arquivo para a pasta /uploads
            $pdfPath = './uploads/' . $newName;

            // Instanciação do parser
            $parser = new Parser();

            try {
                // Carrega o arquivo PDF
                $pdf = $parser->parseFile($pdfPath);

                // Extrai o texto do PDF
                $text = $pdf->getText();

                // Exibe o texto extraído
                return view('pdf_result', ['text' => $text]);
            } catch (\Exception $e) {
                // Lidar com erros, se houver
                return view('pdf_error', ['error' => $e->getMessage()]);
            }
        } else {
            return redirect()->back()->with('error', 'O arquivo PDF é inválido.');
        }
    }

    return redirect()->back()->with('error', 'Nenhum arquivo PDF enviado.');
}
   
}
