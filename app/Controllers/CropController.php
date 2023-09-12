<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use thiagoalessio\TesseractOCR\TesseractOCR;

class CropController extends BaseController
{
    public function index()
    {
        return view ('cropped');
    } 

    public function crop()
    {
      $startTime = microtime(true);

      set_time_limit(100000);

      //função para eu recortar varios pedaços
      function cropAndSave($image, $cropSettings, $outputName) {
        $croppedImage = imagecrop($image, $cropSettings);
        imagejpeg($croppedImage, $outputName);
      }

      
      //Buscando os arquivos(NFSE) enviados 
      $crop_file = $this->request->getFiles();
      echo "Tempo após a operação request: " . (microtime(true) - $startTime) . " segundos.<br>";
      
      
      //se tiver arquivo(NFSE) na variavel retorna TRUE e starta o IF
      if($crop_file){
        // Crie a pasta mãe com um nome aleatório
        $motherFolderName = bin2hex(random_bytes(10)); // nome aleatório para a pasta mãe
        $motherFolderPath = '../writable/crop/' . $motherFolderName . '/';
        if(!is_dir($motherFolderPath)) {
          mkdir($motherFolderPath, 0777, true);
        }

        echo "Tempo após a operação criação da pasta mãe: " . (microtime(true) - $startTime) . " segundos.<br>";

        //Instancio o Array em outra variavel usando o Foreach
        foreach($crop_file['crop_file'] as $files){
          $allResults = [];

          echo "Tempo após a operação instaciando array crop_file: " . (microtime(true) - $startTime) . " segundos.<br>";

          //Verificação de se os arquivos foram validos E for diferente 
          if($files->isValid() && !$files->hasMoved()) {

            $tempName = $files->getRandomName();
           
            $files->move('../writable/temp/', $tempName);

            $pdfPath = '../writable/temp/' . $tempName;
            $baseNameWithoutExtension = pathinfo($tempName, PATHINFO_FILENAME);


            echo "Tempo após pegar o arquivo pdf e enviar para temp com nome aleatorio: " . (microtime(true) - $startTime) . " segundos.<br>";

            // Crie uma pasta filho para cada imagem convertida
            $childFolderPath = $motherFolderPath . $baseNameWithoutExtension . '/';

            

            
            if(!is_dir($childFolderPath)) {
              mkdir($childFolderPath, 0777, true);
            }

            echo "Tempo após a operação criando pasta filho: " . (microtime(true) - $startTime) . " segundos.<br>";


            $convertedImgName = $baseNameWithoutExtension . '.jpg';
            $outputPath = $childFolderPath . $convertedImgName;

            $command = "pdftoppm -jpeg \"$pdfPath\" \"$outputPath\"";
            exec($command);

            $img = imagecreatefromjpeg($outputPath . "-1.jpg");

            echo "Tempo após converter pdf em jpg: " . (microtime(true) - $startTime) . " segundos.<br>";

            // CNPJ TOMADOR
            $cropSettings1 = ['x' => 155, 'y' => 548, 'width' => 192, 'height' => 34];
            $cnpjTomador = $childFolderPath . 'CnpjTomador.jpg';
            cropAndSave($img, $cropSettings1, $cnpjTomador);

            // Recorte Numero nota fiscal
            $cropSettings2 = ['x' => 913, 'y' => 98, 'width' => 104, 'height' => 58];
            $numeroNota = $childFolderPath . 'NumeroNota.jpg';
            cropAndSave($img, $cropSettings2, $numeroNota);

            // CNPJ PRESTADOR
            $cropSettings3 = ['x' => 275, 'y' => 366, 'width' => 170, 'height' => 38];
            $cnpjPrestador = $childFolderPath . 'CnpjPrestador.jpg';
            cropAndSave($img, $cropSettings3, $cnpjPrestador);

            // Data
            $cropSettings4 = ['x' => 664, 'y' => 172, 'width' => 90, 'height' => 40];
            $dataCompetencia = $childFolderPath . 'Data.jpg';
            cropAndSave($img, $cropSettings4, $dataCompetencia);

            // Valor do Serviço
            $cropSettings5 = ['x' => 332, 'y' => 1190, 'width' => 95, 'height' => 30];
            $valorServico = $childFolderPath . 'ValorServico.jpg';
            cropAndSave($img, $cropSettings5, $valorServico);

            // Base de Calculo
            $cropSettings6 = ['x' => 1048, 'y' => 1315, 'width' => 95, 'height' => 30];
            $baseCalculo = $childFolderPath . 'BaseCalculo.jpg';
            cropAndSave($img, $cropSettings6, $baseCalculo);

            // Aliquota %
            $cropSettings7 = ['x' => 1051, 'y' => 1357, 'width' => 95, 'height' => 30];
            $aliquota = $childFolderPath . 'Aliquota.jpg';
            cropAndSave($img, $cropSettings7, $aliquota);

            // ISS
            $cropSettings8 = ['x' => 1045, 'y' => 1443, 'width' => 95, 'height' => 50];
            $iss = $childFolderPath . 'ISS.jpg';
            cropAndSave($img, $cropSettings8, $iss);

            // PIS
            $cropSettings9 = ['x' => 211, 'y' => 1097, 'width' => 95, 'height' => 50];
            $pis = $childFolderPath . 'PIS.jpg';
            cropAndSave($img, $cropSettings9, $pis);

            // COFINS
            $cropSettings10 = ['x' => 409, 'y' => 1097, 'width' => 95, 'height' => 50];
            $cofins = $childFolderPath . 'COFINS.jpg';
            cropAndSave($img, $cropSettings10, $cofins);

            // IR
            $cropSettings11 = ['x' => 613, 'y' => 1097, 'width' => 95, 'height' => 50];
            $ir = $childFolderPath . 'IR.jpg';
            cropAndSave($img, $cropSettings11, $ir);

            // INSS
            $cropSettings12 = ['x' => 809, 'y' => 1097, 'width' => 95, 'height' => 50];
            $inss = $childFolderPath . 'INSS.jpg';
            cropAndSave($img, $cropSettings12, $inss);

             // CSLL
            $cropSettings13 = ['x' => 1007, 'y' => 1097, 'width' => 192, 'height' => 50];
            $csll = $childFolderPath . 'CSLL.jpg';
            cropAndSave($img, $cropSettings13, $csll);

            // Valor liquido
            $cropSettings14 = ['x' => 326, 'y' => 1443, 'width' => 95, 'height' => 50];
            $valorLiquido = $childFolderPath . 'ValorLiquido.jpg';
            cropAndSave($img, $cropSettings14, $valorLiquido);

            // Codigo de serviço / Atividade
            $cropSettings15 = ['x' => 25, 'y' => 931, 'width' => 1150, 'height' => 50];
            $codAtiv = $childFolderPath . 'CodAtiv.jpg';
            cropAndSave($img, $cropSettings15, $codAtiv);

            echo "Tempo após recortar todos os pedaços: " . (microtime(true) - $startTime) . " segundos.<br>";

            // echo (new TesseractOCR($childFolderPath . '.jpg'))
            // ->run();
             $orderedFiles = [
              'CnpjTomador.jpg',
              'NumeroNota.jpg',
              'CnpjPrestador.jpg',
              'Data.jpg',
              'ValorServico.jpg',
              'BaseCalculo.jpg',
              'Aliquota.jpg',
              'ISS.jpg',
              'PIS.jpg',
              'COFINS.jpg',
              'IR.jpg',
              'INSS.jpg',
              'CSLL.jpg',
              'ValorLiquido.jpg',
              // Adicione mais nomes conforme necessário
            ];

            // Construa seu resultado como você fez antes
              $output = [];

              foreach ($orderedFiles as $file) {
              try {
                  $ocrResult = (new TesseractOCR($childFolderPath . $file))->run();
                  $output[] = "$ocrResult";
                  
                  if ($file === 'ValorLiquido.jpg') {
                      $output[] = "\n";  // Adiciona uma quebra de linha após processar o arquivo ValorLiquido.jpg
                  }
                  
              } catch (\thiagoalessio\TesseractOCR\UnsuccessfulCommandException $e) {
                  $output[] = "";
              }
          }

              $resultString = implode(",", $output);
              $allResults[] = $resultString;

              echo "Tempo após pegar os resultados em string das imagens: " . (microtime(true) - $startTime) . " segundos.<br>";
        }

        // var_dump($allResults);

        //  Construir o conteúdo do arquivo XML
        //       var_dump($crop_file);
              // $xmlContent = implode("\n", $allResults); // Separa cada linha por uma quebra de linha
             
              // echo "Tempo após construir o XML: " . (microtime(true) - $startTime) . " segundos.<br>";
              // // Defina os cabeçalhos para forçar o download do arquivo XML
              // header('Content-Type: application/xml');
              // header('Content-Disposition: attachment; filename="result.xml"');

              // // Retorne o conteúdo
              // echo $xmlContent;

              $endTime = microtime(true);
              $totalTime = $endTime - $startTime;
              echo "Tempo total de execução: $totalTime segundos.<br>";
               
          }
      }
    }
}
