<?php defined('BASE') || exit; // si include da index.php, non si apre dal browser ?>
<?php
/** @var bool $riuscita */
/** @var string $detto */
?>
<h1>Conferma dell'indirizzo</h1>
<?php if ($riuscita): ?>
  <p><?= htmlspecialchars($detto) ?></p>
  <p><a href="<?= u('/entra') ?>">Entra</a></p>
<?php else: ?>
  <p class="allarme"><?= htmlspecialchars($detto) ?></p>
  <p class="tenue">Se il collegamento è scaduto puoi chiederne un altro dalla
     <a href="<?= u('/entra') ?>">pagina di accesso</a>.</p>
<?php endif; ?>
