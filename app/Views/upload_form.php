<?= \Config\Services::validation()->listErrors() ?>

<form action="<?= site_url('UploadController/index') ?>" method="post" enctype="multipart/form-data">
    <input type="file" name="file" />
    <button type="submit">Upload</button>
</form>

<?php if (isset($message)): ?>
    <p><?= $message ?></p>

    <a href="<?= $url ?>" download><?= $url ?></a>
<?php endif; ?>
