<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

</head>
<body>

    <form action="<?php echo site_url('CropController2/upload')?>" method="post" enctype="multipart/form-data">

    <label for="crop_file"></label>
    <input type="file" id="crop_file" name="crop_file[]" accept=".pdf" multiple>
    <button type="submit">Enviar</button>
    </form>
    
</body>
</html>
