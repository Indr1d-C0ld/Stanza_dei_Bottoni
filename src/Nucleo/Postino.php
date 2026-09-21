<?php

declare(strict_types=1);

namespace App\Nucleo;

use RuntimeException;

/**
 * Un client SMTP minimo, senza dipendenze.
 *
 * STARTTLS sulla 587 o TLS implicito sulla 465, AUTH LOGIN, solo testo
 * semplice, un destinatario per invio. E' quanto basta per un messaggio di
 * verifica e per qualche avviso, e tirarsi dietro una libreria per farlo
 * sarebbe sproporzionato.
 *
 * Il Postino sa parlare SMTP e nient'altro: che fare quando il server non
 * risponde lo sa Posta, che e' la coda.
 *
 * Configurazione attesa, sotto la chiave 'posta':
 *   trasporto   'smtp' | 'registro'   (registro non invia: scrive nel log)
 *   host, porta, sicurezza ('tls' | 'ssl'), utente, parola
 *   da_indirizzo  (deve essere un mittente verificato dal provider), da_nome
 */
final class Postino
{
    /** @return array{ok:bool, errore?:string} */
    public static function manda(string $a, string $oggetto, string $corpo): array
    {
        $a = trim($a);
        if (!filter_var($a, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'errore' => 'destinatario non valido'];
        }

        $trasporto = (string) Configurazione::leggi('posta.trasporto', 'registro');
        $da     = (string) Configurazione::leggi('posta.da_indirizzo', 'noreply@localhost');
        $daNome = (string) Configurazione::leggi('posta.da_nome', 'Stanza dei Bottoni');

        if ($trasporto === 'registro' || $trasporto === 'nessuno') {
            $riga = sprintf("[%s] POSTA (registro) a=%s oggetto=%s\n%s\n\n",
                date('c'), $a, $oggetto, $corpo);
            @file_put_contents(dirname(__DIR__, 2) . '/storage/logs/posta.log', $riga, FILE_APPEND);
            return ['ok' => true];
        }
        if ($trasporto !== 'smtp') {
            return ['ok' => false, 'errore' => "trasporto sconosciuto: $trasporto"];
        }

        $host = (string) Configurazione::leggi('posta.host', '');
        if ($host === '') {
            return ['ok' => false, 'errore' => 'posta.host mancante'];
        }
        $porta     = (int) Configurazione::leggi('posta.porta', 587);
        $sicurezza = (string) Configurazione::leggi('posta.sicurezza', 'tls');
        $utente    = (string) Configurazione::leggi('posta.utente', '');
        $parola    = (string) Configurazione::leggi('posta.parola', '');
        $attesa    = (int) Configurazione::leggi('posta.timeout', 15);

        $uri = ($sicurezza === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $porta;
        $ctx = stream_context_create(['ssl' => ['SNI_enabled' => true, 'peer_name' => $host]]);
        $fp = @stream_socket_client($uri, $errno, $errstr, $attesa, STREAM_CLIENT_CONNECT, $ctx);
        if ($fp === false) {
            return ['ok' => false, 'errore' => "connessione fallita: $errstr ($errno)"];
        }
        stream_set_timeout($fp, $attesa);

        $saluto = gethostname() ?: 'stanzadeibottoni.local';

        try {
            self::attendi($fp, 220);
            self::comanda($fp, "EHLO $saluto");
            self::leggi($fp);

            if ($sicurezza === 'tls') {
                self::comanda($fp, 'STARTTLS');
                self::attendi($fp, 220);
                if (@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                    throw new RuntimeException('STARTTLS: stretta di mano fallita');
                }
                self::comanda($fp, "EHLO $saluto");
                self::leggi($fp);
            }

            if ($utente !== '') {
                self::comanda($fp, 'AUTH LOGIN');
                self::attendi($fp, 334);
                self::comanda($fp, base64_encode($utente));
                self::attendi($fp, 334);
                self::comanda($fp, base64_encode($parola));
                [$codice] = self::leggi($fp);
                if ($codice !== 235) {
                    throw new RuntimeException("autenticazione rifiutata ($codice)");
                }
            }

            self::comanda($fp, 'MAIL FROM:<' . $da . '>');
            self::attendi($fp, 250);
            self::comanda($fp, 'RCPT TO:<' . $a . '>');
            [$codice] = self::leggi($fp);
            if ($codice !== 250 && $codice !== 251) {
                throw new RuntimeException("destinatario rifiutato ($codice)");
            }
            self::comanda($fp, 'DATA');
            self::attendi($fp, 354);

            $testa = [
                'From: ' . self::intestazione($daNome) . ' <' . $da . '>',
                'To: <' . $a . '>',
                'Subject: ' . self::intestazione($oggetto),
                'Date: ' . date('r'),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $saluto . '>',
            ];
            // Una riga con un punto solo chiude i dati: va protetta.
            $testo = preg_replace('/^\./m', '..', str_replace("\r\n", "\n", $corpo));
            fwrite($fp, implode("\r\n", $testa) . "\r\n\r\n"
                . str_replace("\n", "\r\n", (string) $testo) . "\r\n.\r\n");
            self::attendi($fp, 250);

            self::comanda($fp, 'QUIT');
            fclose($fp);
            return ['ok' => true];
        } catch (\Throwable $e) {
            @fclose($fp);
            return ['ok' => false, 'errore' => $e->getMessage()];
        }
    }

    /** Un'intestazione che puo' contenere accenti va codificata. */
    private static function intestazione(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) === 1
            ? '=?UTF-8?B?' . base64_encode($s) . '?='
            : $s;
    }

    /** @param resource $fp */
    private static function comanda($fp, string $riga): void
    {
        fwrite($fp, $riga . "\r\n");
    }

    /** @param resource $fp @return array{0:int,1:string} */
    private static function leggi($fp): array
    {
        $codice = 0;
        $tutto  = '';
        while (($riga = fgets($fp, 1024)) !== false) {
            $tutto .= $riga;
            $codice = (int) substr($riga, 0, 3);
            if (strlen($riga) < 4 || $riga[3] !== '-') {
                break;
            }
        }
        return [$codice, $tutto];
    }

    /** @param resource $fp */
    private static function attendi($fp, int $atteso): void
    {
        [$codice, $testo] = self::leggi($fp);
        if ($codice !== $atteso) {
            throw new RuntimeException("atteso $atteso, ricevuto $codice: " . trim($testo));
        }
    }
}
