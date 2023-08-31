<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pdf</title>
</head>
<body>
    
    <?php if (session()->getFlashdata('message')): ?>
        <div><?php echo session()->getFlashdata('message') ?></div>
    <?php endif; ?>    
    
    <?php if (session()->getFlashdata('error')): ?>
        <div><?php echo session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <form action="<?php echo site_url('pdfcontroller/upload')?>" method="post" enctype="multipart/form-data">
        <label for="pdf_file"></label>
        <input type="file" name="pdf_file" id="pdf_file" accept=".pdf">
        <button type="submit">Enviar</button>
    </form>


</body>
</html>