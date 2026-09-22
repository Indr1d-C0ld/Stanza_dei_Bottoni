<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Come A vede B. Asimmetrica per costruzione: l'Ucraina e la Russia non hanno
 * la stessa opinione l'una dell'altra.
 */
final class Relazione
{
    public function __construct(
        /** Country Interaction Mood: 1 Armonia di interessi .. 7 Inimicizia */
        public int   $umore = 4,
        /** Affinità diplomatica, -127 .. +127 (Balance of Power) */
        public float $affinita = 0.0,
        /** Obbligo di trattato: 0 / 16 / 32 / 64 / 96 / 128 */
        public int   $obbligo = 0,
        /**
         * L'obbligo MESSO PER ISCRITTO: il trattato che esiste davvero, dal
         * Correlates of War. E' un fatto, non un sentimento.
         *
         * Serve perche' la fase 06 ricalcola l'obbligo dall'affinita' a ogni
         * tick e lo abbassa col due per cento di probabilita': su quindici anni
         * la denuncia e' praticamente certa, e la struttura di alleanze del
         * mondo si sfaldava da 2.763 patti di difesa a diciassette. Nessuna
         * garanzia veniva mai messa alla prova, e l'integrita' — il meccanismo
         * con cui Crawford rende costose le promesse — non aveva su cosa
         * mordere.
         *
         * Le alleanze vere non si sciolgono perche' due governi si
         * raffreddano: la Grecia e la Turchia stanno nella NATO da
         * settant'anni senza volersi bene.
         */
        public int   $obbligoFirmato = 0,
        /** Sfera di influenza di A su B (DontMess), 1 .. 15 */
        public int   $sfera = 1,
        /** La memoria delle crisi vinte o perse su questo paese: si somma
         *  alla sfera strutturale e si consuma piano negli anni. */
        public float $spintaSfera = 0.0,
        public bool  $confinanti = false,
        /**
         * La memoria del rapporto. Crawford pesa la storia otto volte
         * l'ideologia: senza un'ancora, la deriva strutturale riconcilia
         * Israele e Siria in quindici anni di pura aritmetica.
         * Solo gli eventi la muovono.
         */
        public ?float $ancora = null,
    ) {}

    /** L'umore discende dall'affinità: sono la stessa cosa vista da due lati. */
    public function aggiornaUmore(): void
    {
        $this->umore = match (true) {
            $this->affinita >=  90 => 1,   // armonia di interessi
            $this->affinita >=  55 => 2,   // amicizia
            $this->affinita >=  20 => 3,   // cooperazione
            $this->affinita >= -20 => 4,   // indifferenza
            $this->affinita >= -55 => 5,   // competizione
            $this->affinita >= -90 => 6,   // rivalità
            default                => 7,   // inimicizia
        };
    }
}
