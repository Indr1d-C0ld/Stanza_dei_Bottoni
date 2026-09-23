<?php defined('BASE') || exit; // si include da index.php, non si apre dal browser ?>
<?php
/** @var array<string,mixed>|null $chi */
/** @var string $gettone */
?>
<h1>Una parola d'ordine nuova</h1>
<?php if ($chi === null): ?>
  <p class="allarme">Questo collegamento non vale più: o è scaduto — dura
     un'ora — o è già stato usato.</p>
  <p><a href="<?= u('/dimenticata') ?>">Chiedine un altro</a>.</p>
<?php else: ?>
  <p class="tenue">Bentornato, <?= htmlspecialchars((string) $chi['nome']) ?>.
     Scegline una nuova, di almeno
     <?= App\Gioco\Sessione::LUNGHEZZA_MINIMA ?> caratteri.</p>
  <form method="post" action="" class="modulo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="reimposta">
    <input type="hidden" name="g" value="<?= htmlspecialchars($gettone) ?>">
    <label>Parola d'ordine nuova
      <input type="password" name="nuova" required
             minlength="<?= App\Gioco\Sessione::LUNGHEZZA_MINIMA ?>"
             autocomplete="new-password"></label>
    <label>Di nuovo, per sicurezza
      <input type="password" name="conferma" required
             minlength="<?= App\Gioco\Sessione::LUNGHEZZA_MINIMA ?>"
             autocomplete="new-password"></label>
    <button type="submit">Cambiala</button>
  </form>
<?php endif; ?>
