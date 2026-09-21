<h1>Il mondo non è ancora avviato</h1>
<p>La base dati esiste ma non contiene alcuno stato. Servono due comandi, in quest'ordine:</p>
<pre>sudo bash deploy/00-avvio.sh
php bin/avvia_mondo.php</pre>
<p class="tenue">Il primo crea database, utente e tabelle; il secondo costruisce il
mondo dal seme e lo scrive al tick zero. Da lì in poi <code>php bin/tick.php</code>
fa scorrere il tempo.</p>
