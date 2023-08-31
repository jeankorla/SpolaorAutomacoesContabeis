<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class CropController extends BaseController
{
    public function index()
    {
        return view ('cropped');
    } 
    public function crop()
    {
      function cropAndSave($image, $cropSettings, $outputName) {
      $croppedImage = imagecrop($image, $cropSettings);
      imagejpeg($croppedImage, $outputName);
    }
        $crop_file = $this->request->getFiles();
        
        // dd($crop_file);
        if($crop_file){

         
           foreach($crop_file['crop_file'] as $files){
             
             if($files -> isValid() && ! $files -> hasMoved())
              {
              //   dd($crop_file);

                $tempName = $files->getRandomName();
              //   dd($tempName);
                $files->move('../writable/temp/', $tempName);
                


                $pdfPath = '../writable/temp/' . $tempName;
                $baseNameWithoutExtension = pathinfo($tempName, PATHINFO_FILENAME);
                $convertedImgName = $baseNameWithoutExtension . '.jpg'; 
                $outputPath = '../writable/crop/' . $convertedImgName;

             
                $command = "pdftoppm -jpeg \"$pdfPath\" \"$outputPath\"";
                exec($command);
                
                $img = imagecreatefromjpeg($outputPath . "-1.jpg");
                              
               // Primeiro recorte
                $cropSettings1 = ['x' => 0, 'y' => 0, 'width' => 200, 'height' => 200];
                $newName1 = '../writable/crop/recorte1.jpg';
                cropAndSave($img, $cropSettings1, $newName1);

                // Segundo recorte
                $cropSettings2 = ['x' => 50, 'y' => 50, 'width' => 100, 'height' => 100];
                $newName2 = '../writable/crop/recorte2.jpg';
                cropAndSave($img, $cropSettings2, $newName2);

          }
        }
    }
}
}