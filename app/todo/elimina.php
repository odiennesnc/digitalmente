<?php
// filepath: /Users/Odn/Documents/Lavori O(n)/digitalmente/app/todo/elimina.php
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Check if ID is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $userId = $_SESSION['user_id'];
    
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
        } else {
            $_SESSION['message'] = displayError('Errore nell\'eliminazione del task');
        }
        
        $deleteStmt->close();
    } else {
        $_SESSION['message'] = displayError('Task non trovato o non autorizzato');
    }
    
    $checkStmt->close();
    
    // Redirect back to the task list
    redirect('index.php');
} else {
    // If no ID is provided, redirect to index
    redirect('index.php');
}
?>
