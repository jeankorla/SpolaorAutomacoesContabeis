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
        return redirect()->to('http://192.168.6.100:8082/index.php/base/fiscal');
    }
     public function manutencao()
    {
        echo view('common/header');
        echo view('manutencao');
        echo view('common/footer');
    }
    public function back()
    {
        $codigoJavaScript = "
            <script>
                function voltar() {
                   window.history.go(-1);
                }
            </script>
        ";

        return $codigoJavaScript;
    }

public function convertPdfToText()
{
    // Retrieve all uploaded files
    $files = $this->request->getFiles('pdf_file');
    $fieldsArr = [];

    // Process each file one by one
    foreach ($files['pdf_file'] as $file) {
        if ($file->isValid() && !$file->hasMoved()) {
            $file->move('./writable/uploads/pdf/', $file->getName());
            $newName = './writable/uploads/pdf/' . $file->getName();

            if (!is_readable($newName)) {
                return view('pdf_error', ['error' => 'File not found or not readable: ' . $newName]);
            }

            // Convert the PDF to text and extract the fields
            $textFileName = './writable/uploads/pdf/' . pathinfo($file->getName(), PATHINFO_FILENAME) . '.txt';
            $command = 'pdftotext ' . escapeshellarg($newName) . ' ' . escapeshellarg($textFileName) . ' 2>&1';
            shell_exec($command);
          
            // dd($textFileName);

            if (file_exists($textFileName)) {
                $extractedText = file_get_contents($textFileName);
                $fields = $this->extractFields($extractedText);
                $fieldsArr[] = $fields;
                dd($extractedText);
            } else {
                return view('pdf_error', ['error' => 'Failed to extract text.']);
            }
        } else {
            return view('pdf_error', ['error' => $file->getErrorString(). ' ' .$file->getError()]);
        }
    }

    // After all files have been processed, create the text file for download
$downloadFileName = 'processed_fields.txt';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . basename($downloadFileName));
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
echo implode("\n", $fieldsArr);  // join all the fields with newline character

// Delete all the PDF and TXT files from the server
foreach ($files['pdf_file'] as $file) {
    $pdfFilePath = './writable/uploads/pdf/' . $file->getName();
    $txtFilePath = './writable/uploads/pdf/' . pathinfo($file->getName(), PATHINFO_FILENAME) . '.txt';

    if (file_exists($pdfFilePath)) {
        unlink($pdfFilePath); // delete PDF file
    }

    if (file_exists($txtFilePath)) {
        unlink($txtFilePath); // delete TXT file
    }
}

exit;

}

function extractFields($text) {
    $fields = [];

    $zero = 0.00;

    // Extrai o CNPJ
    preg_match("/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/", $text, $matches);
    $cnpj = isset($matches[0]) ? $matches[0] : '';

    // Extrai o número da nota fiscal
    $split_text = preg_split("/Número da\s*NFS-e/", $text);
    if (isset($split_text[1])) {
        preg_match("/\d+/", trim($split_text[1]), $matches);
        $numeroNota = isset($matches[0]) ? $matches[0] : '';
    } else {
        $numeroNota = '';
    }

    // Extrai Valor de Serviço
    preg_match("/Base de Cálculo\s*[\n\s]*([\d.,]+)/", $text, $matches);
    $valorServicos = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

   // Valor de IR
    $lines = preg_split("/(\r?\n)+/", $text);
    $irIndex = array_search('IR(R$)', $lines);
    $valorIR = 0.0;  // Inicializa como 0.0

    if ($irIndex !== false && isset($lines[$irIndex + 1])) {
        preg_match("/[\d\.,]+/", trim($lines[$irIndex + 1]), $matches);
        
        // Apenas atualiza o valor do IR se o valor encontrado for um número e contiver uma vírgula
        if (!empty($matches) && strpos($matches[0], ',') !== false && is_numeric(str_replace(',', '.', str_replace('.', '', $matches[0])))) {
            $valorIR = (float)str_replace(',', '.', str_replace('.', '', $matches[0]));
        }
    }

    // Aliquota de IR
    $aliquotaIR = ($valorIR != 0 && $valorServicos != 0) ? ($valorIR / $valorServicos) * 100 : 0;

    // Extrai data de emissão
    preg_match("/SECRETARIA MUNICIPAL DE FINANÇAS\D*\d*.*(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2})/s", $text, $matches);
    $dataHoraEmissao = isset($matches[1]) ? \DateTime::createFromFormat('d/m/Y H:i', $matches[1])->format('Ymd') : '';


    // O valor de Base de Calculo
    preg_match("/([\d\.,]+)\s*Avisos/", $text, $matches);
    $valorBase = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

     // O valor de Aliquota
    preg_match("/([\d\.,]+)\s*([\d\.,]+)\s*[\d\.,]+\s*Avisos/s", $text, $matches);
    $valorAliquota = isset($matches[2]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

   // Valor de INSS
    $pattern = "/2 - Não\s*\r?\n\r?\n([\d\.,]+)/";
    preg_match($pattern, $text, $matches);
    $valorINSS = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

    //Serviço
    $pattern = "/Código do Serviço \/ Atividade\r?\n([\d\.]+) \/ [\d\-]+/";
    preg_match($pattern, $text, $matches);
    $codigoServico = isset($matches[1]) ? $matches[1] : '';

    //PIS
    $pattern = "/PIS\s+([\d\.,]+)/";
    preg_match($pattern, $text, $matches);
    $valorPIS = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

    //dd($valorPIS);

    // Valor de CSLL
    preg_match("/([\d.,]+)[\s\n]*Cálculo do ISSQN devido no Município/", $text, $matches);
    if (isset($matches[1])) {
        $valorCSLL = (float)str_replace(',', '.', str_replace('.', '', $matches[1]));
    } else {
        $valorCSLL = 0.0;
    }

    // Valor Cofins
    preg_match("/([\d.,]+)[\s\n]*2 -/", $text, $matches);
    if (isset($matches[1])) {
        $valorCofins = (float)str_replace(',', '.', $matches[1]);
    } else {
        $valorCofins = 0.00;
    }

    // Valor de ISS Retido
    preg_match("/ISS Retido[\s\n]*([\d.,]+)/", $text, $matches);
    if (isset($matches[1])) {
        $valorISSRetido = (float)str_replace(',', '.', $matches[1]);
    } else {
        $valorISSRetido = 0.00;
    }

    // CNPJ de quem esta recebendo o serviço
    preg_match("/([\d]{2}\.[\d]{3}\.[\d]{3}\/[\d]{4}-[\d]{2})\s*Endereço e CEP/", $text, $matches);
    $empresaCnpj = isset($matches[1]) ? $matches[1] : '';


    $simplesNacional = [
        "09.163.898/0001-93",
        "46.196.090/0001-39",
        "31.795.189/0001-80",
        "03.561.721/0001-69",
        "39.157.072/0001-82",
        "46.605.227/0001-61",
        "04.859.829/0001-03",
        "07.134.258/0001-20",
        "05.345.542/0001-10",
        "46.408.042/0001-67",
        "48.993.135/0001-21",
        "05.783.689/0001-91",
        "31.097.542/0001-58",
        "26.917.651/0001-34",
        "14.686.279/0001-13",
        "20.541.460/0001-34",
        "05.673.502/0001-05",
        "03.555.442/0001-92",
        "41.265.301/0001-24",
        "42.109.466/0001-70",
        "03.457.756/0001-52",
        "13.686.206/0001-69",
        "24.361.444/0001-10",
        "34.253.710/0001-45",
        "17.966.720/0001-09",
        "17.966.720/0002-90",
        "08.664.277/0001-20",
        "07.280.024/0001-90",
        "32.745.544/0001-79",
        "11.288.132/0001-87",
        "53.739.348/0001-61",
        "05.298.570/0001-23",
        "49.893.790/0001-70",
        "66.080.482/0001-45",
        "43.639.401/0001-07",
        "13.327.173/0001-60",
        "21.639.411/0001-00",
        "34.308.415/0001-49",
        "03.273.460/0001-81",
        "11.309.725/0001-82",
        "96.655.683/0001-94",
        "04.228.577/0001-06",
        "22.630.392/0001-05",
        "49.765.157/0001-05",
        "26.849.416/0001-72",
        "29.117.899/0001-09",
        "11.284.033/0001-27",
        "57.957.177/0001-06",
        "51.643.369/0001-53",
        "50.289.282/0001-67",
        "18.173.234/0001-04",
        "19.295.457/0001-07",
        "04.947.448/0001-78",
        "67.258.129/0001-75",
        "05.806.928/0001-81",
        "21.483.922/0001-77",
        "49.335.739/0001-43",
        "07.677.549/0001-64",
        "32.511.424/0002-98",
        "32.511.424/0001-07",
        "03.552.722/0001-47",
        "35.161.624/0001-75",
        "14.289.140/0001-36",
        "36.334.794/0001-77",
        "01.429.719/0001-05",
        "47.060.790/0001-64",
        "35.152.316/0001-83",
        "74.247.461/0001-08",
        "39.239.254/0001-00",
        "51.023.288/0001-50",
        "48.400.369/0001-18",
        "46.337.407/0001-00",
        "05.880.833/0001-08",
        "03.073.325/0001-92",
        "01.220.028/0001-05",
        "74.360.991/0001-50",
        "05.965.071/0001-42",
        "44.225.669/0001-57",
        "17.825.538/0001-38",
        "32.533.022/0001-03",
        "27.934.495/0001-82",
        "66.589.201/0001-84",
        "50.020.068/0001-00",
        "00.028.447/0001-79",
        "33.177.859/0001-20",
        "34.577.124/0001-56",
        "45.062.588/0001-46",
        "04.516.120/0001-05",
        "32.664.879/0001-62",
        "23.497.288/0001-57",
        "11.865.469/0001-00",
        "26.352.932/0001-97",
        "07.244.893/0001-60",
        "20.424.638/0001-67",
        "44.602.039/0001-54",
        "44.947.530/0001-17",
        "45.319.923/0001-49",
        "45.335.183/0001-34",
        "39.644.823/0001-95",
        "08.704.077/0001-54",
        "36.098.756/0001-62",
        "05.541.086/0001-83",
        "38.026.734/0001-12",
        "39.897.569/0001-37",
        "26.451.180/0001-11",
        "34.198.050/0001-47",
        "39.273.447/0001-70",
        "18.745.519/0001-64",
        "50.359.077/0001-20",
        "08.784.226/0001-32",
        "29.295.894/0001-76",
        "03.228.317/0001-78",
        "07.377.667/0001-57",
        "30.820.844/0001-40",
        "07.583.787/0001-00",
        "19.371.270/0001-37"
    ];

    $lucroPresumido = [
        "36.064.463/0001-64",
        "19.695.889/0001-05",
        "47.237.632/0001-37",
        "24.664.148/0001-99",
        "47.679.467/0001-73",
        "07.517.177/0001-09",
        "32.438.167/0001-25",
        "19.300.503/0001-00",
        "22.638.231/0001-68",
        "41.929.393/0001-08",
        "41.252.013/0001-35",
        "51.024.563/0001-50",
        "10.965.962/0001-39",
        "12.183.739/0001-65",
        "05.342.425/0001-00",
        "13.867.533/0001-17",
        "40.992.621/0001-13",
        "02.953.462/0001-59",
        "14.798.340/0001-14",
        "36.191.127/0001-82",
        "10.893.810/0001-78",
        "03.528.740/0001-93",
        "03.528.740/0002-74",
        "00.756.836/0001-10",
        "39.566.364/0001-79",
        "54.350.020/0001-11",
        "33.872.619/0001-45",
        "14.537.202/0001-81",
        "36.324.776/0001-04",
        "04.952.185/0001-95",
        "28.000.764/0001-04",
        "58.249.251/0001-94",
        "00.519.459/0001-04",
        "10.959.465/0001-28",
        "04.170.211/0001-23",
        "13.296.559/0001-52",
        "35.913.259/0001-08",
        "39.436.924/0001-70",
        "45.154.080/0001-78",
        "55.689.012/0001-67",
        "41.386.792/0001-61",
        "03.118.253/0001-52",
        "03.118.253/0002-33",
        "24.197.977/0001-09",
        "43.471.398/0001-57",
        "14.425.980/0001-89",
        "07.677.675/0001-19",
        "19.696.935/0001-82",
        "08.724.788/0001-90",
        "22.336.048/0001-08",
        "22.336.048/0002-99",
        "48.547.347/0001-85",
        "48.547.347/0002-66",
        "42.360.954/0001-55",
        "45.355.783/0001-64",
        "32.385.964/0001-91",
        "41.302.914/0001-94",
        "11.216.986/0001-58",
        "31.510.926/0001-50",
        "44.647.512/0001-10",
        "28.082.134/0001-18"
    ];

    $lucroReal = [
        "50.449.414/0001-70",
        "24.516.103/0001-77",
        "08.838.383/0001-83",
        "64.654.650/0001-33",
        "01.601.165/0001-81",
        "02.185.665/0001-42",
        "28.557.004/0001-94",
        "28.557.004/0002-75",
        "62.430.194/0001-12",
        "62.430.194/0002-01",
        "62.430.194/0003-84",
        "05.794.155/0001-60",
        "05.794.155/0003-22",
        "05.794.155/0004-03",
        "05.794.155/0002-41",
        "22.320.174/0001-74",
        "44.457.344/0001-08",
        "46.283.524/0001-38",
        "14.798.308/0001-39",
        "14.798.308/0002-10",
        "31.276.127/0001-61",
        "51.653.293/0001-47",
        "08.767.604/0001-70",
        "05.094.642/0001-10",
        "05.094.642/0002-00",
        "44.580.685/0001-68",
        "46.054.548/0001-15",
        "17.999.051/0001-71",
        "09.652.135/0001-06",
        "60.011.343/0002-64",
        "60.011.343/0001-83"  
    ];

    $empresaCodigo = ''; // Inicializa a variável

    if (in_array($empresaCnpj, $simplesNacional)) {
        $empresaCodigo = '99';
    } elseif (in_array($empresaCnpj, $lucroPresumido)) {
        $empresaCodigo = '98';
    } elseif (in_array($empresaCnpj, $lucroReal)) {
        $empresaCodigo = '70';
    }





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
    $fields[] = 'NFSE';

    // Campo 9 - Série do documento, neste exemplo estamos deixando em branco
    $fields[] = '';

    // Campo 10 - Valor contábil da nota
    $fields[] = number_format($valorServicos, 2, '.', '');

     // Campo 11 - Valor do Calculo ISS
    //$fields[] = number_format($valorBase, 2, '.', '');
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 12 - Valor Aliquota
    //$fields[] = number_format($valorAliquota, 2, '.', '');
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 13 - Valor ISS calculado
    //$fields[] = number_format($valorIss, 2, '.', '');
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 14 - Observação, neste exemplo estamos deixando em branco
    $fields[] = '';

    // Campo 15 - Valor contábil da nota (Valor base de cálculo do imposto de renda retido na fonte)
    $fields[] = number_format($valorServicos, 2, '.', '');

    // Campo 16 - Alíquota do I.R.R.F 
   $fields[] = number_format($aliquotaIR, 2, '.', '');

    // Campo 17 - Valor IR ----------------------------------------------------------------------------------
    $fields[] = number_format($valorIR, 2, '.', '');

    // Campo 18 - Condição pagamento da nota ----------------------------------------------------------------
    $fields[] = '';

    // Campo 19 - Conta Débito Exportação P/ contabilida
    $fields[] = '';

    // Campo 20 - Conta Crédito Exportação P/ contabilida
    $fields[] = '';

    // Campo 21 - Redução da base de cálculo de cálculo 
    $fields[] = null;

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
    $fields[] = number_format($valorINSS, 2, '.', '');

    // Campo 43 - Tipo de Prestação de Serviço 
    $fields[] = $codigoServico;

    // Campo 44 - Tipo de recolhimento do ISSQN para cidades 
    $fields[] = '';

    // Campo 45 - Tipo de lançamento
    $fields[] = 'R';

    // Campo 46 -Código do Fornecedor, quando o estado da empresa for igual a MS e o tipo do Lançamento for R (Recebidos)
    $fields[] = '';

    // Campo 47 -Estado do Fornecedor, quando o estado da empresa for igual a MS e o tipo do Lançamento for R (Recebidos)
    $fields[] = '';

    // Campo 48 - Acréscimos Deduções do IRRF
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 49 - Código da Atividade Municipal
    $fields[] = $codigoServico;

    // Campo 50 - Código da Atividade Municipal Joinville -SC
    $fields[] = '';

    // Campo 51 - Valor dos Materiais para Empresas que tem Deduções de ISS 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 52 - Valor das Subempreitadas para Empresas que tem Deduções de ISS
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 53 - Base de cálculo do ISS 1
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 54 - Base de cálculo do ISS 2
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 55 - Base de cálculo do ISS 3
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 56 - Base de cálculo do ISS 4
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 57 - Base de cálculo do ISS 5
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 58 - Base de cálculo do ISS 6
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 59 - Base de cálculo do ISS 7
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 60 - Base de cálculo do ISS 8
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 61 - Base de cálculo do ISS 9
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 62 - Alíquota do ISS 1
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 63 - Alíquota do ISS 2
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 64 - Alíquota do ISS 3
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 65 - Alíquota do ISS 4
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 66 - Alíquota do ISS 5
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 67 - Alíquota do ISS 6
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 68 - Alíquota do ISS 7
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 69 - Alíquota do ISS 8
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 70 - Alíquota do ISS 9
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 71 - Valor do ISS 1
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 72 - Valor do ISS 2
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 73 - Valor do ISS 3
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 74 - Valor do ISS 4
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 75 - Valor do ISS 5
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 76 - Valor do ISS 6
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 77 - Valor do ISS 7
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 78 - Valor do ISS 8
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 79 - Valor do ISS 9
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 80 - Valor da base do ISS 1
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 81 - Valor da base do ISS 2
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 82 - Valor da base do ISS 3
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 83 - Valor da base do ISS 4
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 84 - Valor da base do ISS 5
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 85 - Valor da base do ISS 6
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 86 - Valor da base do ISS 7
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 87 - Valor da base do ISS 8
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 88 - Valor da base do ISS 9
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 89 - Percentual redução da base ISS 1
    $fields[] = number_format($zero, 2, '.', '');
    
    // Campo 90 - Percentual redução da base ISS 2
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 91 - Percentual redução da base ISS 3
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 92 - Percentual redução da base ISS 4
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 93 - Percentual redução da base ISS 5
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 94 - Percentual redução da base ISS 6
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 95 - Percentual redução da base ISS 7
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 96 - Percentual redução da base ISS 8
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 97 - Percentual redução da base ISS 9
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 98 - Tipo da Observação 
    $fields[] = 'F';

    // Campo 99 - Código da natureza de operação -----------------------------------------------------------------
    $fields[] = '1933000';

    // Campo 100 - Valor da Retenção do PIS
    $fields[] = number_format($valorPIS, 2, '.', '');

    // Campo 101 - Valor da Retenção do COFINS
    $fields[] = number_format($valorCofins, 2, '.', '');
    //dd($valorCofins);
    // Campo 102 - Valor da Contribuição Social
    $fields[] = number_format($valorCSLL, 2, '.', '');

    // Campo 103 - Valor da Contribuição Social
    $fields[] = null;

    // Campo 104 - Código Fiscal de Prestação de Serviço
    $fields[] = '';

    // Campo 105 - Tipo Nota
    $fields[] = '';

    // Campo 106 - Situação 
    $fields[] = '';

    // Campo 107 - Valor da Retenção do ISS
    $fields[] = number_format($valorISSRetido, 2, '.', '');
    //dd($valorISSRetido);
    // Campo 108 - Código da Atividade Serviço
    $fields[] = '';

    // Campo 109 - Série INEDAM
    $fields[] = '';

    // Campo 110 - Natureza de Operação INEDAM
    $fields[] = '';

    // Campo 111 - Modelo INEDAM
    $fields[] = '';

    // Campo 112 - Código de receita da Retenção do PIS
    $fields[] = null;

    // Campo 113 - Código de receita da Retenção do COFINS
    $fields[] = null;

    // Campo 114 - Código de receita da Retenção da CONTRIBUIÇÃO SOCIAL 
    $fields[] = null;

    // Campo 115 - Modelo documento para cidades Belo horizonte/campos grande
    $fields[] = '';

    // Campo 116 - Natureza de Operação – Somente Corumbá
    $fields[] = '';

    // Campo 117 - Indicador (PJ / ST / Ajuste) ----------------------------------------------------
    $fields[] = '';

    // Campo 118 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 119 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 120 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 121 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 122 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 123 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 124 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 125 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 126 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 127 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 128 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 129 -  Tipo Nota GISSONLINE
    $fields[] = '1';

    // Campo 130 -  Situação da Nota 
    $fields[] = '';

    // Campo 131 -  Tipo Nota 
    $fields[] = '';

    // Campo 132 - Valor do PIS Lucro Real. 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 133 - Valor do COFINS Lucro Real. 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 134 -Natureza Operação CAMPINAS, SOROCABA, SANTA MARIA e CANOAS 
    $fields[] = null;

    // Campo 135 - Base de cálculo 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 136 -  Tipo de documento 
    $fields[] = '';

    // Campo 137 - Código da situação tributária da declaração do serviço  
    $fields[] = '';

    // Campo 138 - Código da obra 
    $fields[] = '';

    // Campo 139 - Identificação do serviço para Curitiba/PR  
    $fields[] = '';

    // Campo 140 - Tributação da nota para Campinas/SP, Sorocaba/SP e Cuiabá/MT 
    $fields[] = '';

    // Campo 141 - Valor das deduções da nota
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 142 -  Nome do responsável, pessoa física...
    $fields[] = '';
    
    // Campo 143 -  CPF do responsável pelo pagamento 
    $fields[] = '';

    // Campo 144 -  Nome do beneficiário do serviço médico...
    $fields[] = '';

    // Campo 145 -  CPF do beneficiário para a DMED 
    $fields[] = '';

    // Campo 146 -  Data de nascimento do beneficiário para a DMED 
    $fields[] = '';

    // Campo 147 - Número da AIDF (ISS... 
    $fields[] = null;

    // Campo 148 - ano da AIDF (ISS... 
    $fields[] = null;

    // Campo 149 -  Situação do documento (Sped Pis/Cofins)
    $fields[] = '00';

    // Campo 150 -  Chave da nota fiscal de serviços eletrônica (Sped pis/cofins ) 
    $fields[] = '';

    // Campo 151 -  Data de execução do serviço (Sped Pis/Cofins) 
    $fields[] = $dataHoraEmissao;

    // Campo 152 - Base do Pis
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 153 - Aliquota do Pis
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 154 - Valor do Pis
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 155 -  CTS do Pis 
    $fields[] = $empresaCodigo;

    // Campo 156 - Base do Pis importação
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 157 - Valor do Pis importação
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 158 - Data de pagamento do Pis  
    $fields[] = '';

    // Campo 159 - Base do Cofins
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 160 - Aliquota do Cofins
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 161 - Valor do Cofins
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 162 - CST do Cofins  
    $fields[] = $empresaCodigo;

    // Campo 163 - Base do Cofins importação
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 164 - Valor do Cofins importação
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 165 - Data do pagamento do Cofins - Importação  
    $fields[] = '';

    // Campo 166 - Indicador do local da prestação do serviço 
    $fields[] = null;

    // Campo 167 -Código da base de cálculo do crédito... 
    $fields[] = '';

    // Campo 168 -Código do produto vinculado ao serviço 
    $fields[] = $codigoServico;

    // Campo 169 - Tipo da Nota – Itajaí 
    $fields[] = '';

    // Campo 170 - Código de receita IRRF 
    $fields[] = 1708;

    // Campo 171 - Tipo documento: 
    $fields[] = 00;

    // Campo 172 - Tipo da natureza de operação  
    $fields[] = '';

    // Campo 173 - Número da nota Fiscal a deduzir 
    $fields[] = null;

    // Campo 174 - Valor NF a deduzir (Belo Horizonte/MG)
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 175 - Retenção de SEST/Senat
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 176 - Valor da Glosa
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 177 - Valor do PIS de retenção da Glosa
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 178 - Valor do COFINS de retenção da Glosa
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 179 - Valor do CSLL de retenção da Glosa
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 180 - Valor do IR de retenção da Glosa
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 181 - Valor do INSS de retenção da Glosa
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 182 - Valor do SEST/Senat de retenção da Glosa
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 183 - Motivo cancelamento – ISS Tinus
    $fields[] = '';

    // Campo 184 - Base Legal – ISS Tinus
    $fields[] = null;

    // Campo 185 - Número sequencial do recibo – ISS Tinus 
    $fields[] = null;

    // Campo 186 -  Nota avulsa ISS Tinus 
    $fields[] = '';

    // Campo 187 - Descrição do serviço DIF mensal Taió
    $fields[] = '';

    // Campo 188 - Indicador da natureza da retençaõ f600 sped
    $fields[] = null;

    // Campo 189 - Código da atividade para contribuição previdenciária ----------------------------
    $fields[] = '';

    // Campo 190 - 01 - Complementar – ISS Itajaí 
    $fields[] = '';

    // Campo 190 - 02 -Regime de Tributação ISS Simpliss 
    $fields[] = '';

    // Campo 191 - Tributação de ISS no município do cliente/fornecedor 
    $fields[] = '';

    // Campo 192 - Data da anulação  
    $fields[] = '';

    // Campo 193 - Tipo de escrituração de Porto Alegre/RS
    $fields[] = null;

    // Campo 194 - Tipo de serviço de Porto Alegre/R
    $fields[] = null;

    // Campo 195 - Tipo de serviço de Porto Alegre/R ????????????????????????????????????????????????
    $fields[] = null;

    // Campo 196 - Número do tíquete de serviço de Porto Alegre/RS ???????????????????????????????????
    $fields[] = null;

    // Campo 197 - número da sub–declaração de serviço de Porto Alegre ???????????????????????????????????
    $fields[] = null;

    // Campo 198 - Número da matrícula de obra de serviço de Porto Alegre/RS ???????????????????????????????????
    $fields[] = null;

    // Campo 199 -Valor da dedução de turismo – passagens aéreas  ???????????????????????????????????
    $fields[] = null;

    // Campo 200 - Valor da dedução de turismo – diárias de Porto Alegre/R  ???????????????????????????????????
    $fields[] = null;

    // Campo 201 - Valor do imposto retido – ST de Porto Alegre/RS  ???????????????????????????????????
    $fields[] = null;

    // Campo 202 - CNPJ da empresa que está fazendo o lançamento para validação do arquivo  ----------------------
    $fields[] = '';

    // Campo 203 - Regime especial de tributação – Belo Horizonte  ???????????????????????????????????
    $fields[] = null;

    // Campo 204 - Regime especial de tributação – Belo Horizonte  ???????????????????????????????????
    $fields[] = null;

    // Campo 205 -Cidade de Incidência do ISSQN – Código IBGE – Belo Horizonte  VAZIOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 206 - Inscrição estadual do Cliente/Fornecedor
    $fields[] = '';

    // Campo 207 - Motivo do Cancelamento – Belo Horizonte VAZIOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 208 - NFSe Substituidora – Belo Horizonte
    $fields[] = '';

    // Campo 209 - Código da conta financeira (GIF-IF) 
    $fields[] = '';

    // Campo 210 - Nota Fiscal de Serviço o Eletrônica (Maringá) 
    $fields[] = '';

    // Campo 211 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 212 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 213 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 214 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 215 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 216 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 217 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 218 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 219 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 220 - Base de cálculo de prestação de serviços para outros municípios (anexo VI) VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 221 - Valor do desconto VAZIOOOOOOOOOOOOOOOO
    $fields[] = null;

    // Campo 222 -  Indicador da obra 
    $fields[] = '';

    // Campo 223 -  CNO - Código nacional de obra 
    $fields[] = '';
    
    // Campo 224 -  Tipo do serviço conforme tabela 6 REINF 
    $fields[] = '';

    // Campo 225 -  Incidência da CPRB no prestador (Serviços Tomados) 
    $fields[] = null;

    // Campo 226 -  Situação do documento Sped  
    $fields[] = null;

    // Campo 227 -  Modelo do documento sped  
    $fields[] = '';
    
    // Campo 228 -  Chave DF-e Sped  
    $fields[] = '';

    // Campo 229 -  Emitente  -------------------------------------------
    $fields[] = '';

    // Campo 230 -  Quantidade cancelada 
    $fields[] = null;

    // Campo 231 - Outras Deduções INSS 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 232 - Redução da base de INSS 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 233 - Materiais de terceiros 
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 234 - receita inss
    $fields[] = null;

    // Campo 235 - Base de retenção de INSS ------------------------------------------
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 236 -Indicador da origem do crédito ----------------------------------
    $fields[] = "0";

    $fields = implode(",", $fields);

    return $fields;  // Adicione esta linha para retornar $fields
}

}