<!-- pdf_upload.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Upload de PDF</title>
</head>
<body>
    <h1>Upload de PDF</h1>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="error"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <?= form_open_multipart('base/convertPdfToText') ?>
        <input type="file" name="pdf_file">
        <button type="submit">Enviar</button>
    <?= form_close() ?>
</body>
</html>
