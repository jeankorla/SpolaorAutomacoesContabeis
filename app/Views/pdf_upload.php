<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background-color: #1F628E;">
  <div class="container-fluid navbar-container">
    <a class="navbar-brand" href="#">
        <img class="img-fluid" src="<?= base_url('img/logo-lado.svg') ?>" alt="Logolado">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- Itens de navegação -->
      </ul>
      <!-- Formulário de pesquisa -->
    </div>
  </div>
</nav>
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
