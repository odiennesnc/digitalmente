<?php
// filepath: /Users/Odn/Documents/Lavori O(n)/digitalmente/app/todo/elimina.php
require_once '../includes/header.php';

// Log l'ingresso nel file elimina.php
error_log("Accesso a elimina.php - " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("Utente non loggato - reindirizzamento al login");
    redirect('../login.php');
}

// Check if ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Log del valore ricevuto
    error_log("ID ricevuto: " . $_POST['id']);
    
    // Puliamo e validiamo l'ID del task
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $id = filter_var($id, FILTER_VALIDATE_INT);
    
    error_log("ID validato: " . ($id !== false ? $id : "non valido"));
    
    if (!$id) {
        $_SESSION['message'] = displayError('ID task non valido');
        error_log("ID task non valido");
        ob_end_clean();
        redirect('./index.php');
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Log per debug
    error_log("Tentativo di eliminare il task con ID: " . $id . " per l'utente: " . $userId);
    
    // First verify the task belongs to the current user
    $checkStmt = $conn->prepare("SELECT id FROM todo WHERE id = ? AND utente_id = ?");
    $checkStmt->bind_param("ii", $id, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 1) {
        // Delete the task
        $deleteStmt = $conn->prepare("DELETE FROM todo WHERE id = ? AND utente_id = ?");
        $deleteStmt->bind_param("ii", $id, $userId);
        
        if ($deleteStmt->execute()) {
            $_SESSION['message'] = displaySuccess('Task eliminato con successo');
            error_log("Task eliminato con successo: " . $id);
        } else {
            $_SESSION['message'] = displayError('Errore nell\'eliminazione del task: ' . $conn->error);
            error_log("Errore nell'eliminazione del task: " . $conn->error);
        }
        
        $deleteStmt->close();
    } else {
        $_SESSION['message'] = displayError('Task non trovato o non autorizzato');
        error_log("Task non trovato o non autorizzato: " . $id);
    }
    
    $checkStmt->close();
    
    // Redirect back to the task list con output buffer flush per evitare problemi
    ob_end_clean(); // Pulisce qualsiasi output precedente
    redirect('./index.php'); // Percorso relativo rispetto alla directory corrente
} else {
    // If no ID is provided, redirect to index
    error_log("Tentativo di eliminazione task senza ID fornito");
    $_SESSION['message'] = displayError('Nessun ID task fornito');
    ob_end_clean();
    redirect('./index.php');
}
?>
