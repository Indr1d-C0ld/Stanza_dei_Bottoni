<?php defined('BASE') || exit; // si include da index.php, non si apre dal browser ?>
<?php /** @var string $modo */ ?>
<h1>Registrati</h1>

<?php if ($modo === 'chiuse'): ?>
  <p class="allarme">Le registrazioni sono chiuse. Non è una cosa che passa
     aspettando: se pensi di doverci essere, scrivi a chi ti ha parlato del gioco.</p>
<?php else: ?>
<p class="tenue">Il nome in gioco è quello con cui gli altri ti conosceranno. La
reputazione che ti costruirai — l'integrità, nel linguaggio del modello — ti
seguirà da una poltrona all'altra e da un paese all'altro.</p>
<form method="post" action="" class="modulo">
  <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
  <input type="hidden" name="azione" value="registrati">
<?php if ($modo === 'invito'): ?>
  <label>Codice d'invito
    <span class="tenue">(te l'ha dato chi ti ha chiamato al tavolo)</span>
    <input type="text" name="invito" required maxlength="24" autocomplete="off"
           style="text-transform:uppercase" placeholder="XXXXX-XXXXX"></label>
<?php endif; ?>
  <label>Nome in gioco<input type="text" name="nome" required minlength="3" maxlength="48"></label>
  <label>Indirizzo di posta<input type="email" name="email" required autocomplete="username"></label>
  <label>Parola d'ordine
    <span class="tenue">(almeno <?= App\Gioco\Sessione::LUNGHEZZA_MINIMA ?> caratteri)</span>
    <input type="password" name="password" required
           minlength="<?= App\Gioco\Sessione::LUNGHEZZA_MINIMA ?>"
           autocomplete="new-password"></label>
  <button type="submit">Registrati</button>
</form>
<?php endif; ?>

<p class="tenue">Non sai di che si tratta?
   <a href="<?= u('/guida') ?>">C'è una guida</a>.</p>
