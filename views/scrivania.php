<?php defined('BASE') || exit; // si include da index.php, non si apre dal browser ?>
<?php
/** @var array<string,mixed> $poltrona $nazione */
/** @var list<array<string,mixed>> $colleghi $daFirmare $mieiOrdini $paesi $relazioni $registro $quadro */
/** @var array<string,array<string,mixed>> $verbi */
$ruoli = App\Dati\Gabinetto::RUOLI;
$domini = ['soc'=>'diplomazia','eco'=>'economia','info'=>'informazione',
           'int'=>'operazioni coperte','mil'=>'militare','nuc'=>'nucleare'];
$tensioni = [1=>'quiete',2=>'pace',3=>'tensione',4=>'conflitto aperto',5=>'guerra',6=>'guerra totale'];
?>
<h1><?= htmlspecialchars($ruoli[$poltrona['ruolo']] ?? '') ?>
    <span class="tenue">di</span> <?= htmlspecialchars((string) $poltrona['nazione']) ?></h1>
<p class="tenue">Al tuo posto sedeva <?= htmlspecialchars((string) $poltrona['nome']) ?>.
   Legittimità del governo <?= n((float) $nazione['legittimita'], 0) ?>/100 ·
   <?= $tensioni[(int) $nazione['net_peace']] ?? '' ?> ·
   influenza <?= n((float) $nazione['influenza_totale'], 2) ?>%</p>

<?php if ($poltrona['per_delega'] ?? false): ?>
<p class="allarme">Stai sedendo alla poltrona di
   <?= htmlspecialchars((string) $poltrona['titolare_vero'] ?? '') ?>, che te l'ha affidata.
   Quel che firmi qui resta agli atti come tuo.</p>
<?php endif; ?>

<?php if ($mentreNonCEri !== []): ?>
<section class="cartella">
  <h2>Mentre non c'eri</h2>
  <p class="tenue">Il mondo non si è fermato ad aspettarti. Questo è quel che è
     stato deciso al posto tuo — lo si dice una volta sola.</p>
  <ul class="elenco-piano">
    <?php foreach ($mentreNonCEri as $f): ?>
      <li><?= App\Nucleo\Calendario::tick((int) $f['tick']) ?> · <strong><?= htmlspecialchars((string) $f['cosa']) ?></strong>
          <?php if ((string) $f['dettaglio'] !== ''): ?>
            — <?= htmlspecialchars((string) $f['dettaglio']) ?>
          <?php endif; ?>
          <span class="tenue">(<?= (string) $f['chi'] === 'apparato' ? "l'apparato" : 'il delegato' ?>)</span></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php if (!($poltrona['per_delega'] ?? false)): ?>
<section class="cartella">
  <h2>Gli avvisi</h2>
  <p class="tenue">Il mondo gira ogni due ore anche mentre dormi. Se vuoi, ti
     scriviamo quando c'è qualcosa che non può aspettare — una crisi che tocca a
     te, una firma che manca, la tua poltrona che sta per passare all'apparato.
     Al massimo un messaggio ogni <?= (int) $ogniQuanti ?> giri, e solo se c'è
     davvero qualcosa da dire.</p>
  <p>Adesso sono <strong><?= $avvisiAccesi ? 'accesi' : 'spenti' ?></strong>.</p>
  <form method="post" action="" class="in-linea">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="avvisi">
    <input type="hidden" name="acceso" value="<?= $avvisiAccesi ? '0' : '1' ?>">
    <button type="submit"><?= $avvisiAccesi ? 'Spegnili' : 'Accendili' ?></button>
  </form>
</section>
<?php endif; ?>

<?php if ($poltroneAffidate !== [] || !($poltrona['per_delega'] ?? false)): ?>
<section class="cartella">
  <h2>La delega</h2>
  <?php if (!($poltrona['per_delega'] ?? false)): ?>
    <?php if ($poltrona['delega_a'] === null): ?>
      <p class="tenue">Se stai via, la tua poltrona resta vuota: dopo
         <?= App\Gioco\Delega::TICK_PRIMA_DELL_APPARATO ?> giri senza che tu la tocchi,
         l'apparato riprende il paese in mano e decide al posto tuo — crisi comprese.
         Puoi invece affidarla a qualcuno. Agirà in tuo nome, e la firma resterà sua:
         potrà fare quel che avresti fatto tu, e quel che non avresti mai fatto.</p>
      <p class="tenue">Silenzio attuale: <?= $silenzio ?> giri su
         <?= App\Gioco\Delega::TICK_PRIMA_DELL_APPARATO ?>.</p>
      <form method="post" action="" class="modulo">
        <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
        <input type="hidden" name="azione" value="affida">
        <label>Affidare la poltrona a
          <select name="giocatore" required>
            <option value="">—</option>
            <?php foreach ($altriGiocatori as $g): ?>
              <option value="<?= (int) $g['id'] ?>"><?= htmlspecialchars((string) $g['nome']) ?>
                <?= $g['nazione'] !== null ? '(' . htmlspecialchars((string) $g['nazione']) . ')' : '' ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit">Affidare</button>
      </form>
    <?php else: ?>
      <p>La poltrona è affidata dal <?= App\Nucleo\Calendario::tick((int) $poltrona['delega_dal_tick']) ?>.</p>
      <form method="post" action="" class="in-linea">
        <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
        <input type="hidden" name="azione" value="revoca_delega">
        <button type="submit">Riprenderla</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($poltroneAffidate !== [] || ($poltrona['per_delega'] ?? false)): ?>
    <h3>Dove puoi sederti</h3>
    <table class="tabella">
      <tr>
        <td>la tua</td>
        <td><form method="post" action="" class="in-linea">
          <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
          <input type="hidden" name="azione" value="siedi">
          <input type="hidden" name="poltrona" value="0">
          <button type="submit" class="minuto">sedersi</button>
        </form></td>
      </tr>
      <?php foreach ($poltroneAffidate as $a): ?>
      <tr>
        <td><?= htmlspecialchars($ruoli[$a['ruolo']] ?? '') ?> di
            <?= htmlspecialchars((string) $a['nazione']) ?>
            <span class="tenue">— da <?= htmlspecialchars((string) $a['titolare_vero']) ?></span></td>
        <td><form method="post" action="" class="in-linea">
          <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
          <input type="hidden" name="azione" value="siedi">
          <input type="hidden" name="poltrona" value="<?= (int) $a['id'] ?>">
          <button type="submit" class="minuto">sedersi</button>
        </form></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- ─────────────────────────────  LA CARTELLA  ───────────────────────────── -->
<section class="cartella">
  <h2>La Cartella</h2>
  <?php if ($daFirmare === [] && $mieiOrdini === []): ?>
    <p class="tenue">Niente che richieda la tua firma. Puoi impartire un ordine qui sotto.</p>
  <?php endif; ?>

  <?php foreach ($daFirmare as $o): ?>
    <article class="voce-cartella">
      <p class="riga-uno"><strong><?= htmlspecialchars((string) $o['proponente']) ?></strong>
         (<?= htmlspecialchars($ruoli[$o['ruolo_proponente']] ?? '') ?>) propone
         <strong><?= htmlspecialchars(str_replace('_', ' ', (string) $o['verbo'])) ?></strong>
         contro <?= htmlspecialchars((string) $o['bersaglio']) ?>,
         intensità <?= (int) $o['intensita'] ?>%.</p>
      <p class="riga-due">Ti si chiede di controfirmare, o di non farlo.</p>
      <p class="riga-tre tenue">Se non fai nulla, l'ordine scade il
         <?= App\Nucleo\Calendario::tick((int) $o['scade_tick']) ?> e non parte.</p>
      <form method="post" action="" class="in-linea">
        <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
        <input type="hidden" name="azione" value="firma">
        <input type="hidden" name="ordine" value="<?= (int) $o['id'] ?>">
        <button type="submit">Controfirmo</button>
      </form>
    </article>
  <?php endforeach; ?>

  <?php foreach ($mieiOrdini as $o): ?>
    <article class="voce-cartella <?= $o['stato'] === 'in_attesa' ? 'in-sospeso' : '' ?>">
      <p class="riga-uno"><strong><?= htmlspecialchars(str_replace('_', ' ', (string) $o['verbo'])) ?></strong>
         contro <?= htmlspecialchars((string) $o['bersaglio']) ?>, intensità <?= (int) $o['intensita'] ?>%.</p>
      <p class="riga-due"><?= $o['stato'] === 'firmato'
          ? 'Firmato: parte al prossimo giro d\'orologio.'
          : ((int) ($o['firmatario_presente'] ?? 0) > 0
              ? 'Aspetta la firma di ' . htmlspecialchars($ruoli[$o['richiede_controfirma']] ?? '?') . '.'
              : 'La firma spetta ' . htmlspecialchars(App\Dati\Gabinetto::A_CHI[$o['richiede_controfirma']] ?? '?')
                . ', che non è una persona al tavolo: al prossimo giro decide l\'apparato. '
                . 'Firma più volentieri quel che è coerente con la linea del governo.') ?></p>
      <p class="riga-tre tenue">Se nessuno firma, decade il <?= App\Nucleo\Calendario::tick((int) $o['scade_tick']) ?>.</p>
      <form method="post" action="" class="in-linea">
        <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
        <input type="hidden" name="azione" value="annulla">
        <input type="hidden" name="ordine" value="<?= (int) $o['id'] ?>">
        <button type="submit" class="pericolo">Ritira</button>
      </form>
    </article>
  <?php endforeach; ?>
</section>

<!-- ─────────────────────  IL REGISTRO DELLE OPERAZIONI  ───────────────────── -->
<?php if (!empty($registro)): ?>
<section class="cartella registro">
  <h2>Il registro delle operazioni</h2>
  <p class="tenue">Gli ordini di questa poltrona e il loro destino, per quel che se ne può sapere:
     se sono partiti, se sono arrivati a segno, e se il mondo li ha attribuiti a qualcuno.
     Chi li ha visti passare non si sa: è affare dei servizi altrui.</p>
  <?php foreach ($registro as $o):
    $verbo = htmlspecialchars(str_replace('_', ' ', (string) $o['verbo']));
    $firma = (int) $o['firmato_da_apparato'] === 1 ? 'dall\'apparato' : ($o['firmatario'] !== null
        ? 'da ' . htmlspecialchars((string) $o['firmatario']) : '');
    [$classe, $destino] = match (true) {
        $o['stato'] === 'in_attesa' => ['in-sospeso', 'Aspetta la seconda firma ('
            . htmlspecialchars($ruoli[$o['richiede_controfirma']] ?? '?') . ').'],
        $o['stato'] === 'firmato'   => ['', 'Firmato' . ($firma !== '' ? " $firma" : '') . ': parte al prossimo giro.'],
        $o['stato'] === 'scaduto'   => ['tenue', 'Nessuno l\'ha firmato in tempo: non è partito.'],
        $o['stato'] === 'annullato' && (int) $o['firmato_da_apparato'] === 1
                                    => ['ostile', 'L\'apparato ha negato la seconda firma: non è partito.'],
        $o['stato'] === 'annullato' => ['tenue', 'Ritirato.'],
        $o['esito'] === 'in_volo'   => ['in-sospeso', 'Partito' . ($firma !== '' ? " (firmato $firma)" : '')
            . '. In corso: arriva a destinazione verso il '
            . App\Nucleo\Calendario::tick((int) $o['maturazione_tick']) . '.'],
        $o['esito'] === 'realizzato' => ['', 'Arrivato a segno il ' . App\Nucleo\Calendario::tick((int) ($o['chiuso_tick'] ?? $o['maturazione_tick'])) . '.'
            . (($effetto = App\Simulazione\Fasi\Fase02Maturazione::raccontaEffetto((string) $o['verbo'],
                (int) ($o['intensita_effettiva'] ?? $o['intensita']))) !== null ? ' ' . htmlspecialchars($effetto) : '')],
        $o['esito'] === 'fermato'    => ['ostile', 'Fermato' . ($o['chiuso_tick'] !== null
            ? ' il ' . App\Nucleo\Calendario::tick((int) $o['chiuso_tick']) : '')
            . ': non è arrivato a segno. Qualcuno l\'ha visto in tempo, o il governo ci ha ripensato.'],
        default => ['tenue', 'Partito; l\'esito non è registrato.'],
    };
  ?>
    <article class="voce-cartella <?= $classe === 'in-sospeso' ? 'in-sospeso' : '' ?>">
      <p class="riga-uno"><strong><?= $verbo ?></strong> contro <?= htmlspecialchars((string) $o['bersaglio']) ?>,
         intensità <?= (int) $o['intensita'] ?>%<?= $o['intensita_effettiva'] !== null && (int) $o['intensita_effettiva'] !== (int) $o['intensita']
             ? ' (eseguita al ' . (int) $o['intensita_effettiva'] . '%: conta la competenza del servizio)' : '' ?><?= (int) $o['copertura'] > 0 ? ', copertura ' . (int) $o['copertura'] . '%' : '' ?>
         <span class="tenue">— ordinato il <?= App\Nucleo\Calendario::tick((int) $o['creato_tick']) ?></span></p>
      <p class="riga-due <?= $classe === 'ostile' ? 'ostile' : ($classe === 'tenue' ? 'tenue' : '') ?>"><?= $destino ?></p>
      <?php foreach ($o['attribuzioni'] as $a): ?>
        <p class="riga-tre <?= $a['accusato'] === $o['nazione'] ? 'ostile' : '' ?>">
          <?= App\Nucleo\Calendario::tick((int) $a['tick']) ?>: <?= htmlspecialchars($a['chi']) ?>
          l'ha attribuito pubblicamente a <strong><?= htmlspecialchars($a['accusato']) ?></strong><?=
          $a['accusato'] === $o['nazione'] ? ' — lo scandalo è nostro.'
          : ' — la colpa è caduta su altri.' ?></p>
      <?php endforeach; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- ───────────────────────  IL QUADRO DEL SERVIZIO  ───────────────────────── -->
<?php if (App\Gioco\Operazioni::vedeIlQuadro((string) $poltrona['ruolo'])): ?>
<section class="cartella quadro-servizio">
  <h2>Il quadro del servizio</h2>
  <p class="tenue">Le operazioni altrui che il nostro servizio ha scoperto, in corso o chiuse di recente.
     Se ne sa per gradi: che qualcosa si muove; di che genere e dove; contro chi; chi l'ha ordinata.
     Il quarto gradino è il più difficile, e il nome che fa può essere quello sbagliato: chi opera
     sa anche far ricadere la colpa su un altro.</p>
  <?php if ($quadro === []): ?>
    <p class="tenue">Per ora il servizio non ha niente da riferire.</p>
  <?php endif; ?>
  <?php foreach ($quadro as $k):
    $liv = (int) $k['livello'];
    $controNoi = (int) $k['bersaglio_id'] === (int) $poltrona['nazione_id'];
    $inVolo = $k['stato'] === 'in_volo';
    $verbo = htmlspecialchars(str_replace('_', ' ', (string) $k['verbo']));
    $dominio = App\Gioco\Operazioni::DOMINI[$k['dominio']] ?? '';
    $regione = App\Gioco\Operazioni::REGIONI[$k['regione']] ?? '';
    $testo = match (true) {
        $liv <= 1 => 'Qualcosa si muove: un\'attività insolita che il servizio non sa ancora leggere.',
        $liv === 2 => ucfirst($dominio) . ": {$verbo}, in " . htmlspecialchars($regione) . '. Contro chi, ancora non si sa.',
        default    => ucfirst($dominio) . ": {$verbo} contro "
                      . ($controNoi ? '<strong>di noi</strong>' : htmlspecialchars((string) $k['bersaglio'])) . '.',
    };
  ?>
    <article class="voce-cartella <?= $inVolo && $controNoi ? 'in-sospeso' : '' ?>">
      <p class="riga-uno"><span class="etichetta-livello">gradino <?= $liv ?> di 4</span> <?= $testo ?></p>
      <?php if ($liv >= 4): ?>
        <p class="riga-due">Il servizio fa il nome di <strong><?= htmlspecialchars((string) $k['accusato']) ?></strong>.</p>
      <?php endif; ?>
      <p class="riga-tre tenue">Segnalata il <?= App\Nucleo\Calendario::tick((int) $k['primo_tick']) ?> ·
        <?php if ($inVolo): ?>in corso<?php
        elseif ($controNoi && $k['stato'] === 'realizzato'): ?>è arrivata a segno<?php
        elseif ($controNoi && $k['stato'] === 'fermato'): ?>non è arrivata a segno<?php
        else: ?>conclusa<?php endif; ?>
        <?php if ($k['scelta'] !== null): ?>
          · la nostra scelta: <strong><?= htmlspecialchars(App\Gioco\Contromosse::SCELTE[$k['scelta']] ?? $k['scelta']) ?></strong>
          <?= $k['contromossa_esito'] !== null ? '— ' . htmlspecialchars((string) $k['contromossa_esito']) : '' ?>
        <?php endif; ?></p>
      <?php $possibili = App\Gioco\Contromosse::possibili($k, (int) $poltrona['nazione_id']);
        if ($possibili !== [] && ($k['contromossa_stato'] ?? '') !== 'conclusa'): ?>
      <form method="post" action="" class="in-linea">
        <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
        <input type="hidden" name="azione" value="contromossa">
        <input type="hidden" name="evento" value="<?= (int) $k['evento'] ?>">
        <?php foreach ($possibili as $s): ?>
          <button type="submit" name="scelta" value="<?= $s ?>"
                  title="<?= htmlspecialchars(App\Gioco\Contromosse::SPIEGAZIONI[$s]) ?>"
                  <?= $k['scelta'] === $s ? 'disabled' : '' ?>><?= htmlspecialchars(App\Gioco\Contromosse::SCELTE[$s]) ?></button>
        <?php endforeach; ?>
      </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- ─────────────────────────────  LE CRISI  ─────────────────────────────── -->
<?php if (!empty($crisiAperte)): ?>
<section class="cartella crisi">
  <h2>Crisi aperte</h2>
  <?php foreach ($crisiAperte as $k):
    $sonoSfidante = (int) $k['sfidante_id'] === (int) $poltrona['nazione_id'];
    $miaParte = $sonoSfidante ? 'sfidante' : 'sfidato';
    $tocca = (string) $k['tocca_a'] === $miaParte;
    $miaPosta = (float) ($sonoSfidante ? $k['posta_sfidante'] : $k['posta_sfidato']);
    $suaPosta = (float) ($sonoSfidante ? $k['posta_sfidato'] : $k['posta_sfidante']);
  ?>
    <article class="voce-cartella <?= $tocca ? 'in-sospeso' : '' ?>">
      <p class="riga-uno">
        <strong><?= htmlspecialchars((string) $k['sfidante']) ?></strong> contesta a
        <strong><?= htmlspecialchars((string) $k['sfidato']) ?></strong>
        <?= $k['verbo'] !== null ? 'un\'operazione di ' . htmlspecialchars(str_replace('_', ' ', (string) $k['verbo'])) : 'un fatto' ?>
        <?= $k['oggetto'] !== null ? 'in ' . htmlspecialchars((string) $k['oggetto']) : '' ?>.
      </p>
      <p class="riga-due">Gradino <?= (int) $k['livello'] ?> di 9:
         <strong><?= htmlspecialchars($gradini[(int) $k['livello']] ?? '') ?></strong>.
         <?= $tocca ? 'Tocca a te.' : 'Stiamo aspettando la loro mossa.' ?></p>
      <p class="riga-tre tenue">Se cedi ora perdi <?= n($miaPosta, 0) ?> punti di faccia;
         se cedono loro ne perdono <?= n($suaPosta, 0) ?>.
         <?php if ((int) $k['livello'] >= 5): ?>
           <span class="ostile">Da qui in su ogni passo può sfuggire di mano.</span>
         <?php endif; ?>
         Se non rispondi entro il <?= App\Nucleo\Calendario::tick((int) $k['scade_tick']) ?>, hai ceduto.</p>
      <?php if ($tocca && !App\Gioco\Crisi::siedeAlTavolo((string) $poltrona['ruolo'])): ?>
      <p class="tenue">La mossa spetta al Capo o agli Esteri; se nessuno dei due è al suo
         posto, decide l'apparato.</p>
      <?php elseif ($tocca): ?>
      <form method="post" action="" class="in-linea">
        <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
        <input type="hidden" name="azione" value="crisi">
        <input type="hidden" name="crisi" value="<?= (int) $k['id'] ?>">
        <button type="submit" name="mossa" value="scala" class="pericolo">Salgo di un gradino</button>
        <button type="submit" name="mossa" value="cede">Cedo</button>
      </form>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (!empty($contestabili) && App\Gioco\Crisi::siedeAlTavolo((string) $poltrona['ruolo'])): ?>
<section>
  <h2>Cosa possiamo contestare</h2>
  <p class="tenue">Solo ciò che siamo riusciti a <strong>dimostrare</strong>. Sapere
     non basta: una contestazione senza prove serve solo a farsi smentire.</p>
  <table>
    <tbody>
    <?php foreach ($contestabili as $e): ?>
      <tr>
        <td><?= htmlspecialchars(str_replace('_', ' ', (string) $e['verbo'])) ?></td>
        <td class="tenue">di <?= htmlspecialchars((string) $e['mandante']) ?>
            <?= $e['bersaglio'] !== null ? 'contro ' . htmlspecialchars((string) $e['bersaglio']) : '' ?></td>
        <td>
          <form method="post" action="" class="in-linea">
            <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
            <input type="hidden" name="azione" value="apri_crisi">
            <input type="hidden" name="evento" value="<?= (int) $e['id'] ?>">
            <button type="submit">Contesta</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php endif; ?>

<!-- ─────────────────────────────  LE TUE AGENDE  ────────────────────────── -->
<?php if (!empty($agende)): ?>
<section class="agende">
  <h2>Quel che vuoi tu</h2>
  <p class="tenue">Nessuno le conosce tranne te. Non coincidono con l'interesse
     del tuo paese, e a volte lo contraddicono: è il punto.</p>
  <?php foreach ($agende as $a): ?>
    <article class="agenda <?= $a['stato'] !== 'aperta' ? 'chiusa' : '' ?>">
      <p><strong><?= htmlspecialchars((string) $a['titolo']) ?></strong>
         <?php if ($a['segreta']): ?><span class="pastiglia">segreta</span><?php endif; ?>
         <?php if ($a['stato'] === 'riuscita'): ?><span class="esito buono">riuscita</span>
         <?php elseif ($a['stato'] === 'fallita'): ?><span class="esito cattivo">fallita</span><?php endif; ?>
      </p>
      <p class="tenue"><?= htmlspecialchars((string) $a['testo']) ?></p>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- ─────────────────────────────  LE OFFERTE  ────────────────────────────── -->
<?php if (!empty($offerte)): ?>
<section class="cartella riservato">
  <h2>Qualcuno ti ha cercato</h2>
  <?php foreach ($offerte as $o): ?>
    <article class="voce-cartella">
      <p class="riga-uno">Un emissario di <strong><?= htmlspecialchars((string) $o['da_paese']) ?></strong>
         ti ha fatto avere un messaggio.</p>
      <p class="citazione">«<?= nl2br(htmlspecialchars((string) $o['testo'])) ?>»</p>
      <p class="riga-due">Sul piatto:
        <?= implode(', ', array_filter([
            (int) $o['offre_denaro']   ? 'denaro' : null,
            (int) $o['offre_dossier']  ? 'un dossier che ti serve' : null,
            (int) $o['offre_appoggio'] ? 'appoggio alla tua causa' : null,
        ])) ?>.</p>
      <p class="riga-tre tenue">Se non rispondi, la proposta decade il <?= App\Nucleo\Calendario::tick((int) $o['scade_tick']) ?>.
         Accettare significa che quel che passa dalla tua scrivania passerà anche da loro.</p>
      <form method="post" action="" class="in-linea">
        <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
        <input type="hidden" name="azione" value="rispondi_offerta">
        <input type="hidden" name="offerta" value="<?= (int) $o['id'] ?>">
        <button type="submit" name="risposta" value="accetta">Accetto</button>
        <button type="submit" name="risposta" value="rifiuta">Rifiuto</button>
        <button type="submit" name="risposta" value="denuncia" class="pericolo">Rendo pubblica</button>
      </form>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($armi !== null && $armi !== []): ?>
<section class="cartella">
  <h2>Le nostre armi commerciali</h2>
  <p class="tenue">Prima di ordinare un embargo, ecco quanto costerebbe — in
     punti di crescita annua, a loro e a noi. Quando la seconda colonna è più
     grande della prima, l'arma è puntata dalla parte sbagliata.
     <a href="<?= u('/commercio') ?>">Il quadro completo</a>.</p>
  <table class="tabella">
    <tr><th>verso</th><th>costa a loro</th><th>costa a noi</th></tr>
    <?php foreach ($armi as $a): ?>
      <tr>
        <td><?= htmlspecialchars($nomiPaesi[$a['iso']] ?? $a['iso']) ?></td>
        <td class="<?= $a['costa_a_loro'] > $a['costa_a_noi'] ? 'positivo' : 'negativo' ?>">
          &minus;<?= n(100 * $a['costa_a_loro'], 2) ?>%</td>
        <td class="<?= $a['costa_a_noi'] >= $a['costa_a_loro'] ? 'negativo' : 'tenue' ?>">
          &minus;<?= n(100 * $a['costa_a_noi'], 2) ?>%</td>
      </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php endif; ?>

<!-- ─────────────────────────────  IMPARTIRE  ─────────────────────────────── -->
<section>
  <h2>Impartire un ordine</h2>
  <p class="tenue">Puoi disporre soltanto nel tuo dominio. Le cose che contano
     richiedono una seconda firma: è così che il gabinetto è un gabinetto e non
     un uomo solo al comando.</p>
  <form method="post" action="" class="modulo largo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="ordina">
    <label>Che cosa
      <select name="verbo" required>
        <?php foreach ($verbi as $v => $d): ?>
          <option value="<?= htmlspecialchars($v) ?>">
            <?= htmlspecialchars(str_replace('_', ' ', $v)) ?>
            — <?= $domini[$d['dominio']] ?? $d['dominio'] ?>
            <?= $d['controfirma'] !== null ? '· serve la firma di ' . ($ruoli[$d['controfirma']] ?? '') : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Contro chi
      <select name="bersaglio" required>
        <?php foreach ($paesi as $p): if ($p['codice'] === $poltrona['codice']) continue; ?>
          <option value="<?= htmlspecialchars((string) $p['codice']) ?>"><?= htmlspecialchars((string) $p['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Con quanta forza <span class="tenue">più alza, più fa rumore</span>
      <input type="range" name="intensita" min="10" max="100" value="50" step="5"></label>
    <label>Quanto occultarla <span class="tenue">vale solo per le operazioni coperte</span>
      <input type="range" name="copertura" min="0" max="90" value="0" step="10"></label>
    <button type="submit">Impartisci</button>
  </form>
</section>

<!-- ─────────────────────────────  RECLUTARE  ─────────────────────────────── -->
<?php if ($nostriUomini !== null): ?>
<section class="riservato">
  <h2>Comprare qualcuno</h2>
  <p class="tenue">Una poltrona che lavora per noi non deve scoprire niente: sta
     dentro. Tutto ciò che il suo governo ha in volo ci arriva con nome e
     cognome del mandante — che è la cosa che nessun'altra disciplina regala.
     In cambio, ogni settimana che passa è una settimana in cui il loro
     controspionaggio può accorgersene.</p>

  <?php if ($nostriUomini !== []): ?>
    <h3>I nostri uomini</h3>
    <ul class="fazioni">
    <?php foreach ($nostriUomini as $u): ?>
      <li><strong><?= htmlspecialchars((string) $u['nome']) ?></strong>
          <span class="tenue"><?= htmlspecialchars(App\Gioco\Canale::etichettaRuolo((string) $u['ruolo'])) ?>
          di <?= htmlspecialchars((string) $u['paese']) ?>, dal <?= App\Nucleo\Calendario::tick((int) $u['reclutata_tick']) ?></span>
          <?php if ((int) $u['sospettata']): ?><span class="esito cattivo">è sotto sospetto</span><?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (!empty($offerteFatte)): ?>
    <h3>Le offerte fatte</h3>
    <ul class="fazioni">
    <?php foreach ($offerteFatte as $o):
      $quando = App\Nucleo\Calendario::tick((int) ($o['risposta_tick'] ?? $o['tick']));
      [$classe, $esito] = match ((string) $o['stato']) {
          'aperta'     => ['', 'sul tavolo: deve rispondere entro il '
                              . App\Nucleo\Calendario::tick((int) $o['scade_tick'])],
          'accettata'  => ['buono', 'accettata il ' . $quando . ': lavora per noi'],
          'rifiutata'  => ['cattivo', 'rifiutata il ' . $quando
                              . '. Da loro la proposta resta agli atti: è una prova'],
          'denunciata' => ['cattivo', 'denunciata in pubblico il ' . $quando
                              . ': il mondo sa che ci abbiamo provato, e i rapporti ne soffrono'],
          'scaduta'    => ['', 'nessuna risposta: scaduta il ' . $quando],
          default      => ['', (string) $o['stato']],
      }; ?>
      <li><strong><?= htmlspecialchars((string) $o['destinatario']) ?></strong>
          <span class="tenue"><?= htmlspecialchars(App\Gioco\Canale::etichettaRuolo((string) $o['ruolo'])) ?>
          di <?= htmlspecialchars((string) $o['paese']) ?>, offerta del <?= App\Nucleo\Calendario::tick((int) $o['tick']) ?></span>
          <span class="esito <?= $classe ?>"><?= htmlspecialchars($esito) ?></span>
      </li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="" class="modulo largo">
    <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
    <input type="hidden" name="azione" value="recluta">
    <label>A chi
      <select name="destinatario" required>
        <?php foreach ($rubrica as $paese => $poltrone):
          if ($paese === $poltrona['nazione']) continue; ?>
          <optgroup label="<?= htmlspecialchars((string) $paese) ?>">
          <?php foreach ($poltrone as $p): ?>
            <option value="<?= (int) $p['id'] ?>">
              <?= htmlspecialchars(App\Gioco\Canale::etichettaRuolo((string) $p['ruolo'])) ?>
              — <?= htmlspecialchars((string) ($p['giocatore'] ?? $p['titolare'])) ?>
            </option>
          <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Che cosa gli dici
      <textarea name="testo" rows="3" required maxlength="2000"
        placeholder="Un'offerta senza niente sul piatto non è un'offerta."></textarea></label>
    <fieldset class="piatto">
      <legend class="tenue">Sul piatto</legend>
      <label class="riga"><input type="checkbox" name="denaro" value="1"> denaro</label>
      <label class="riga"><input type="checkbox" name="dossier" value="1"> un dossier</label>
      <label class="riga"><input type="checkbox" name="appoggio" value="1"> appoggio alla sua causa</label>
    </fieldset>
    <button type="submit" class="pericolo">Fa' l'offerta</button>
  </form>
</section>
<?php endif; ?>

<!-- ─────────────────────────────  IL TAVOLO  ─────────────────────────────── -->
<div class="quadranti">
<section class="quadrante">
  <h3>Il tavolo</h3>
  <table>
    <tbody>
    <?php foreach ($colleghi as $c): ?>
      <tr<?= (int) $c['id'] === (int) $poltrona['id'] ? ' class="sono-io"' : '' ?>>
        <td class="tenue"><?= htmlspecialchars($ruoli[$c['ruolo']] ?? '') ?></td>
        <td><?= htmlspecialchars((string) ($c['giocatore'] ?? $c['nome'])) ?>
            <?php if ($c['giocatore'] === null): ?><span class="tenue">· apparato</span><?php endif; ?></td>
        <td class="num"><?= n((float) $c['potere'], 0) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<section class="quadrante">
  <h3>Chi ci guarda</h3>
  <table>
    <tbody>
    <?php foreach ($relazioni as $r): ?>
      <tr>
        <td><a href="<?= u('/nazione') ?>/<?= $r['codice'] ?>"><?= htmlspecialchars((string) $r['nome']) ?></a></td>
        <td class="num <?= (float) $r['affinita'] < -35 ? 'ostile' : ((float) $r['affinita'] > 55 ? 'amico' : '') ?>">
            <?= n((float) $r['affinita'], 0) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
</div>
