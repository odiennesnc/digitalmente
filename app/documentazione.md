# Documentazione Applicazione Digitalmente

## Indice
1. [Introduzione](#introduzione)
2. [Requisiti di Sistema](#requisiti-di-sistema)
3. [Installazione](#installazione)
4. [Struttura dell'Applicazione](#struttura-dellapplicazione)
5. [Funzionalità](#funzionalità)
   - [Autenticazione](#autenticazione)
   - [Dashboard](#dashboard)
   - [Argomenti](#argomenti)
   - [Documenti](#documenti)
   - [Utenti](#utenti)
   - [Todo](#todo)
6. [Database](#database)
7. [Guida Utente](#guida-utente)
8. [Manutenzione](#manutenzione)
9. [Risoluzione Problemi](#risoluzione-problemi)

## Introduzione

L'applicazione Digitalmente è un sistema di catalogazione per libri, riviste e video. Consente agli utenti di organizzare, cercare e gestire documenti di diversa tipologia. L'interfaccia responsive è basata sui templates di Windmill Dashboard e permette una facile navigazione da qualsiasi dispositivo.

## Requisiti di Sistema

- **Server Web**: Apache/Nginx con PHP 7.4 o superiore
- **Database**: MySQL 5.7 o superiore
- **PHP Extensions**: mysqli, mbstring, gd
- **Browser**: Qualsiasi browser web moderno (Chrome, Firefox, Safari, Edge)

## Installazione

1. Clonare o scaricare i file dell'applicazione nella directory del server web
2. Creare un database MySQL
3. Importare il file `/app/config/database.sql` per creare le tabelle necessarie
4. Configurare i parametri di connessione nel file `/app/config/db.php`:
   ```php
   $db_host = "localhost";     // Host del database
   $db_user = "root";          // Nome utente database
   $db_pass = "";              // Password database
   $db_name = "digitalmente";  // Nome del database
   ```
5. Assicurarsi che la directory `/app/uploads/documents/` abbia i permessi di scrittura
6. Accedere all'applicazione tramite browser (utilizzo dell'utente predefinito admin@example.com con password admin123)

## Struttura dell'Applicazione

```
app/
├── config/           # Configurazioni del database e file SQL
├── includes/         # File includibili (header, footer, funzioni)
├── assets/           # Risorse statiche (CSS, JS, immagini)
├── uploads/          # Directory per i file caricati
├── argomenti/        # Gestione argomenti (categorie)
├── documenti/        # Gestione documenti (libri, riviste, video)
├── utenti/           # Gestione utenti
└── todo/             # Gestione task e promemoria
```

## Funzionalità

### Autenticazione

L'applicazione prevede un sistema di autenticazione completo con:
- **Login**: Accesso tramite email e password
- **Recupero password**: Sistema per reimpostare la password tramite email
- **Logout**: Uscita dal sistema

L'applicativo supporta due ruoli utente:
- **Amministratore** (ruolo 1): Accesso completo a tutte le funzionalità
- **Editor** (ruolo 2): Accesso limitato (non può gestire gli utenti)

### Dashboard

La dashboard principale mostra:
- Statistiche generali (numero documenti, argomenti, utenti)
- Lista degli ultimi documenti inseriti
- Lista dei task da completare

### Argomenti

Sezione per la gestione delle categorie dei documenti:
- **Visualizzazione**: Lista degli argomenti con conteggio documenti associati
- **Aggiunta**: Inserimento di nuovi argomenti
- **Modifica**: Aggiornamento di argomenti esistenti
- **Eliminazione**: Rimozione di argomenti (con validazione per evitare eliminazioni di categorie in uso)

### Documenti

Il cuore dell'applicazione per catalogare materiali:
- **Visualizzazione**: Lista documentI filtrabili per tipo e categoria
- **Aggiunta**: Form dinamico che cambia in base alla tipologia (libro, rivista, video)
- **Modifica**: Aggiornamento documenti esistenti
- **Eliminazione**: Rimozione documenti con conferma
- **Upload immagini**: Possibilità di associare immagini ai documenti

Campi specifici per tipologia:
- **Libro**: Autore, Titolo, Collana, Traduzione, Editore, Anno pubblicazione, Pagine, Indice, Bibliografia
- **Rivista**: Autore, Titolo, Editore, Anno pubblicazione, Mese, Numero, Sommario
- **Video**: Titolo, Regia, Montaggio, Anno pubblicazione, Argomento trattato

### Utenti

Sezione amministrativa per la gestione degli utenti (solo per amministratori):
- **Visualizzazione**: Lista utenti con ruoli e dati di accesso
- **Aggiunta**: Creazione nuovi account
- **Modifica**: Aggiornamento profili e modifica ruoli
- **Eliminazione**: Rimozione account con validazione

### Todo

Sistema di gestione dei promemoria personali:
- **Visualizzazione**: Lista dei task dell'utente con stato e scadenze
- **Aggiunta**: Creazione nuovi task con descrizione e data di scadenza
- **Modifica**: Aggiornamento task esistenti
- **Cambio stato**: Marcatura task come completati o da fare
- **Eliminazione**: Rimozione task

## Database

Il database si compone di quattro tabelle principali:
1. **argomenti**: Categorie per i documenti
2. **documenti**: Archivio dei materiali catalogati
3. **utenti**: Account e credenziali degli utenti
4. **todo**: Task e promemoria personali

Relazioni:
- Documenti → Argomenti (foreign key: argomenti_id)
- Todo → Utenti (foreign key: utente_id)

## Guida Utente

### Accesso al sistema
1. Dalla pagina di login, inserire email e password
2. Se dimentichi la password, usa il link "Password dimenticata?"
3. L'utente admin predefinito è admin@example.com con password admin123

### Gestione documenti
1. Per inserire un nuovo documento, vai su "Documenti" -> "Inserisci documento"
2. Seleziona la tipologia (libro, rivista o video)
3. Compila i campi richiesti e carica un'immagine (opzionale)
4. Per modificare un documento esistente, vai su "Documenti" -> "Visualizza documenti" e clicca sull'icona di modifica
5. Per eliminare un documento, clicca sull'icona di eliminazione e conferma

### Utilizzo della funzionalità Todo
1. Per aggiungere un task, vai su "Todo" -> "Aggiungi task"
2. Inserisci la descrizione e la data di scadenza (opzionale)
3. Per completare un task, clicca sul pulsante "Da fare" che lo cambierà in "Completato"
4. Per modificare o eliminare un task, usa le icone apposite nella lista

## Manutenzione

### Backup del database
Si consiglia di effettuare backup regolari del database utilizzando:
```
mysqldump -u username -p digitalmente > backup_digitalmente_$(date +%Y%m%d).sql
```

### Gestione file
I file caricati vengono salvati in `/app/uploads/documents/`. Verificare periodicamente lo spazio disponibile.

### Aggiornamento
Per aggiornare l'applicazione:
1. Effettuare un backup del database e dei file
2. Sostituire i file dell'applicazione con quelli nuovi
3. Eseguire eventuali script di aggiornamento del database

## Risoluzione Problemi

| Problema | Possibile soluzione |
|----------|---------------------|
| Errore di connessione al database | Verificare i parametri nel file `/app/config/db.php` e controllare che il servizio MySQL sia attivo |
| Impossibile caricare file | Verificare che la directory `/app/uploads/documents/` abbia i permessi di scrittura |
| Impossibile accedere | Verificare che l'utente esista e che la password sia corretta. Eventualmente utilizzare la funzionalità di reset password |
| Errore 500 | Controllare i log del server per identificare il problema specifico |

---

**Data documentazione**: 17 maggio 2025  
**Versione applicazione**: 1.0
