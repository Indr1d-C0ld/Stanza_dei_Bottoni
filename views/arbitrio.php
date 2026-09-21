<?php
/** @var array<string,mixed> $conteggi $salute */
/** @var list<array<string,mixed>> $arrivi $atti $conversazioni */
/** @var array<string,mixed> $leve */
/** @var App\Nucleo\Calibrazione $cal */
$ruoli = App\Dati\Gabinetto::RUOLI;
$adesso = time();
?>
<h1>Il banco dell'arbitro</h1>
<p class="tenue">Tre mestieri diversi: chi arriva, chi c'è, e come gira il mondo.
   Ogni atto finisce nel registro in fondo alla pagina — in un gioco dove
   l'arbitro può cambiare le regole a partita in corso, un atto non tracciato è
   indistinguibile da un favore.</p>

<section>
  <h2>A colpo d'occhio</h2>
  <table class="tabella">
    <tr>
      <td>in attesa di conferma</td><td><strong><?= (int) $conteggi['in_attesa'] ?></strong></td>
      <td>attivi</td><td><strong><?= (int) $conteggi['attivi'] ?></strong></td>
      <td>sospesi</td><td><strong><?= (int) $conteggi['sospesi'] ?></strong></td>
      <td>chiusi</td><td><strong><?= (int) $conteggi['chiusi'] ?></strong></td>
    </tr>
    <tr>
      <td>tick</td><td><strong><?= (int) $salute['tick'] ?></strong></td>
      <td>posta in coda</td><td><strong><?= (int) $salute['posta']['in_coda'] ?></strong></td>
      <td>inviate 24h</td>
      <td><strong><?= (int) $salute['posta']['inviate_24h'] ?></strong>/<?= (int) $salute['posta']['tetto'] ?></td>
      <td>rinunciate</td><td><strong><?= (int) $salute['posta']['rinunciate'] ?></strong></td>
    </tr>
    <tr>
      <td>crisi aperte</td><td><strong><?= (int) $salute['crisi_aperte'] ?></strong></td>
      <td>guerre</td><td><strong><?= (int) $salute['guerre'] ?></strong></td>
      <td>rubinetti chiusi</td><td><strong><?= (int) $salute['strozzature'] ?></strong></td>
      <td>messaggi</td><td><strong><?= (int) $salute['messaggi'] ?></strong></td>
    </tr>
    <tr>
      <td>ultimo salvataggio</td>
      <td colspan="3" class="<?= $salute['salvataggio']['ore'] === null
              || $salute['salvataggio']['ore'] > 36 ? 'negativo' : 'positivo' ?>">
        <strong><?= $salute['salvataggio']['quando'] !== null
            ? htmlspecialchars((string) $salute['salvataggio']['quando'])
              . ' (' . (int) $salute['salvataggio']['ore'] . ' ore fa)'
            : 'MAI — il mondo non è salvato da nessuna parte' ?></strong></td>
      <td>copie</td><td><strong><?= (int) $salute['salvataggio']['quanti'] ?></strong></td>
      <td colspan="2" class="tenue"><?= htmlspecialchars((string) $salute['salvataggio']['peso']) ?></td>
    </tr>
  </table>
</section>

<section>
  <h2>Chi c'è, e chi bussa</h2>
  <table class="tabella">
    <tr><th>chi</th><th>stato</th><th>poltrona</th><th>atti</th></tr>
    <?php foreach ($arrivi as $g): ?>
      <?php
        $sospeso = $g['sospeso_fino'] !== null && strtotime((string) $g['sospeso_fino']) > $adesso;
        $stato = match (true) {
            (int) $g['attivo'] === 0        => 'chiuso',
            $sospeso                        => 'sospeso fino al ' . App\Nucleo\Calendario::dataOra((string) $g['sospeso_fino']),
            (int) $g['email_verificata'] === 0 => 'non ha confermato',
            default                         => 'attivo',
        };
      ?>
      <tr>
        <td>
          <strong><?= htmlspecialchars((string) $g['nome']) ?></strong>
          <?= (string) $g['ruolo'] === 'arbitro' ? '<span class="tenue">· arbitro</span>' : '' ?><br>
          <span class="tenue"><?= htmlspecialchars((string) $g['email']) ?></span><br>
          <span class="tenue">dal <?= App\Nucleo\Calendario::data((string) $g['creato']) ?>
            <?= $g['ip_registrazione'] ? ' · ' . htmlspecialchars((string) $g['ip_registrazione']) : '' ?></span>
          <?php if ((string) $g['nota_arbitro'] !== ''): ?>
            <br><span class="tenue">nota: <?= htmlspecialchars((string) $g['nota_arbitro']) ?></span>
          <?php endif; ?>
        </td>
        <td class="<?= $stato === 'attivo' ? 'positivo' : 'negativo' ?>"><?= htmlspecialchars($stato) ?></td>
        <td class="tenue">
          <?= $g['poltrona_id'] !== null
              ? htmlspecialchars(($ruoli[$g['poltrona_ruolo']] ?? '') . ' di ' . $g['nazione'])
              : '—' ?>
        </td>
        <td>
          <?php
            $atti = [];
            if ((int) $g['email_verificata'] === 0) {
                $atti['verifica'] = 'dai per buono';
                $atti['rimanda']  = 'riscrivigli';
            }
            $atti[$sospeso ? 'riattiva' : 'sospendi'] = $sospeso ? 'togli sospensione' : 'sospendi 7 giorni';
            $atti[(int) $g['attivo'] === 1 ? 'chiudi' : 'riapri'] = (int) $g['attivo'] === 1 ? 'chiudi' : 'riapri';
            if ($g['poltrona_id'] !== null) {
                $atti['libera'] = 'libera la poltrona';
            }
            $atti[(string) $g['ruolo'] === 'arbitro' ? 'degrada' : 'promuovi'] =
                (string) $g['ruolo'] === 'arbitro' ? 'togli il banco' : 'fallo arbitro';
          ?>
          <?php foreach ($atti as $a => $et): ?>
            <form method="post" action="" class="in-linea">
              <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
              <input type="hidden" name="azione" value="arbitrio_account">
              <input type="hidden" name="giocatore" value="<?= (int) $g['id'] ?>">
              <input type="hidden" name="atto" value="<?= htmlspecialchars($a) ?>">
              <?php if ($a === 'sospendi'): ?><input type="hidden" name="argomento" value="7"><?php endif; ?>
              <button type="submit" class="minuto"><?= htmlspecialchars($et) ?></button>
            </form>
          <?php endforeach; ?>
          <form method="post" action="" class="in-linea">
            <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
            <input type="hidden" name="azione" value="arbitrio_account">
            <input type="hidden" name="giocatore" value="<?= (int) $g['id'] ?>">
            <input type="hidden" name="atto" value="email">
            <input type="email" name="argomento" placeholder="nuovo indirizzo" size="18">
            <button type="submit" class="minuto">cambia</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>

<section>
  <h2>Chi può entrare</h2>
  <p class="tenue">Adesso le registrazioni sono
     <strong><?= htmlspecialchars($modoInviti) ?></strong> —
     <?= htmlspecialchars(App\Gioco\Inviti::MODI[$modoInviti] ?? '') ?></p>
  <p class="tenue">Il modo si cambia dalle leve, con la chiave
     <code>gioco.registrazioni</code>. Qui si fanno i codici: ognuno vale una
     volta sola e dura <?= App\Gioco\Inviti::DURATA_GIORNI ?> giorni.</p>

  <p>Codici liberi: <strong><?= (int) $contoInviti['liberi'] ?></strong> ·
     usati: <?= (int) $contoInviti['usati'] ?> ·
     scaduti: <?= (int) $contoInviti['scaduti'] ?></p>

  <form method="post" action="" class="modulo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="invito_crea">
    <label>Per chi <span class="tenue">(una nota per ricordartelo)</span>
      <input type="text" name="nota" maxlength="120" placeholder="Marco, quello del forum"></label>
    <label>Quanti
      <input type="number" name="quanti" value="1" min="1" max="20" style="width:5rem"></label>
    <button type="submit">Fai i codici</button>
  </form>

  <table class="tabella">
    <?php foreach ($inviti as $i): ?>
      <?php
        $scaduto = $i['scade_il'] !== null && strtotime((string) $i['scade_il']) < time();
        $stato = $i['usato_da'] !== null
            ? 'usato da ' . $i['usato_dal']
            : ($scaduto ? 'scaduto' : 'libero');
      ?>
      <tr>
        <td><code><?= htmlspecialchars((string) $i['codice']) ?></code></td>
        <td class="<?= $i['usato_da'] !== null ? 'tenue' : ($scaduto ? 'negativo' : 'positivo') ?>">
          <?= htmlspecialchars($stato) ?></td>
        <td class="tenue"><?= htmlspecialchars((string) $i['nota']) ?></td>
        <td class="tenue">fatto il <?= App\Nucleo\Calendario::data((string) $i['creato_il']) ?>
          <?= $i['scade_il'] !== null
              ? ', scade il ' . App\Nucleo\Calendario::data((string) $i['scade_il']) : '' ?></td>
        <td>
          <?php if ($i['usato_da'] === null): ?>
          <form method="post" action="" class="in-linea">
            <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
            <input type="hidden" name="azione" value="invito_revoca">
            <input type="hidden" name="codice" value="<?= htmlspecialchars((string) $i['codice']) ?>">
            <button type="submit" class="minuto">revoca</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>

<section>
  <h2>Le leve del mondo</h2>
  <p class="tenue">I file in <code>calibrazione/</code> restano la verità di
     partenza e stanno in git. Qui si dichiarano gli scostamenti, uno per uno,
     e si possono rimettere a posto svuotando il campo. Un salto di oltre dieci
     volte viene rifiutato: se è voluto, ci si arriva in due passi — così non ci
     si arriva per uno zero di troppo. <strong>Vale dal tick successivo.</strong></p>

  <?php foreach (App\Gioco\Arbitrio::LEVE as $gruppo => $voci): ?>
    <h3><?= htmlspecialchars($gruppo) ?></h3>
    <table class="tabella prosa">
      <?php foreach ($voci as $chiave => $spiega): ?>
        <?php
          $mossa   = $leve[$chiave] ?? null;
          $attuale = $cal->leggi($chiave, null);
        ?>
        <tr>
          <td>
            <code><?= htmlspecialchars($chiave) ?></code><br>
            <span class="tenue"><?= htmlspecialchars($spiega) ?></span>
            <?php if ($mossa !== null): ?>
              <br><span class="negativo">mossa: era <?= htmlspecialchars(var_export($mossa['prima'], true)) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <form method="post" action="" class="in-linea">
              <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
              <input type="hidden" name="azione" value="arbitrio_leva">
              <input type="hidden" name="chiave" value="<?= htmlspecialchars($chiave) ?>">
              <input type="text" name="valore" size="10"
                     value="<?= htmlspecialchars((string) (is_scalar($attuale) ? $attuale : '')) ?>">
              <button type="submit" class="minuto">muovi</button>
            </form>
            <?php if ($mossa !== null): ?>
              <form method="post" action="" class="in-linea">
                <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
                <input type="hidden" name="azione" value="arbitrio_leva">
                <input type="hidden" name="chiave" value="<?= htmlspecialchars($chiave) ?>">
                <input type="hidden" name="valore" value="">
                <button type="submit" class="minuto">rimetti</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endforeach; ?>
</section>

<section>
  <h2>Le conversazioni</h2>
  <p class="tenue">In chiaro, perché il database le tiene in chiaro — e il
     documento sul Canale lo dice dal primo giorno: la sicurezza qui è una
     proprietà del canale, non della cifratura a riposo, e qualcuno deve poter
     intervenire. Chi gioca lo sa.</p>
  <?php foreach (array_slice($conversazioni, 0, 15) as $m): ?>
    <article class="messaggio">
      <p class="tenue"><?= App\Nucleo\Calendario::tick((int) $m['tick']) ?> ·
        <?= htmlspecialchars((string) ($m['da_giocatore'] ?? 'apparato')) ?>
        (<?= htmlspecialchars((string) $m['da_paese']) ?>) →
        <?= htmlspecialchars((string) ($m['a_giocatore'] ?? 'apparato')) ?>
        (<?= htmlspecialchars((string) $m['a_paese']) ?>)
        <?= (int) $m['soppresso'] === 1 ? ' · <strong>soppresso</strong>' : '' ?>
        <?= $m['testo_originale'] !== null ? ' · <strong>manomesso</strong>' : '' ?></p>
      <p><?= nl2br(htmlspecialchars(mb_substr((string) $m['testo'], 0, 400))) ?></p>
      <?php if ($m['testo_originale'] !== null): ?>
        <p class="tenue">originale: <?= nl2br(htmlspecialchars(mb_substr((string) $m['testo_originale'], 0, 400))) ?></p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>

<section>
  <h2>Il registro degli atti</h2>
  <?php if ($atti === []): ?><p class="tenue">Nessun atto, per ora.</p><?php endif; ?>
  <table class="tabella prosa">
    <?php foreach ($atti as $a): ?>
      <tr>
        <td class="tenue"><?= App\Nucleo\Calendario::dataOra((string) $a['quando']) ?></td>
        <td><?= htmlspecialchars((string) $a['arbitro']) ?></td>
        <td><?= htmlspecialchars((string) $a['genere']) ?></td>
        <td><code><?= htmlspecialchars((string) $a['bersaglio']) ?></code></td>
        <td class="tenue"><?= htmlspecialchars((string) $a['dettaglio']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>
