<?php
// Pagina di selezione della tipologia di documento da inserire
require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../includes/document_manager.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('../login.php');
}

// Get document types
$tipi_documento = getDocumentTypes();

include '../includes/header.php';
?>

<div class="container px-6 mx-auto grid">
    <h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
        Seleziona Tipologia Documento da Inserire
    </h2>
    
    <div class="grid gap-6 mb-8 md:grid-cols-3">
        <!-- Libro -->
        <a href="aggiungi_libro.php" class="block p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-xl font-medium text-gray-600 dark:text-gray-400">
                        Libro
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Inserisci un nuovo libro nel sistema
                    </p>
                </div>
            </div>
        </a>

        <!-- Rivista -->
        <a href="aggiungi_rivista.php" class="block p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-xl font-medium text-gray-600 dark:text-gray-400">
                        Rivista
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Inserisci una nuova rivista nel sistema
                    </p>
                </div>
            </div>
        </a>

        <!-- Video-Documentario -->
        <a href="aggiungi_video.php" class="block p-6 bg-white rounded-lg shadow-md dark:bg-gray-800 hover:shadow-lg transition-shadow">
            <div class="flex items-center">
                <div class="p-3 mr-4 text-purple-500 bg-purple-100 rounded-full dark:text-purple-100 dark:bg-purple-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-xl font-medium text-gray-600 dark:text-gray-400">
                        Video-Documentario
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Inserisci un nuovo video o documentario nel sistema
                    </p>
                </div>
            </div>
        </a>
    </div>
    
    <div class="mt-6">
        <a href="index.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Torna all'elenco dei documenti
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
