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
                // Chama o método extractFields e guarda o resultado na variável $fields
                $fields = $this->extractFields($extractedText);

                //dd($extractedText);
                // Se não houver um erro, mostra os campos extraídos.
                return view('pdf_result', ['fields' => $fields]);
            } else {
                return view('pdf_error', ['error' => 'Failed to extract text.']);
            }
        }
    } else {
        return view('pdf_error', ['error' => $file->getErrorString(). ' ' .$file->getError()]);
    }
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

    //Valor ISS
    preg_match("/([\d\.,]+)\s*\(\X\) Sim \(\) Não/", $text, $matches);
    $valorIss = isset($matches[1]) ? (float)str_replace(',', '.', str_replace('.', '', $matches[1])) : 0.0;

// Valor de INSS
$lines = preg_split("/(\r?\n)+/", $text);
$inssIndex = array_search('INSS(R$)', $lines);
$valorINSS = 0.0;  // Inicializa como 0.0

if ($inssIndex !== false && isset($lines[$inssIndex + 1])) {
    preg_match("/[\d\.,]+/", trim($lines[$inssIndex + 1]), $matches);

    // Apenas atualiza o valor de INSS se o valor encontrado for um número
    if (!empty($matches) && is_numeric(str_replace(',', '.', str_replace('.', '', $matches[0])))) {
        $valorINSS = (float)str_replace(',', '.', str_replace('.', '', $matches[0]));
    }
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
    $fields[] = '';

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
    $fields[] = '';

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
    $fields[] = '';

    // Campo 100 - Valor da Retenção do PIS
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 101 - Valor da Retenção do COFINS
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 102 - Valor da Contribuição Social
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 103 - Valor da Contribuição Social
    $fields[] = null;

    // Campo 104 - Código Fiscal de Prestação de Serviço
    $fields[] = '';

    // Campo 105 - Tipo Nota
    $fields[] = '';

    // Campo 106 - Situação 
    $fields[] = '';

    // Campo 107 - Valor da Retenção do ISS
    $fields[] = number_format($zero, 2, '.', '');

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

    // Campo 129 -  Tipo Nota GISSONLINE ------------------------------------------------------------------------
    $fields[] = '';

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
    $fields[] = '';

    // Campo 152 - Base do Pis
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 153 - Aliquota do Pis
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 154 - Valor do Pis
    $fields[] = number_format($zero, 2, '.', '');

    // Campo 155 -  CTS do Pis 
    $fields[] = '';

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
    $fields[] = '';

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
    $fields[] = '';

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