<?php
/** @var array<string,mixed>|null $poltrona */
$ruoli = App\Dati\Gabinetto::RUOLI;
?>
<h1>Come si gioca</h1>
<p class="tenue">Dieci minuti di lettura. Non c'è altro da imparare: il resto si
   capisce facendolo, e sbagliando.</p>

<section>
  <h2>Che cos'è</h2>
  <p>Un mondo che va avanti da solo, con dentro centottantanove paesi. Ogni due
     ore fa un passo — un <strong>giro</strong>, che nel calendario del mondo è
     una settimana — e in quel passo le economie crescono o affondano, i governi
     tengono o cadono, le operazioni coperte maturano, le guerre uccidono.</p>
  <p>Va avanti anche se non c'è nessuno. Tu ci entri sedendoti a
     <strong>una poltrona sola</strong> di un gabinetto, e da lì cerchi di
     spostarlo.</p>
  <p class="tenue">Viene da due giochi per MS-DOS, <em>Shadow President</em>
     (1993) e <em>CyberJudas</em> (1996), e dal modello di <em>Balance of
     Power</em> di Chris Crawford (1985). Di Crawford è anche la cosa più
     importante che c'è da sapere: <strong>si vince non facendo niente di
     stupido</strong>, e la parte difficile è riconoscere in tempo quale mossa
     sia stupida.</p>
</section>

<section>
  <h2>La tua poltrona</h2>
  <p>Non comandi un paese: occupi <em>un posto</em> in un gabinetto di otto.
     Gli altri sette li tiene l'apparato — o altri giocatori.</p>
  <table class="tabella">
    <?php foreach ($ruoli as $k => $n): ?>
      <tr><td><strong><?= htmlspecialchars($n) ?></strong></td>
          <td class="tenue"><?= htmlspecialchars(match ($k) {
            'capo'         => 'Può quasi tutto, e per questo quasi tutto gli chiede una seconda firma.',
            'staff'        => 'Tiene la macchina in piedi e sa chi ha firmato cosa.',
            'esteri'       => 'Trattati, emissari, condanne pubbliche, linee dirette.',
            'difesa'       => 'Vendite di armi, dimostrazioni di forza, e il resto.',
            'intelligence' => 'Operazioni coperte, intercettazioni, reclutamenti, falsi.',
            'interni'      => 'Sicurezza interna, e chi trama in casa.',
            'economia'     => 'Aiuti, investimenti, restrizioni, embarghi.',
            'informazione' => 'Disinformazione, e il controllo di quel che si racconta.',
            default        => '',
          }) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <p class="tenue">Le cose che contano <strong>richiedono una seconda firma</strong>:
     è così che un gabinetto è un gabinetto e non un uomo solo al comando. Se
     chi deve firmare non firma, l'ordine decade.</p>
</section>

<section>
  <h2>Niente succede subito</h2>
  <p>Un ordine non è un fatto. Ogni azione esiste nel mondo <strong>prima</strong>
     di produrre effetti: parte, viaggia, e matura qualche giro dopo. In quella
     finestra può essere scoperta — e può essere fermata.</p>
  <p>Vale per te e vale per gli altri. È la ragione per cui l'intelligence non è
     un ornamento.</p>
</section>

<section>
  <h2>Sapere non basta: bisogna poter dimostrare</h2>
  <p>Di ogni cosa che succede nel mondo si può sapere a quattro livelli:</p>
  <table class="tabella">
    <tr><td><strong>1</strong></td><td>che è successo qualcosa</td></tr>
    <tr><td><strong>2</strong></td><td>di che genere, e più o meno dove</td></tr>
    <tr><td><strong>3</strong></td><td>chi l'ha subìto</td></tr>
    <tr><td><strong>4</strong></td><td><strong>chi l'ha ordinato</strong></td></tr>
  </table>
  <p>Il quarto è un ordine di grandezza più difficile degli altri tre, ed è
     l'unico che conta politicamente. <strong>Un'operazione che nessuno riesce
     ad attribuirti non ti costa niente</strong>: nessuno può protestare per una
     cosa che non può provare.</p>
  <p class="tenue">Da qui viene metà del gioco. Si può colpire e restare puliti,
     ma più si colpisce più si lascia impronta; e si può incolpare qualcun altro
     — la falsa bandiera — col rischio che il falso venga smontato.</p>
</section>

<section>
  <h2>I tuoi messaggi non sono sicuri</h2>
  <p>Puoi scrivere alle poltrone degli altri paesi. Il canale che scegli decide
     quanta gente ti legge:</p>
  <table class="tabella">
    <?php foreach (App\Gioco\Canale::LIVELLI as $n => $d): ?>
      <tr><td><strong><?= htmlspecialchars($d['nome']) ?></strong></td>
          <td class="tenue"><?= htmlspecialchars($d['nota']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <p>Nessun canale è sicuro davvero. Un servizio straniero che ti ascolta da
     abbastanza vicino non solo legge: se si è preparato, può
     <strong>riscrivere</strong> quel che hai scritto prima che arrivi. Il
     destinatario leggerà una cosa che non hai detto.</p>
  <p class="tenue">La sola difesa strutturale è che <em>chi ha scritto sa cosa ha
     scritto</em>. Se due si parlano anche altrove, il falso viene fuori.</p>
</section>

<section>
  <h2>Le crisi</h2>
  <p>Quando puoi <strong>dimostrare</strong> chi ti ha colpito, puoi contestarlo
     apertamente. Da lì parte una scala di nove gradini: a ogni mossa si sale o
     si cede, e non c'è una terza scelta — chi non risponde entro tre giri ha
     ceduto.</p>
  <p>Cedere costa la faccia, e la faccia il mondo la misura. Ma più si sale, più
     diventa caro tornare indietro, e in cima alla scala c'è una guerra vera.
     Dal quinto gradino in su ogni passo può anche sfuggire di mano da solo.</p>
  <p class="tenue">Prima di ogni mossa il gioco ti dice quanto costerebbe cedere
     adesso, a te e a loro. È l'unica informazione che serve, ed è quella che
     rende la scala una decisione invece di un binario.</p>
</section>

<section>
  <h2>Il commercio è un'arma che ferisce chi la impugna</h2>
  <p>I paesi si comprano energia, cibo, tecnologia, finanza e manufatti. Chiudere
     i rubinetti a qualcuno gli fa male in proporzione a quanto dipendeva da te e
     a quanto è difficile rimpiazzarti — ma fa male anche a te, perché smetti di
     essere pagato.</p>
  <p class="tenue">Un embargo verso chi non ti compra niente non è debole: è
     <em>inesistente</em>. Alla Scrivania dell'Economia trovi le due cifre prima
     di ordinare.</p>
</section>

<section>
  <h2>Quello che nessuno ti dirà</h2>
  <p>Hai <strong>due agende private</strong>, che nessun altro conosce. Non
     coincidono con l'interesse del tuo paese, e a volte lo contraddicono. Alla
     fine dell'epoca contano quanto il resto.</p>
  <p>E qualcuno, prima o poi, ti farà una proposta riservata. Accettarla
     significa che quel che passa dalla tua scrivania passerà anche da loro.
     Rifiutarla significa che adesso sanno che hai rifiutato.</p>
</section>

<section>
  <h2>Il giorno in cui si scopre tutto</h2>
  <p>Un'epoca dura qualche centinaio di giri. Quando finisce si conta — interesse
     nazionale, agende portate a casa, il prezzo pagato — ma soprattutto
     <strong>si rivela</strong>: ogni operazione coperta col suo vero mandante,
     ogni talpa col suo padrone, ogni lettera riscritta accanto all'originale.</p>
  <p class="tenue">Per un'epoca intera questo gioco è fatto di cose non dette.
     Quando si dicono tutte insieme si capisce che partita si stava giocando — ed
     è quello, non la classifica, il momento per cui vale la pena arrivare in
     fondo.</p>
</section>

<section>
  <h2>Tre cose pratiche</h2>
  <p><strong>Se stai via</strong>, dopo sei giri l'apparato riprende in mano il
     tuo paese e decide al posto tuo. Puoi invece affidare la poltrona a un altro
     giocatore — che potrà fare quel che avresti fatto tu, e quel che non avresti
     mai fatto.</p>
  <p><strong>Ti avvisiamo per posta</strong> quando c'è qualcosa che non può
     aspettare, non più di un messaggio al giorno, e li puoi spegnere.</p>
  <p><strong>Non si recupera niente</strong>: quel che è maturato è maturato, e
     una crisi ceduta resta ceduta. Il passato, in questo gioco, pesa.</p>
</section>

<?php if ($poltrona === null): ?>
  <p><a href="<?= u('/poltrone') ?>">Le poltrone libere</a> ·
     <a href="<?= u('/mappa') ?>">Il planisfero</a> ·
     <a href="<?= u('/nazioni') ?>">Le nazioni</a></p>
<?php else: ?>
  <p><a href="<?= u('/scrivania') ?>">Torna alla Scrivania</a></p>
<?php endif; ?>
