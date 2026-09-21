<h1>Parola d'ordine dimenticata</h1>
<p class="tenue">Scrivi il tuo indirizzo o il tuo nome in gioco: ti mandiamo un
   collegamento per sceglierne una nuova. Vale un'ora.</p>
<p class="tenue">La risposta è la stessa in ogni caso, anche se l'indirizzo non
   corrisponde a nessuno: dire il contrario sarebbe un modo comodo per scoprire
   chi gioca.</p>
<form method="post" action="" class="modulo">
  <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
  <input type="hidden" name="azione" value="dimenticata">
  <label>Indirizzo di posta o nome in gioco
    <input type="text" name="chi" required autocomplete="username"></label>
  <button type="submit">Mandamelo</button>
</form>
<p class="tenue"><a href="<?= u('/entra') ?>">Torna all'accesso</a></p>
