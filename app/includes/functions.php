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
?>
