# Istruzioni per la Migrazione del Database e Utilizzo del Nuovo Sistema di Gestione Documenti

## Panoramica delle Modifiche

Il sistema di gestione dei documenti è stato completamente riprogettato per risolvere i problemi di binding dei parametri SQL e migliorare la struttura dei dati. Le principali modifiche sono:

1. **Nuova struttura del database**
   - Una tabella base (`documenti_base`) che contiene i campi comuni a tutti i documenti
   - Tabelle separate per libri (`documenti_libri`), riviste (`documenti_riviste`) e video (`documenti_video`) per i campi specifici

2. **Nuove funzioni di gestione**
   - Gestione dei documenti con transazioni per mantenere l'integrità tra le tabelle
   - Migliore gestione degli errori e validazione
   - Funzioni specifiche per ogni tipo di documento

3. **Aggiornamenti UI**
   - Menu aggiornato con accesso diretto ai form specifici per tipo di documento
   - Pagina di selezione del tipo di documento prima dell'inserimento
   - Form di modifica specifici per ogni tipo di documento

## Procedura di Migrazione

### Passo 1: Eseguire la Migrazione del Database

1. Accedere al sistema come amministratore
2. Andare alla nuova sezione "Amministrazione" nel menu laterale
3. Selezionare "Migrazione Database"
4. Confermare di aver fatto un backup del database spuntando la casella
5. Cliccare su "Esegui Migrazione"

Lo script di migrazione eseguirà le seguenti operazioni:
- Creare le nuove tabelle nel database
- Migrare tutti i dati esistenti dalla tabella `documenti` alle nuove tabelle
- Mantenere gli ID originali per garantire la compatibilità

### Passo 2: Verificare la Migrazione

Dopo aver eseguito la migrazione:
1. Andare a "Visualizza documenti" nel menu Documenti
2. Verificare che tutti i documenti siano visualizzati correttamente
3. Provare a modificare un documento di ciascun tipo per verificare che i dati siano stati migrati correttamente

## Utilizzo del Nuovo Sistema

### Inserimento di Documenti

1. Dal menu "Documenti", selezionare "Inserisci documento"
2. Nella pagina di selezione del tipo, scegliere la tipologia di documento da inserire (Libro, Rivista, Video)
3. Compilare il form specifico con i campi richiesti
4. Confermare l'inserimento

In alternativa, è possibile utilizzare direttamente i link nel menu per:
- Inserisci libro
- Inserisci rivista
- Inserisci video

### Modifica di Documenti

1. Dalla pagina "Visualizza documenti", cliccare sull'icona di modifica per il documento desiderato
2. Il sistema aprirà automaticamente il form di modifica specifico per il tipo di documento
3. Effettuare le modifiche e salvare

### Eliminazione di Documenti

Il processo di eliminazione è rimasto invariato:
1. Dalla pagina "Visualizza documenti", cliccare sull'icona di eliminazione
2. Confermare l'eliminazione nel modal

## Supporto e Problemi Noti

In caso di problemi:
- Verificare i log degli errori di PHP
- Ripristinare i file di backup (.bak) se necessario
- Contattare il supporto tecnico

---

**Nota**: Prima di procedere con la migrazione in produzione, è fortemente consigliato testare l'intera procedura in un ambiente di test con dati reali.
