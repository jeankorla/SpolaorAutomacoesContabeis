<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class PdfController extends BaseController
{
    public function index()
    {
        return view('pdf_upload');
    }
    
    public function upload(){

        $file = $this->request->getFile('pdf_file');

        //dd($file);

        if($file->isValid() && !$file->hasMoved())
        {
            $newName = $file->getRandomName();
            $file->move('./writable/uploads/', $newName);

            return redirect()->to('/pdfcontroller')->with('message', 'PDF uploaded successfully!');
        }

        return redirect()->to('/pdfcontroller')->with('message', 'PDF uploaded successfully!');
    }

}
