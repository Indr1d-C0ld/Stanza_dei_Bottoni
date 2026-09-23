<?php

declare(strict_types=1);

namespace App\Gioco;

use App\Dati\Lettura;
use App\Nucleo\Basedati;
use App\Nucleo\Calibrazione;

/**
 * Tutti i servizi di gioco in un posto solo.
 *
 * Non e' un contenitore di dipendenze e non vuole diventarlo: e' un sacchetto
 * di oggetti gia' costruiti, con un motivo preciso. La funzione che smista le
 * azioni del web ne riceveva uno per parametro, e ogni pezzo nuovo del gioco ne
 * aggiungeva un altro. La prima volta che la lista e' cresciuta, il punto di
 * chiamata e' rimasto indietro e le crisi rispondevano con un errore 500 —
 * perche' un array finiva dove il codice si aspettava un oggetto.
 *
 * Con un solo parametro quel modo di sbagliare non esiste piu'.
 */
final class Servizi
{
    public readonly Sessione $sessione;
    public readonly Scrivania $scrivania;
    public readonly Canale $canale;
    public readonly Reclutamento $reclutamento;
    public readonly Crisi $crisi;
    public readonly Agende $agende;
    public readonly Falsificazione $falso;
    public readonly Linea $linea;
    public readonly Delega $delega;
    public readonly Epoca $epoca;
    public readonly Mercato $mercato;
    public readonly Arbitrio $arbitrio;
    public readonly Inviti $inviti;
    public readonly \App\Nucleo\Posta $posta;
    public readonly Lettura $lettura;
    public readonly Calibrazione $calibrazione;

    public function __construct(Basedati $db, Calibrazione $cal, string $radice)
    {
        $this->calibrazione = $cal;
        $this->lettura      = new Lettura($db);
        $this->sessione     = new Sessione($db);
        $this->scrivania    = new Scrivania($db, (int) $cal->numero('gioco.ordini_per_tick', 2));
        $this->canale       = new Canale($db);
        $this->reclutamento = new Reclutamento($db);
        $this->crisi        = new Crisi(
            $db,
            (array) $cal->leggi('crisi.gradini', []),
            $cal->numero('crisi.crescita_posta', 0.38),
            (int) $cal->numero('crisi.pazienza_tick', 3),
        );
        $this->agende = new Agende($db, require $radice . '/calibrazione/agende.php');
        $this->falso  = new Falsificazione($db);
        $this->linea  = new Linea($db);
        $this->delega = new Delega($db);
        $this->epoca  = new Epoca($db);
        $this->mercato = new Mercato($db, $cal, $radice);
        $this->arbitrio = new Arbitrio($db);
        $this->inviti   = new Inviti($db);
        $this->posta   = new \App\Nucleo\Posta($db);
    }
}
