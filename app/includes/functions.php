<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1;
}

/**
 * Redirect to specified page
 * @param string $location
 * @return void
 */
function redirect($location) {
    // Registriamo il reindirizzamento per debug
    error_log("Reindirizzamento a: $location");
    
    // Aggiungiamo header anti-cache per evitare problemi con la cache del browser
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Reindirizzamento
    header("Location: $location");
    exit;
}

/**
 * Clean data to prevent XSS attacks
 * @param mixed $data
 * @return mixed
 */
function cleanData($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Display error message
 * @param string $message
 * @return string
 */
function displayError($message) {
    return '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p>' . $message . '</p>
            </div>';
}

/**
 * Display success message
 * @param string $message
 * @return string
 */
function displaySuccess($message) {
    return '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p>' . $message . '</p>
            </div>';
}

/**
 * Upload file
 * @param array $file
 * @param string $destination
 * @return string|bool
 */
function uploadFile($file, $destination) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check if file extension is allowed
    if (!in_array($fileExtension, $allowedExtensions)) {
        return false;
    }
    
    // Create unique filename
    $newFilename = uniqid() . '.' . $fileExtension;
    $targetPath = $destination . $newFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $newFilename;
    } else {
        return false;
    }
}

/**
 * Get document type name
 * @param int $typology
 * @return string
 */
function getDocumentTypeName($typology) {
    switch ($typology) {
        case 1:
            return 'Libro';
        case 2:
            return 'Rivista';
        case 3:
            return 'Video';
        default:
            return 'Sconosciuto';
    }
}

/**
 * Get user role name
 * @param int $role
 * @return string
 */
function getUserRoleName($role) {
    switch ($role) {
        case 1:
            return 'Amministratore';
        case 2:
            return 'Editor';
        default:
            return 'Sconosciuto';
    }
}

/**
 * Get base URL for assets
 * @return string
 */
function getBaseUrl() {
    // Get the current script path
    $path = $_SERVER['PHP_SELF'];
    $parts = explode('/', $path);
    
    // Find the 'app' directory in the path
    $appIndex = array_search('app', $parts);
    
    if ($appIndex === false) {
        // Fallback if 'app' is not found in the path
        return '/';
    }
    
    // Calculate how many directories we need to go back to reach app root
    $depth = count($parts) - $appIndex - 2; // -2 to account for 'app/' itself and the filename
    if ($depth < 0) $depth = 0;
    
    // Generate the relative path back to the app root
    $basePath = '';
    for ($i = 0; $i < $depth; $i++) {
        $basePath .= '../';
    }
    
    // Ensure the path always ends with a slash for consistency
    if ($basePath !== '' && substr($basePath, -1) !== '/') {
        $basePath .= '/';
    }
    
    // Log debug information
    error_log("URL Debug - Path: $path, Parts: " . json_encode($parts) . ", AppIndex: $appIndex, Depth: $depth, BasePath: $basePath");
    
    return $basePath;
}

/**
 * Formatta una data per l'inserimento nel database
 * Se la data è vuota, restituisce NULL
 * Se la data è in formato valido, restituisce la data in formato Y-m-d
 * @param string $date La data da formattare
 * @return string|null La data formattata o NULL
 */
function formatDateForDB($date) {
    if(empty($date)) {
        return null;
    }
    
    // Verifica se la data è in formato valido
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if($dateObj && $dateObj->format('Y-m-d') === $date) {
        return $date;
    }
    
    // Prova altri formati comuni
    $formats = ['d/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];
    foreach($formats as $format) {
        $dateObj = DateTime::createFromFormat($format, $date);
        if($dateObj) {
            return $dateObj->format('Y-m-d');
        }
    }
    
    // Se arriva qui, la data non è in un formato riconosciuto
    error_log("Data non valida: $date");
    return null;
}
?>
