<h1>Entra</h1>
<form method="post" action="" class="modulo">
  <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
  <input type="hidden" name="azione" value="entra">
  <label>Indirizzo di posta o nome in gioco
    <input type="text" name="email" required autocomplete="username"></label>
  <label>Parola d'ordine<input type="password" name="password" required autocomplete="current-password"></label>
  <button type="submit">Entra</button>
</form>
<p class="tenue">Non hai ancora un accesso? <a href="<?= u('/registrati') ?>">Registrati</a>.
   Dimenticata la parola d'ordine? <a href="<?= u('/dimenticata') ?>">Te ne mandiamo un'altra</a>.</p>

<section>
  <h3>Non ti è arrivata la conferma?</h3>
  <p class="tenue">Senza indirizzo confermato l'accesso non si apre. Se il
     messaggio non è arrivato — o il collegamento è scaduto — chiedine un altro.</p>
  <form method="post" action="" class="modulo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="rimanda_verifica">
    <label>Il tuo indirizzo
      <input type="email" name="email" required maxlength="190"></label>
    <button type="submit">Rimandamelo</button>
  </form>
</section>
