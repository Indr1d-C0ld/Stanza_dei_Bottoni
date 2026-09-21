<?php
/** @var array<string,mixed> $poltrona */
/** @var list<array<string,mixed>> $ricevuti $inviati $intercettati */
/** @var array<string,list<array<string,mixed>>> $rubrica */
$liv = App\Gioco\Canale::LIVELLI;
$r = fn(string $x): string => App\Gioco\Canale::etichettaRuolo($x);
?>
<h1>Il Canale</h1>
<p class="tenue">La sicurezza è una proprietà del canale, non del testo. Quattro
gradini: più sali, meno gente legge — e più costa. Il corriere è l'unico che i
segnali non toccano, ma ci mette due giri d'orologio.</p>
<p class="tenue">Niente arriva nell'istante in cui parte, tranne una dichiarazione
pubblica. In quel transito il messaggio è fuori dalle mani di entrambi — ed è lì
che un servizio straniero, se ha già ordinato l'operazione e riesce a leggerlo
per intero, può riscriverlo prima che arrivi.</p>

<section>
  <h2>Scrivere</h2>
  <form method="post" action="" class="modulo largo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="scrivi">
    <label>A chi
      <select name="destinatario" required>
        <?php foreach ($rubrica as $nazione => $poltrone): ?>
          <optgroup label="<?= htmlspecialchars((string) $nazione) ?>">
          <?php foreach ($poltrone as $p): ?>
            <option value="<?= (int) $p['id'] ?>">
              <?= htmlspecialchars($r((string) $p['ruolo'])) ?>
              — <?= htmlspecialchars((string) ($p['giocatore'] ?? $p['titolare'])) ?>
              <?= $p['giocatore'] === null ? '(apparato)' : '' ?>
            </option>
          <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Su che canale
      <select name="sicurezza">
        <?php foreach ($liv as $n => $d): ?>
          <?php if ($n !== 5 || $linee_aperte !== []): ?>
          <option value="<?= $n ?>"<?= $n === 2 ? ' selected' : '' ?>>
            <?= htmlspecialchars($d['nome']) ?> — <?= htmlspecialchars($d['nota']) ?>
          </option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Testo<textarea name="testo" rows="4" required maxlength="4000"></textarea></label>
    <button type="submit">Spedisci</button>
  </form>
</section>

<div class="quadranti">
<section class="quadrante">
  <h3>Ricevuti</h3>
  <?php if ($ricevuti === []): ?><p class="tenue">Nulla.</p><?php endif; ?>
  <?php foreach ($ricevuti as $m): ?>
    <article class="messaggio">
      <p class="tenue"><?= App\Nucleo\Calendario::tick((int) $m['tick']) ?> ·
        <?= htmlspecialchars($r((string) $m['ruolo_mittente'])) ?> di
        <?= htmlspecialchars((string) $m['nazione_mittente']) ?>
        · canale <?= htmlspecialchars($liv[(int) $m['sicurezza']]['nome']) ?></p>
      <?php if ((int) ($m['manomissione_sospetta'] ?? 0) === 1): ?>
        <p class="allarme">I nostri tecnici dicono che questo testo mostra segni
           di manomissione. Non sanno di chi, né quanto: sanno solo che è stato
           toccato. Trattalo di conseguenza.</p>
      <?php endif; ?>
      <p><?= nl2br(htmlspecialchars((string) $m['testo'])) ?></p>
    </article>
  <?php endforeach; ?>
</section>

<section class="quadrante">
  <h3>Inviati</h3>
  <?php if ($inviati === []): ?><p class="tenue">Nulla.</p><?php endif; ?>
  <?php foreach ($inviati as $m): ?>
    <article class="messaggio">
      <p class="tenue"><?= App\Nucleo\Calendario::tick((int) $m['tick']) ?> · a
        <?= htmlspecialchars($r((string) $m['ruolo_destinatario'])) ?> di
        <?= htmlspecialchars((string) $m['nazione_destinataria']) ?>
        · <?= htmlspecialchars($liv[(int) $m['sicurezza']]['nome']) ?>
        <?php if ((int) ($m['in_transito'] ?? 0) === 1): ?>
          · <strong>ancora in viaggio</strong>
        <?php endif; ?></p>
      <p><?= nl2br(htmlspecialchars((string) $m['testo'])) ?></p>
    </article>
  <?php endforeach; ?>
</section>
</div>

<?php if ($intercettati !== null): ?>
<section>
  <h2>Quel che abbiamo letto</h2>
  <p class="tenue">Prodotto dei nostri servizi. Lo vedi perché occupi
     l'Intelligence o la Sicurezza interna: non è roba da tutto il gabinetto.</p>
  <?php if ($intercettati === []): ?><p class="tenue">Niente, per ora.</p><?php endif; ?>
  <?php foreach ($intercettati as $i): ?>
    <article class="messaggio intercetto">
      <p class="tenue"><?= App\Nucleo\Calendario::tick((int) $i['tick']) ?> ·
        <?= htmlspecialchars($r((string) $i['ruolo_mittente'])) ?> di <?= htmlspecialchars((string) $i['nazione_mittente']) ?>
        → <?= htmlspecialchars($r((string) $i['ruolo_destinatario'])) ?> di <?= htmlspecialchars((string) $i['nazione_destinataria']) ?>
        · canale <?= htmlspecialchars($liv[(int) $i['sicurezza']]['nome']) ?>
        · <strong><?= htmlspecialchars((string) $i['livello']) ?></strong></p>
      <?php if ($i['livello'] === 'integrale'): ?>
        <p><?= nl2br(htmlspecialchars((string) $i['testo'])) ?></p>
      <?php elseif ($i['livello'] === 'parziale'): ?>
        <p class="frammento"><?= htmlspecialchars(mb_substr((string) $i['testo'], 0, 60)) ?>…
           <span class="tenue">[il resto non è stato ricostruito]</span></p>
      <?php else: ?>
        <p class="tenue">Solo i metadati: chi ha parlato con chi, e quando. Spesso basta.</p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<section>
  <h2>Le linee dirette</h2>
  <p class="tenue">Un canale dedicato con una potenza: arriva in un giro e nessun
     servizio straniero lo ascolta. Si apre in due — finché l'altra parte non
     accetta, non esiste — ed è <strong>pubblica</strong>: il mondo vede quali
     capitali si parlano così, e ne trae le sue conclusioni.</p>
  <p class="tenue">Ma è al sicuro dai segnali, non dalle persone: chi ha
     reclutato qualcuno in uno dei due palazzi legge tutto quello che ci passa,
     e non deve decifrare niente perché glielo consegnano.</p>

  <?php if (in_array($poltrona['ruolo'], App\Gioco\Linea::RUOLI_AMMESSI, true)): ?>
  <form method="post" action="" class="modulo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="proponi_linea">
    <label>Proporre una linea a
      <select name="controparte" required>
        <option value="">—</option>
        <?php foreach ($rubrica as $nome => $poltrone): ?>
          <?php $primo = $poltrone[0] ?? null; ?>
          <?php if ($primo !== null && (int) $primo['nazione_id'] !== (int) $poltrona['nazione_id']): ?>
            <option value="<?= (int) ($primo['nazione_id'] ?? 0) ?>"><?= htmlspecialchars((string) $nome) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Proporre</button>
  </form>
  <?php endif; ?>

  <h3>Le nostre</h3>
  <?php if ($linee === []): ?><p class="tenue">Nessuna, né aperta né proposta.</p><?php endif; ?>
  <table class="tabella">
    <?php foreach ($linee as $l): ?>
      <?php
        $nostra = (int) $poltrona['nazione_id'];
        $altro = (int) $l['a_nazione_id'] === $nostra ? $l['nome_b'] : $l['nome_a'];
        $laNostraProposta = (int) $l['proponente_id'] === $nostra;
      ?>
      <tr>
        <td><strong><?= htmlspecialchars((string) $altro) ?></strong></td>
        <td><?= htmlspecialchars((string) $l['stato']) ?>
            <?php if ((string) $l['stato'] === 'proposta'): ?>
              <span class="tenue">(<?= $laNostraProposta ? 'nostra, aspettiamo loro' : 'loro, aspettano noi' ?>)</span>
            <?php endif; ?></td>
        <td>
          <?php if ((string) $l['stato'] === 'proposta' && !$laNostraProposta
                    && in_array($poltrona['ruolo'], App\Gioco\Linea::RUOLI_AMMESSI, true)): ?>
            <?php foreach (['si' => 'accettare', 'no' => 'rifiutare'] as $v => $et): ?>
            <form method="post" action="" class="in-linea">
              <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
              <input type="hidden" name="azione" value="rispondi_linea">
              <input type="hidden" name="linea" value="<?= (int) $l['id'] ?>">
              <input type="hidden" name="risposta" value="<?= $v ?>">
              <button type="submit" class="minuto"><?= $et ?></button>
            </form>
            <?php endforeach; ?>
          <?php elseif ((string) $l['stato'] === 'attiva'
                    && in_array($poltrona['ruolo'], App\Gioco\Linea::RUOLI_AMMESSI, true)): ?>
            <form method="post" action="" class="in-linea">
              <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
              <input type="hidden" name="azione" value="chiudi_linea">
              <input type="hidden" name="linea" value="<?= (int) $l['id'] ?>">
              <button type="submit" class="minuto">chiudere</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h3>Quelle del mondo</h3>
  <?php if ($linee_mondo === []): ?><p class="tenue">Nessuna capitale ne ha ancora aperta una.</p><?php endif; ?>
  <ul class="elenco-piano">
    <?php foreach ($linee_mondo as $l): ?>
      <li><?= htmlspecialchars((string) $l['nome_a']) ?> &harr; <?= htmlspecialchars((string) $l['nome_b']) ?>
          <span class="tenue">dal <?= App\Nucleo\Calendario::tick((int) $l['attivata_tick']) ?></span></li>
    <?php endforeach; ?>
  </ul>
</section>

<?php if ($manipolazioni !== null): ?>
<section>
  <h2>Riscrivere le parole degli altri</h2>
  <p class="tenue">Leggere è una cosa, scrivere un'altra. Una squadra appostata
     agisce sul primo messaggio che riusciamo a leggere <em>per intero</em> — i
     frammenti non bastano, non si riscrive quel che non si è capito — e poi si
     ritira. Serve avere già qualcosa piantato dentro il paese bersaglio.</p>
  <p class="tenue">Ricorda la debolezza di ogni falso: <strong>chi ha scritto sa
     cosa ha scritto.</strong> Basta che le due parti si parlino altrove perché
     venga fuori, e allora il danno torna indietro moltiplicato.</p>

  <form method="post" action="" class="modulo largo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="manometti">
    <label>Di chi riscriviamo le parole
      <select name="bersaglio" required>
        <option value="">—</option>
        <?php foreach ($paesi as $p): ?>
          <?php if ((int) $p['id'] !== (int) $poltrona['nazione_id']): ?>
            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars((string) $p['nome']) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Solo quelle dirette a (facoltativo — altrimenti chiunque)
      <select name="verso">
        <option value="">chiunque</option>
        <?php foreach ($paesi as $p): ?>
          <?php if ((int) $p['id'] !== (int) $poltrona['nazione_id']): ?>
            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars((string) $p['nome']) ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Che cosa facciamo
      <select name="modo">
        <?php foreach (App\Gioco\Falsificazione::MODI as $k => $d): ?>
          <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($d) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Il testo, preparato adesso
      <textarea name="testo" rows="3" maxlength="2000"
        placeholder="La frase da infilare, o il testo intero da sostituire. Per la soppressione lascia vuoto."></textarea></label>
    <button type="submit">Appostare la squadra</button>
  </form>

  <h3>Le nostre operazioni</h3>
  <?php if ($manipolazioni === []): ?><p class="tenue">Nessuna.</p><?php endif; ?>
  <table class="tabella">
    <?php foreach ($manipolazioni as $o): ?>
      <tr>
        <td><?= htmlspecialchars((string) $o['bersaglio']) ?>
            <?= $o['verso'] !== null ? '&rarr; ' . htmlspecialchars((string) $o['verso']) : '<span class="tenue">&rarr; chiunque</span>' ?></td>
        <td><?= htmlspecialchars((string) $o['modo']) ?></td>
        <td><?= htmlspecialchars((string) $o['stato']) ?></td>
        <td class="tenue">aperta il <?= App\Nucleo\Calendario::tick((int) $o['aperta_tick']) ?>, scade il <?= App\Nucleo\Calendario::tick((int) $o['scade_tick']) ?></td>
        <td>
          <?php if ((string) $o['stato'] === 'attiva'): ?>
          <form method="post" action="" class="in-linea">
            <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
            <input type="hidden" name="azione" value="revoca_manomissione">
            <input type="hidden" name="operazione" value="<?= (int) $o['id'] ?>">
            <button type="submit" class="minuto">richiama</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php endif; ?>
