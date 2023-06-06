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
        if ($pdfFile != null && $pdfFile->isValid() && !$pdfFile->hasMoved()) {
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

                //dd($text);
                // Chama a função extractFields para obter os campos extraídos do texto
                $fields = $this->extractFields($text);

                // Formata os campos para exibição
                $formattedFields = [];
                foreach ($fields as $field) {
                    $formattedFields[] = is_numeric($field) ? $field : '"' . $field . '"';
                }

                // Exibe o resultado na view ou passa para a próxima etapa do seu programa
                return view('pdf_result', ['fields' => implode(',', $formattedFields)]);
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

function extractFields($text) {
    $fields = [];

    // Extrai o CNPJ
    preg_match("/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/", $text, $matches);
    $cnpj = isset($matches[0]) ? $matches[0] : '';

    // Extrai o número da nota fiscal
    preg_match("/SECRETARIA MUNICIPAL DE FINANÇAS\s*(\d+)/s", $text, $matches);
    $numeroNota = isset($matches[1]) ? $matches[1] : '';

    // Extrai data de emissão
    preg_match("/SECRETARIA MUNICIPAL DE FINANÇAS\D*\d*.*(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/s", $text, $matches);
    $dataHoraEmissao = isset($matches[1]) ? \DateTime::createFromFormat('d/m/Y H:i', $matches[1])->format('Ymd') : '';

    // O valor da nota fiscal
    preg_match("/([\d\.,]+)\s*([\d\.,]+)\s*Avisos/s", $text, $matches);
    $valorNota = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

    // O valor de Base de Calculo
    preg_match("/([\d\.,]+)\s*Avisos/", $text, $matches);
    $valorBase = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

     // O valor de Aliquota
    preg_match("/([\d\.,]+)\s*([\d\.,]+)\s*[\d\.,]+\s*Avisos/s", $text, $matches);
    $valorAliquota = isset($matches[2]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

    //Valor ISS
    preg_match("/([\d\.,]+)\s*\(\X\) Sim \(\) Não/", $text, $matches);
    $valorIss = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;


    // Campo 1 - Número da chave da importação, neste caso estamos apenas usando um número aleatório
    $fields[] = rand(1, 999999);

    // Campo 2 - Código, CNPJ, CPF ou apelido do cliente
    $fields[] = $cnpj;

    // Campo 3 - Código da cidade. Neste exemplo, deixamos em branco
    $fields[] = '5321';

    // Campo 4 - Estado do destinatário da nota. Neste exemplo, deixamos em branco
    $fields[] = 'SP';

    // Campo 5 - Data da emissão da nota
    $fields[] =  $dataHoraEmissao;

    // Campo 6 - Número inicial das notas
    $fields[] = $numeroNota;

    // Campo 7 - Número final das notas
    $fields[] = $numeroNota;

    // Campo 8 - Espécie do documento, neste exemplo estamos usando um valor fixo
    $fields[] = 'NFS';

    // Campo 9 - Série do documento, neste exemplo estamos deixando em branco
    $fields[] = '';

    // Campo 10 - Valor contábil da nota
    $fields[] = number_format($valorNota, 2, '.', '');

    // Campo 11 - Valor do Calculo ISS
    $fields[] = number_format($valorBase, 2, '.', '');

    // Campo 12 - Valor Aliquota
    $fields[] = number_format($valorAliquota, 2, '.', '');

    // Campo 13 - Valor ISS calculado
    $fields[] = number_format($valorIss, 2, '.', '');

    // Campo 14 - Observação, neste exemplo estamos deixando em branco
    $fields[] = '';

    return $fields;
}

}
