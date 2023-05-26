<style>

.left-button-sc{
    position: absolute;
    top: 40%;
    left: 5%;
    font-size: 3vw; /* Tamanho do texto em porcentagem da largura da viewport */
    background-color: #1F628E;
    border-radius: 22px;
    padding: 1.5vw 5vw; /* Padding em porcentagem da largura da viewport */
    margin-right: 0;
    max-width: 30%; /* Tamanho máximo em porcentagem da largura da imagem */
    text-align: center;
    color: #fff;
    font-family: 'Montserrat', sans-serif;
    font-weight: 500;
}
.right-button-sc{
    position: absolute;
    top: 40%;
    right: 5%;
    font-size: 3vw;
    background-color: #1F628E;
    border-radius: 22px;
    padding: 1.5vw 5vw;
    margin-right: 0;
    max-width: 30%;
    text-align: center;
    color: #fff;
    font-family: 'Montserrat', sans-serif;
    font-weight: 500;
}
a:hover{
    transform: scale(1.05);
    transition: transform 1.5s ease-in-out;
}

</style>


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

<div>
    
      <a class="left-button-sc" style="cursor: pointer; text-decoration: none;"
      href=""
      >Conciliação Contábil</a>
    
      <a class="right-button-sc" style="cursor: pointer; text-decoration: none;"
      href="fiscal"
      >Coversor de PDF para XML</a>
</div>
