<style>
  body {
  background-color: #eee;
}

/* === Wrapper Styles === */
#FileUpload {
  display: flex;
  justify-content: center;
}
.wrapper {
  margin: 30px;
  padding: 10px;
  box-shadow: 0 19px 38px rgba(0,0,0,0.30), 0 15px 12px rgba(0,0,0,0.22);
  border-radius: 10px;
  background-color: white;
  width: 415px;
}

/* === Upload Box === */
.upload {
  margin: 10px;
  height: 85px;
  border: 8px dashed #e6f5e9;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 5px;
}
.upload p {
  margin-top: 12px;
  line-height: 0;
  font-size: 22px;
  color: #0c3214;
  letter-spacing: 1.5px;
}
.upload__button {
  background-color: #e6f5e9;
  border-radius: 10px;
  padding: 0px 8px 0px 10px;
}
.upload__button:hover {
  cursor: pointer;
  opacity: 0.8;
}

/* === Uploaded Files === */
.uploaded {
  width: 375px;
  margin: 10px;
  background-color: #e6f5e9;
  border-radius: 10px;
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
}
.file {
  display: flex;
  flex-direction: column;
}
.file__name {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: baseline;
  width: 300px;
  line-height: 0;
  color: #0c3214;
  font-size: 18px;
  letter-spacing: 1.5px;
}
.fa-times:hover {
  cursor: pointer;
  opacity: 0.8;
}
.fa-file-pdf {
  padding: 15px;
  font-size: 40px;
  color: #0c3214;
}
</style>


<!DOCTYPE html>
<html>
<head>
  <title>Upload de PDF</title>
  <style>
  /* Seu código de estilo aqui */
  </style>
</head>
<body>
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

  <div id="FileUpload">
    <div class="wrapper">
      <h1>Upload de PDF</h1>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="error"><?= session()->getFlashdata('error') ?></div>
      <?php endif; ?>

      <?= form_open_multipart('base/convertPdfToText', ['class' => 'upload']) ?>
        <p>Envie seus arquivos
          <span class="upload__button">
            <input type="file" name="pdf_file[]" multiple style="display:none;">
            Navegar
          </span>
        </p>
      <?= form_close() ?>

      <!-- Loop through uploaded files -->
      <?php if (!empty(session()->get('uploaded_files'))): ?>
        <?php foreach(session()->get('uploaded_files') as $file): ?>
          <div class="uploaded">
            <i class="far fa-file-pdf"></i>
            <div class="file">
              <div class="file__name">
                <p><?= $file ?></p>
                <i class="fas fa-times"></i>
              </div>
              <div class="progress">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width:100%"></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  
</body>
</html>


<script>
  // Adicione um evento de clique ao elemento .upload__button
  // que aciona o clique no campo de entrada do arquivo.
  document.querySelector('.upload__button').addEventListener('click', function() {
    document.querySelector('input[type="file"]').click();
  });

  // Adicione um evento de alteração ao campo de entrada do arquivo
  // que envia o formulário quando um arquivo é selecionado.
  document.querySelector('input[type="file"]').addEventListener('change', function() {
    document.querySelector('.upload').submit();
  });
</script>
