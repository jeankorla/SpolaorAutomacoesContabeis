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

                dd($text);
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

    $zero = 0.00;

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

    //Valor IR
    preg_match("/IR\(R\$\)\s*([\d\.,]+)/", $text, $matches);
    $valorIR = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;



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

    // Campo 15 - Valor contábil da nota (Valor base de cálculo do imposto de renda retido na fonte)
    $fields[] = number_format($valorNota, 2, '.', '');

    // Campo 16 - Alíquota do I.R.R.F 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 17 - Valor IR ----------------------------------------------------------------------------------
    $fields[] = number_format($valorIR, 2, '.', '');

    // Campo 18 - Condição pagamento da nota ----------------------------------------------------------------
    $fields[] = '';

    // Campo 19 - Conta Débito Exportação P/ contabilida
    $fields[] = '';

    // Campo 20 - Conta Crédito Exportação P/ contabilida
    $fields[] = '';

    // Campo 21 - Redução da base de cálculo de cálculo ----------------------------------------------------
    $fields[] = '';

    // Campo 22 - Valor de isentas do ISS
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 23 - Valor de outras do ISS
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 24 - Nome do primeiro centro de custo
    $fields[] = '';

    // Campo 25 -Percentual do primeiro Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 26 - Nome do segundo centro de custo
    $fields[] = '';

    // Campo 27 -Percentual do segundo Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 28 - Nome do terceiro centro de custo
    $fields[] = '';

    // Campo 29 -Percentual do terceiro Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 30 - Nome do quarto centro de custo
    $fields[] = '';

    // Campo 31 -Percentual do quarto Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 32 - Nome do quinto centro de custo
    $fields[] = '';

    // Campo 33 -Percentual do quinto Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 34 - Nome do sexto centro de custo
    $fields[] = '';

    // Campo 35 -Percentual do sexto Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 36 - Nome do setimo centro de custo
    $fields[] = '';

    // Campo 37 -Percentual do setimo Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 38 - Nome do oitavo centro de custo
    $fields[] = '';

    // Campo 39 -Percentual do oitavo Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 40 - Nome do nono centro de custo
    $fields[] = '';

    // Campo 41 -Percentual do nono Custo
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 42 -Valor da retenção de INSS ----------------------------------------------------------------------
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 43 - Tipo de Prestação de Serviço -----------------------------------------------------------------
    $fields[] = '';

    // Campo 44 -Tipo de recolhimento do ISSQN para cidades -----------------------------------------------------
    $fields[] = '';

    // Campo 45 -Código do Fornecedor, quando o estado da empresa for igual a MS e o tipo do Lançamento for R (Recebidos)
    $fields[] = '';

    // Campo 46 -Estado do Fornecedor, quando o estado da empresa for igual a MS e o tipo do Lançamento for R (Recebidos)
    $fields[] = '';

    
















    

    return $fields;
}

}
