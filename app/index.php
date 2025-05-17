<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Get latest documents
$stmt = $conn->prepare("SELECT d.id, d.titolo, d.tipologia_doc, d.autore, d.editore, a.argomento 
                      FROM documenti d 
                      LEFT JOIN argomenti a ON d.argomenti_id = a.id 
                      ORDER BY d.data_inserimento DESC LIMIT 5");
$stmt->execute();
$latest_docs = $stmt->get_result();

// Get todo items for the current user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM todo WHERE utente_id = ? AND completato = 0 ORDER BY data_scadenza ASC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$todos = $stmt->get_result();

include 'includes/header.php';
?>

<!-- Dashboard header -->
<h2 class="my-6 text-2xl font-semibold text-gray-700 dark:text-gray-200">
    Dashboard
</h2>

<!-- Cards -->
<div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <!-- Document Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-orange-500 bg-orange-100 rounded-full dark:text-orange-100 dark:bg-orange-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM documenti");
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['total'];
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Documenti Totali
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
    <!-- Topics Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-green-500 bg-green-100 rounded-full dark:text-green-100 dark:bg-green-500">
            <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor">
                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM argomenti");
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['total'];
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Argomenti
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
    <!-- Todo Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-blue-500 bg-blue-100 rounded-full dark:text-blue-100 dark:bg-blue-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM todo WHERE utente_id = ? AND completato = 0");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['total'];
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Todo Attivi
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
    <!-- Users Card -->
    <div class="flex items-center p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <div class="p-3 mr-4 text-teal-500 bg-teal-100 rounded-full dark:text-teal-100 dark:bg-teal-500">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
            </svg>
        </div>
        <div>
            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM utenti");
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc()['total'];
            ?>
            <p class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Utenti
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                <?php echo $count; ?>
            </p>
        </div>
    </div>
</div>

<!-- Latest documents and Todo section -->
<div class="grid gap-6 mb-8 md:grid-cols-2">
    <!-- Latest Documents -->
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
            Ultimi documenti inseriti
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Titolo</th>
                        <th class="px-4 py-3">Autore</th>
                        <th class="px-4 py-3">Argomento</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                    <?php if ($latest_docs->num_rows > 0) : ?>
                        <?php while ($doc = $latest_docs->fetch_assoc()) : ?>
                            <tr class="text-gray-700 dark:text-gray-400">
                                <td class="px-4 py-3 text-sm">
                                    <?php echo getDocumentTypeName($doc['tipologia_doc']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($doc['titolo']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($doc['autore']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($doc['argomento']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td colspan="4" class="px-4 py-3 text-sm text-center">
                                Nessun documento trovato
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="documenti/index.php" class="px-4 py-2 text-sm text-white bg-purple-600 rounded-lg">
                Vedi tutti
            </a>
        </div>
    </div>
    
    <!-- Todo List -->
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs dark:bg-gray-800">
        <h4 class="mb-4 font-semibold text-gray-800 dark:text-gray-300">
            Todo List
        </h4>
        <div class="overflow-x-auto">
            <table class="w-full whitespace-no-wrap">
                <thead>
                    <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                        <th class="px-4 py-3">Task</th>
                        <th class="px-4 py-3">Scadenza</th>
                        <th class="px-4 py-3">Stato</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y dark:divide-gray-700 dark:bg-gray-800">
                    <?php if ($todos->num_rows > 0) : ?>
                        <?php while ($todo = $todos->fetch_assoc()) : ?>
                            <tr class="text-gray-700 dark:text-gray-400">
                                <td class="px-4 py-3 text-sm">
                                    <?php echo htmlspecialchars($todo['task']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php echo $todo['data_scadenza']; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if ($todo['completato']) : ?>
                                        <span class="px-2 py-1 font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100">
                                            Completato
                                        </span>
                                    <?php else : ?>
                                        <span class="px-2 py-1 font-semibold leading-tight text-orange-700 bg-orange-100 rounded-full dark:bg-orange-700 dark:text-orange-100">
                                            In corso
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr class="text-gray-700 dark:text-gray-400">
                            <td colspan="3" class="px-4 py-3 text-sm text-center">
                                Nessun todo trovato
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-right">
            <a href="todo/index.php" class="px-4 py-2 text-sm text-white bg-purple-600 rounded-lg">
                Vedi tutti
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
