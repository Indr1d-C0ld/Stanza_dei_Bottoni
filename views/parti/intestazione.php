<?php
/** @var string $titolo */
/** @var array<string,mixed>|null $stato */
$statoMondo = $stato ?? null;
$livelli = [1 => 'pace globale', 2 => 'pace stabile', 3 => 'pace fredda',
            4 => 'guerra fredda', 5 => 'guerra calda', 6 => 'anarchia'];
?><!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titolo) ?> — Stanza dei Bottoni</title>
<?php
/**
 * Il foglio di stile porta in coda la data dell'ultima modifica.
 *
 * Senza, il browser tiene la propria copia e una correzione allo stile non
 * arriva mai a chi e' gia' stato sul sito. Con la data in coda l'indirizzo
 * cambia quando cambia il file, e solo allora: la cache continua a funzionare
 * per tutto il resto del tempo, che e' quel che deve fare.
 */
$foglio = dirname(__DIR__, 2) . '/assets/css/stanza.css';
$versione = is_file($foglio) ? (int) filemtime($foglio) : 0;
?>
<link rel="stylesheet" href="<?= u('/assets/css/stanza.css') ?>?v=<?= $versione ?>">
</head>
<body>
<header class="barra">
  <a class="marchio" href="<?= u('/') ?>">Stanza dei Bottoni</a>
  <nav>
    <a href="<?= u('/') ?>">Il mondo</a>
    <a href="<?= u('/mappa') ?>">Il planisfero</a>
    <a href="<?= u('/nazioni') ?>">Le nazioni</a>
    <a href="<?= u('/cronaca') ?>">La cronaca</a>
    <a href="<?= u('/guida') ?>">Come si gioca</a>
    <a href="<?= u('/bilancio') ?>">Il bilancio</a>
    <?php if (($sessione ?? null) && $sessione->autenticato()): ?>
      <a href="<?= u('/scrivania') ?>">La Scrivania</a>
      <a href="<?= u('/messaggi') ?>">Il Canale</a>
      <a href="<?= u('/commercio') ?>">Il commercio</a>
      <?php if ($sessione->arbitro()): ?>
        <a href="<?= u('/arbitrio') ?>" class="banco">Il banco</a>
      <?php endif; ?>
    <?php endif; ?>
  </nav>
  <?php if ($statoMondo !== null): ?>
  <div class="orologio">
    <span class="data"><?= htmlspecialchars(App\Nucleo\Calendario::data((string) $statoMondo['data_gioco'])) ?></span>
    <span class="pace"><?= htmlspecialchars($livelli[(int) $statoMondo['livello_pace']] ?? '—') ?></span>
  </div>
  <?php endif; ?>
  <?php if (($sessione ?? null) && $sessione->autenticato()): ?>
    <form class="uscita" method="post" action="">
      <input type="hidden" name="gettone" value="<?= htmlspecialchars($sessione->gettone()) ?>">
      <input type="hidden" name="azione" value="esci">
      <span class="tenue"><?= htmlspecialchars((string) $sessione->giocatore()['nome']) ?></span>
      <button type="submit">esci</button>
    </form>
  <?php elseif (($sessione ?? null)): ?>
    <span class="uscita"><a href="<?= u('/entra') ?>">entra</a> · <a href="<?= u('/registrati') ?>">registrati</a></span>
  <?php endif; ?>
</header>
<main>
<?php if (!empty($avviso)): ?>
  <p class="avviso <?= $avviso[0] ? 'buono' : 'cattivo' ?>"><?= htmlspecialchars((string) $avviso[1]) ?></p>
<?php endif; ?>
