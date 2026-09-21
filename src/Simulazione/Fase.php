<?php

declare(strict_types=1);

namespace App\Simulazione;

/**
 * Una delle dodici fasi del tick.
 *
 * Ogni fase deve essere IDEMPOTENTE (controllare di non aver già scritto per
 * quel tick) e DETERMINISTICA nell'ordine (mai affidarsi a un ordinamento
 * implicito: sempre ORDER BY id). Senza queste due proprieta' un cron che
 * riparte a metà corrompe il mondo in modo silenzioso.
 */
interface Fase
{
    public function codice(): string;

    public function nome(): string;

    public function esegui(ContestoTick $contesto): EsitoFase;
}
