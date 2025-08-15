# RES-PONG
Plugin WordPress per gestire la prenotazione delle attività libere delle società di Tennistavolo.

## Stato del progetto
Lo sviluppo è in corso. Al momento è disponibile soltanto l'area di amministrazione:
- gestione degli utenti con import/export CSV, reset password e time-out;
- creazione, modifica ed esportazione degli eventi;
- gestione delle prenotazioni degli eventi;
- configurazione dei parametri generali del sistema.

L'interfaccia pubblica per gli utenti non è ancora stata implementata.

## Requisiti
- WordPress 6.x
- PHP 8.1 o superiore
- Database supportato da WordPress (MySQL/MariaDB)

## Installazione
1. Copia la cartella `res-pong-codex` nella directory `wp-content/plugins` del tuo sito WordPress.
2. Attiva il plugin dalla sezione **Plugin** della dashboard.
3. All'attivazione verranno create le tabelle `RP_USER`, `RP_EVENT` e `RP_RESERVATION`.
4. Accedi alla voce **Res Pong** del menu di amministrazione per configurare il sistema.

## API
Il plugin espone un endpoint REST con prefisso `/wp-json/res-pong-admin/v1/` utilizzato dall'interfaccia di amministrazione.

## Roadmap
- implementazione dell'area pubblica per i tesserati
- gestione delle notifiche e delle conferme presenza

Contributi e suggerimenti sono benvenuti!
