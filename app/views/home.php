<?php
/** @var array{type: string, message: string}|null $flash */
/** @var array<string, mixed> $old */
/** @var array<string, string> $errors */
/** @var list<array<string, mixed>> $guests */
/** @var string|null $dbError */

$mode = (string) ($old['purpose_mode'] ?? 'manual');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buku Tamu</title>
<link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>
<body>
<main class="container">
  <header class="app-header">
    <h1>Buku Tamu</h1>
    <p class="subtitle">Pencatatan data tamu &amp; pengujian konektivitas API</p>
  </header>

  <?php if ($flash !== null): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
  <?php endif; ?>

  <section class="card">
    <h2>Form Data Tamu</h2>
    <form method="post" action="<?= e(app_url('/')) ?>" id="guest-form" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="name">Nama</label>
        <input type="text" id="name" name="name" maxlength="150" required
               value="<?= e((string) ($old['name'] ?? '')) ?>">
        <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" maxlength="255" required
               value="<?= e((string) ($old['email'] ?? '')) ?>">
        <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
      </div>

      <div class="field">
        <label for="phone">Nomor HP</label>
        <input type="tel" id="phone" name="phone" maxlength="30" required
               placeholder="Contoh: 081234567890"
               value="<?= e((string) ($old['phone'] ?? '')) ?>">
        <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
      </div>

      <fieldset class="field purpose-field">
        <legend>Keperluan Kunjungan</legend>

        <div class="purpose-mode" role="radiogroup" aria-label="Mode pengisian keperluan">
          <label class="segmented<?= $mode === 'manual' ? ' is-active' : '' ?>">
            <input type="radio" name="purpose_mode" value="manual"<?= $mode === 'manual' ? ' checked' : '' ?>> Tulis Manual
          </label>
          <label class="segmented<?= $mode === 'dropdown' ? ' is-active' : '' ?>">
            <input type="radio" name="purpose_mode" value="dropdown"<?= $mode === 'dropdown' ? ' checked' : '' ?>> Pilih Keperluan
          </label>
        </div>

        <input type="text" id="purpose-custom" name="purpose_custom" maxlength="500"
               placeholder="Tulis keperluan kunjungan Anda"
               value="<?= e((string) ($old['purpose_custom'] ?? '')) ?>"<?= $mode === 'manual' ? '' : ' disabled' ?>>

        <select id="purpose-select" name="visit_purpose"<?= $mode === 'dropdown' ? '' : ' disabled' ?>>
          <option value="Minta API Data Tamu">Minta API Data Tamu</option>
        </select>

        <?php if (isset($errors['visit_purpose'])): ?><p class="field-error"><?= e($errors['visit_purpose']) ?></p><?php endif; ?>
      </fieldset>

      <button type="submit" class="btn">Submit</button>
    </form>
  </section>

  <section class="card">
    <h2>Daftar Tamu</h2>
    <?php if ($dbError !== null): ?>
      <p class="flash flash-error" role="alert"><?= e($dbError) ?></p>
    <?php elseif ($guests === []): ?>
      <p class="empty">Belum ada data tamu.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>No. HP</th>
            <th>Keperluan</th>
            <th>IP Address</th>
            <th>Last Updated</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($guests as $guest): ?>
          <tr>
            <td><?= e((string) $guest['name']) ?></td>
            <td><?= e((string) $guest['email']) ?></td>
            <td><?= e((string) $guest['phone']) ?></td>
            <td><?= e((string) $guest['visit_purpose']) ?></td>
            <td><?= e((string) $guest['ip_address']) ?></td>
            <td><?= e(date('d M Y H:i', strtotime((string) $guest['updated_at']) ?: 0)) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <footer class="app-footer">
    <p>Endpoint API:
      <a href="<?= e(app_url('api/v1/health')) ?>">/api/v1/health</a> ·
      <a href="<?= e(app_url('api/v1/guests')) ?>">/api/v1/guests</a>
    </p>
  </footer>
</main>

<script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
