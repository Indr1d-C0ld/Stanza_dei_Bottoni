<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Una fazione interna: chi ha messo al potere il capo, e chi può toglierlo.
 *
 * È il "Panel" di CyberJudas — il gruppo di leader che ti ha messo lì e che ti
 * assegna il compito — generalizzato. La stessa struttura descrive regimi
 * diversissimi: partito, finanziatori, stampa e apparato in una democrazia;
 * servizi, esercito, famiglia e oligarchi in un'autocrazia. Cambiano i nomi e i
 * pesi, non la meccanica.
 */
final class Fazione
{
    public function __construct(
        public readonly string $nome,
        public float $forza,     // 0..100
        public float $favore,    // -100 ostile .. +100 devota
        public readonly string $agenda,
    ) {}
}
