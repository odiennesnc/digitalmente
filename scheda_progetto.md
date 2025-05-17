Questa applicazione ha lo scopo di creare un backend per la gestione della catalogazione di libri, riviste e video.
Il layout dovrà essere accattivante e semplice e totalmente responvive.
Dovrà utilizzare gli stili e i layout contenuti nella cartella windmill-dashboard.

L'applicazione sarà accessibile tramite form di login con i seguenti campi:
    1. email
    2. password
Nella pagina di login ci dovrà essere anche la possibilità di fare il recupero delle credenziali

Gli utenti saranno gestiti tramite database mysql.
All'interno del backend l'amminstratore potrà creare nuovi utenti editor che saranno in grado di fare tutto a parte la creazione di utenti

Il menù dell'applicazione avrà le seguenti pagine:
    1. Argomenti
        1. Inserisci argomento
        2. Visualizza argomenti
    2. Documenti
        1. Inserisci un documento
        2. Visualizza documenti
    4. Utenti
        1. Inserisci nuovo utente
        2. Visualizza utenti
    5. Todo

Una volta effettuato il login si accede alla dashboard dell'applicazione dove viene visualizzata la lista degli ultimi documenti inseriti e la lista dei todo che sono da completare

Sezione Argomenti:
    Questa pagina contiene la lista di tutti gli argormenti ai quali saranno abbinati i documenti
        1. Aggiungi argomento
        2. Visulizza argomenti

    Le colonne visibili per la tabella degli argoomenti saranno:
        Argomento
        Numero di documenti abbinati
        Modifica
        Elimina

    Il tracciato della tabella mysql per gli argomenti è:
    id
    argomento

    Cliccando sul pulsante elimina argomento si dovrà aprire un alert dove si chiede di confermare o meno la cancellazione
    Cliccando sul pulsante modifica si aprirà il form per modificare tutti i campi dell'argomento.

Sezione documenti:
Le tipologie di documenti che verranno inseriti sono:
1. libri
    SCHEDA CATALOGAZIONE LIBRO
        -autore: Cognome e nome
        - titolo libro
        - collana
        -traduzione
        - editore
        - anno di pubblicazione
        - pagine
        - argomento
        -indice
        - bibliografia
2. riviste
    SCHEDA CATALOGAZIONE RIVISTE
        -titolo
        -anno
        -mese
        - numero
        -editore
        sommario
3. video-documentari
    SCHEDA CATALOGAZIONE VIDEO- DOCUMENTARIO
        - titolo
        - autore
        - anno
        - regia
        - montaggio
        - argomento

La pagina di inserimento di un documento contiene un form per l'inserimento dei documenti stessi che varia a seconda della tipologia.
Dovrà essere possibile anche caricare almento un'immagine per ogni documento
La tabella mysql per il salvataggio dei documenti sarà unica per tutte le tipologie e con il seguente tracciato:

== Struttura della tabella documenti

|------
|Colonna|Tipo|Null|Predefinito
|------
|//**id**//|int(11)|No|
|argomenti_id|int(11)|Sì|NULL
|autore|varchar(250)|Sì|NULL
|titolo|varchar(250)|Sì|NULL
|collana|varchar(250)|Sì|NULL
|traduzione|varchar(250)|Sì|NULL
|editore|varchar(250)|Sì|NULL
|anno_pubblicazione|varchar(50)|Sì|NULL
|pagine|varchar(50)|Sì|NULL
|tipologia_doc|int(11)|Sì|NULL
|indice|text|Sì|NULL
|bibliografia|text|Sì|NULL
|mese|varchar(50)|Sì|NULL
|numero|varchar(50)|Sì|NULL
|sommario|text|Sì|NULL
|regia|varchar(250)|Sì|NULL
|montaggio|varchar(250)|Sì|NULL
|argomento_trattato|varchar(250)|Sì|NULL
|foto|varchar(250)|Sì|NULL

Nella pagina di visulizzazione dei documenti dovrà esserci la lista dei documenti inseriti.
Se possibile creare la lista con datatable così da potere filtrare i risultati ed avere una paginazione

Le colonne della tabella saranno:
    Tipologia
    Argomento
    Autore
    Titolo
    Collana
    Editore
    Modifica
    Elimina

Cliccando sul pulsante elimina argomento si dovrà aprire un alert dove si chiede di confermare o meno la cancellazione
Cliccando sul pulsante modifica si aprirà il form per modificare tutti i campi dell'argomento.

Sezione utenti
Questa sezione sarà visibile solo per gli utenti con ruolo amministratore.
La pagina di inserimento utente prevederà un form per la creazione del nuovo utente.
Il tracciato mysql della tabella utenti è:

== Struttura della tabella utenti

|------
|Colonna|Tipo|Null|Predefinito
|------
|//**id**//|int(11)|No|
|nominativo|varchar(250)|Sì|NULL
|password|varchar(255)|Sì|NULL
|email|varchar(255)|Sì|NULL
|ruolo|int(11)|Sì|NULL
|last_login|date|Sì|NULL
|created_at|date|Sì|NULL
|reset_id|varchar(250)|Sì|NULL

Cliccando sul pulsante elimina utente si dovrà aprire un alert dove si chiede di confermare o meno la cancellazione
Cliccando sul pulsante modifica si aprirà il form per modificare i dati utente

Sezione todo
La sezione todo gestirà i task che ogni utente si segna come promemoria. Oltre al task va previsto anche una data di scadenza