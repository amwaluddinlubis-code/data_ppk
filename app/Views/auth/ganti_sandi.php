<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card bg-base-100 shadow-sm max-w-lg">
    <div class="card-body">
        <form method="post" action="<?= base_url('ganti-sandi') ?>" class="flex flex-col gap-3">
            <?= csrf_field() ?>
            <label class="form-control w-full">
                <span class="label-text font-semibold">Kata sandi lama</span>
                <input type="password" name="sandi_lama" class="input input-bordered w-full" required autocomplete="current-password">
            </label>
            <label class="form-control w-full">
                <span class="label-text font-semibold">Kata sandi baru (minimal 8 karakter)</span>
                <input type="password" name="sandi_baru" class="input input-bordered w-full" required autocomplete="new-password">
            </label>
            <label class="form-control w-full">
                <span class="label-text font-semibold">Ulangi kata sandi baru</span>
                <input type="password" name="sandi_baru_konfirmasi" class="input input-bordered w-full" required autocomplete="new-password">
            </label>
            <div class="flex gap-2 mt-1">
                <button type="submit" class="btn btn-primary">Simpan Kata Sandi</button>
                <a href="<?= base_url('/') ?>" class="btn btn-ghost">Batal</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('javascript') ?>
<?php // tidak ada JS khusus ?>
<?= $this->endSection() ?>
