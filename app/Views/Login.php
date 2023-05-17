<section class="vh-100 d-flex align-items-center" style="background-color: hsl(0, 0%, 96%)">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 mb-5 mb-lg-0 text-center">
        <img class="img-fluid mb-4" style="max-width: 70%; margin-top: -60px;" src="<?= base_url('img/logo.svg') ?>" alt="Logo">
        <h1 class="my-1 display-5 fw-bold" style="margin-top: -10px;">
          <span style="color: #1F628E">Empenhados </span> em facilitar o seu trabalho!
        </h1>
      </div>

      <div class="col-md-6 col-lg-4 mb-3 mb-lg-0 d-flex align-items-center ms-auto">
        <div class="card mx-auto w-100" style="border-radius: 30px;">
          <div class="card-body py-5 px-md-5">
            <h1 class="text-center">Login</h1>

            <form>
              <!-- Email input -->
              <div class="form-outline mb-4">
                <input type="text" id="name" name="name" class="form-control" />
                <label class="form-label" for="name">Username</label>
              </div>

              <!-- Password input -->
              <div class="form-outline mb-4">
                <input type="password" id="password" name="password" class="form-control" />
                <label class="form-label" for="password">Password</label>
              </div>

              <!-- Submit button -->
              <button type="submit" class="btn btn-primary btn-block mb-4">
                Sign in
              </button>

            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>
