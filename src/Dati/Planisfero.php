<?php

declare(strict_types=1);

namespace App\Dati;

/**
 * Il planisfero: i confini, e come si colorano.
 *
 * I tracciati stanno in db/seed/confini-svg.json, gia' proiettati e gia'
 * sgrossati da bin/costruisci_mappa.php — a runtime non si fa nessun conto
 * geografico. Qui si decide soltanto di che colore e' ciascun paese, e questo
 * e' il punto: una mappa politica non serve a sapere dove stanno i posti, serve
 * a vedere in un colpo d'occhio una cosa che nelle tabelle si perde.
 *
 * Sei letture, e ciascuna racconta una cosa diversa dello stesso mondo.
 */
final class Planisfero
{
    /** @var array<string,array{titolo:string,nota:string}> */
    public const LETTURE = [
        'legittimita' => [
            'titolo' => 'Chi tiene in piedi il proprio governo',
            'nota'   => 'Verde: il governo regge. Rosso: sta per cadere, e lo sanno tutti.',
        ],
        'influenza' => [
            'titolo' => 'Chi conta',
            'nota'   => 'Quota di influenza mondiale. Le grandi potenze si vedono da lontano.',
        ],
        'tensione' => [
            'titolo' => 'Dove si spara',
            'nota'   => 'Dalla quiete alla guerra totale, paese per paese.',
        ],
        'benessere' => [
            'titolo' => 'Come si vive',
            'nota'   => 'Qualità della vita di chi ci abita, che non coincide col prodotto.',
        ],
        'commercio' => [
            'titolo' => 'Chi ha i rubinetti chiusi',
            'nota'   => 'Pressione dei nostri embarghi e di quelli altrui, in punti di crescita persi.',
        ],
        'rapporti' => [
            'titolo' => 'Chi ci vuole bene',
            'nota'   => 'Come ci guardano, dal nostro paese. Blu: amici. Rosso: il contrario.',
        ],
    ];

    /** @var array<string,string> iso3 => tracciato */
    public readonly array $tracciati;
    public readonly float $larghezza;
    public readonly float $altezza;

    public function __construct(string $percorso)
    {
        $d = is_file($percorso)
            ? (array) json_decode((string) file_get_contents($percorso), true)
            : [];
        $this->tracciati = (array) ($d['tracciati'] ?? []);
        $this->larghezza = (float) ($d['larghezza'] ?? 1000);
        $this->altezza   = (float) ($d['altezza'] ?? 507);
    }

    public function pronto(): bool
    {
        return $this->tracciati !== [];
    }

    /**
     * Il colore di ogni paese, per una certa lettura.
     *
     * La «misura» e' il valore vero, orientato in modo che piu' alto voglia dire
     * meglio: serve a ordinare (code()), perche' il colore non si ordina. Vale
     * null dove non c'e' niente da misurare (un paese con cui non abbiamo
     * rapporti non e' ne' amico ne' nemico).
     *
     * @param list<array<string,mixed>> $nazioni
     * @param array<string,float> $relazioni iso3 => affinità, per la lettura 'rapporti'
     * @return array<string,array{colore:string,valore:string,misura:?float}>
     */
    public function colori(string $lettura, array $nazioni, array $relazioni = []): array
    {
        $per = [];
        foreach ($nazioni as $n) {
            $iso = (string) $n['codice'];
            [$q, $etichetta] = match ($lettura) {
                'influenza'  => $this->scalaInfluenza((float) $n['influenza_totale']),
                'tensione'   => $this->scalaTensione((int) $n['net_peace']),
                'benessere'  => [(float) $n['qualita_vita'] / 10.0,
                                 sprintf('%d su 10', (int) $n['qualita_vita'])],
                'commercio'  => $this->scalaCommercio((float) $n['pressione_esterna']),
                'rapporti'   => $this->scalaRapporti($relazioni[$iso] ?? null),
                default      => [(float) $n['legittimita'] / 100.0,
                                 sprintf('%d su 100', (int) $n['legittimita'])],
            };
            $per[$iso] = [
                'colore'  => $lettura === 'rapporti' ? $this->freddoCaldo($q) : $this->rossoVerde($q),
                'valore'  => $etichetta,
                'misura'  => match ($lettura) {
                    'influenza' => (float) $n['influenza_totale'],
                    'tensione'  => -(float) $n['net_peace'],
                    'benessere' => (float) $n['qualita_vita'],
                    'commercio' => -(float) $n['pressione_esterna'],
                    'rapporti'  => isset($relazioni[$iso]) ? (float) $relazioni[$iso] : null,
                    default     => (float) $n['legittimita'],
                },
            ];
        }
        return $per;
    }

    /**
     * Le due code di una lettura: i paesi messi peggio e quelli messi meglio.
     *
     * Si ordina sulla misura, non sul colore. Il colore ordinato come testo
     * mette in fondo l'ocra di mezzo, che ha il rosso piu' acceso di tutti:
     * cosi' la pagina dava per «peggiori» i paesi medi (docs/40).
     *
     * Gli ex aequo contano. Su certe letture i valori sono pochi (sei gradi di
     * tensione, dieci di benessere) e il quinto posto e' spesso condiviso da
     * decine di paesi: sceglierne uno per ordine alfabetico direbbe una cosa
     * falsa. Allora la coda prende i primi $quante e conta a parte («altri»)
     * chi sta allo stesso valore dell'ultimo. La coda dei peggiori non arriva
     * mai al valore migliore: se 186 paesi non hanno sanzioni, non sono i
     * peggiori di nessuno.
     *
     * @param array<string,array{misura:?float}> $colori nell'ordine in cui
     *        vanno mostrati gli ex aequo (per nome)
     * @return array{peggio:array{righe:list<string>,altri:list<string>},
     *               meglio:array{righe:list<string>,altri:list<string>}}
     */
    public static function code(array $colori, int $quante = 5): array
    {
        $misure = [];
        foreach ($colori as $iso => $c) {
            if (($c['misura'] ?? null) !== null) {
                $misure[(string) $iso] = (float) $c['misura'];
            }
        }
        if ($misure === []) {
            return ['peggio' => ['righe' => [], 'altri' => []], 'meglio' => ['righe' => [], 'altri' => []]];
        }
        $migliore = max($misure);

        // uasort e' stabile: a parita' di misura resta l'ordine per nome.
        $su = $misure;
        uasort($su, static fn (float $a, float $b) => $a <=> $b);
        $giu = $misure;
        uasort($giu, static fn (float $a, float $b) => $b <=> $a);

        $peggio = self::coda($su, $quante, static fn (float $v) => $v < $migliore);
        $presi = array_flip(array_merge($peggio['righe'], $peggio['altri']));
        $meglio = self::coda($giu, $quante, static fn (float $v) => true, $presi);
        return ['peggio' => $peggio, 'meglio' => $meglio];
    }

    /**
     * @param array<string,float> $ordinate
     * @param callable(float):bool $ammesso
     * @param array<string,mixed> $esclusi
     * @return array{righe:list<string>,altri:list<string>}
     */
    private static function coda(array $ordinate, int $quante, callable $ammesso, array $esclusi = []): array
    {
        $righe = [];
        $altri = [];
        $confine = null;
        foreach ($ordinate as $iso => $v) {
            $iso = (string) $iso;
            if (isset($esclusi[$iso]) || !$ammesso($v)) {
                continue;
            }
            if (count($righe) < $quante) {
                $righe[] = $iso;
                $confine = $v;
            } elseif ($v === $confine) {
                $altri[] = $iso;
            } else {
                break;
            }
        }
        return ['righe' => $righe, 'altri' => $altri];
    }

    /** @return array{0:float,1:string} */
    private function scalaInfluenza(float $v): array
    {
        // L'influenza e' distribuita malissimo: la Cina sta al 17%, la mediana
        // sotto lo 0,2%. Su scala lineare il mondo sarebbe tutto rosso con tre
        // macchie verdi, e non si leggerebbe niente. La radice apre il basso.
        // Sotto lo 0,1% due decimali danno «0,00%» a una ventina di paesi e una
        // cifra sola a un'ottantina: li si scrive con due cifre significative.
        $q = min(1.0, ($v / 12.0) ** 0.45);
        $decimali = $v >= 0.1 || $v <= 0.0 ? 2 : min(6, 1 - (int) floor(log10($v)));
        return [$q, number_format($v, $decimali, ',', '.') . '%'];
    }

    /** @return array{0:float,1:string} */
    private function scalaTensione(int $netPeace): array
    {
        $nomi = [1 => 'quiete', 2 => 'pace', 3 => 'tensione',
                 4 => 'conflitto aperto', 5 => 'guerra', 6 => 'guerra totale'];
        return [1.0 - ($netPeace - 1) / 5.0, $nomi[$netPeace] ?? '—'];
    }

    /** @return array{0:float,1:string} */
    private function scalaCommercio(float $pressione): array
    {
        return [
            1.0 - min(1.0, $pressione / 0.085),
            $pressione > 0.0005
                ? '−' . number_format(100 * $pressione, 2, ',', '.') . '% di crescita'
                : 'nessuna sanzione',
        ];
    }

    /** @return array{0:float,1:string} */
    private function scalaRapporti(?float $affinita): array
    {
        if ($affinita === null) {
            return [0.5, 'nessun rapporto degno di nota'];
        }
        return [
            ($affinita + 127.0) / 254.0,
            sprintf('%+d su 127', (int) round($affinita)),
        ];
    }

    /** Dal rosso (0) al verde (1), passando per un ocra che non urla. */
    private function rossoVerde(float $q): string
    {
        $q = max(0.0, min(1.0, $q));
        return $q < 0.5
            ? $this->fondi([0xC0, 0x44, 0x3C], [0xC9, 0xA2, 0x3F], $q * 2.0)
            : $this->fondi([0xC9, 0xA2, 0x3F], [0x4F, 0x9E, 0x5C], ($q - 0.5) * 2.0);
    }

    /** Dal rosso (0) al blu (1), col grigio nel mezzo: e' l'indifferenza. */
    private function freddoCaldo(float $q): string
    {
        $q = max(0.0, min(1.0, $q));
        return $q < 0.5
            ? $this->fondi([0xC0, 0x44, 0x3C], [0x5A, 0x62, 0x6E], $q * 2.0)
            : $this->fondi([0x5A, 0x62, 0x6E], [0x4C, 0x7F, 0xB8], ($q - 0.5) * 2.0);
    }

    /** @param array{0:int,1:int,2:int} $a @param array{0:int,1:int,2:int} $b */
    private function fondi(array $a, array $b, float $t): string
    {
        return sprintf('#%02x%02x%02x',
            (int) round($a[0] + ($b[0] - $a[0]) * $t),
            (int) round($a[1] + ($b[1] - $a[1]) * $t),
            (int) round($a[2] + ($b[2] - $a[2]) * $t));
    }
}
