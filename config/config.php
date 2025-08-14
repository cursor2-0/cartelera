<?php
// Configuración general de la aplicación

// Configuración de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Configuración de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 si usa HTTPS

// Configuración de subida de archivos
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Zona horaria
date_default_timezone_set('America/Caracas');

// Configuraciones de la aplicación
define('APP_NAME', 'Cartelera Digital');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'image/jpeg',
    'image/jpg', 
    'image/png',
    'image/gif'
]);

// Configuración de la cartelera
define('CASILLAS_TOTAL', 9);
define('AUTO_REFRESH_INTERVAL', 30000); // 30 segundos en milisegundos
define('INACTIVITY_TIMEOUT', 40000); // 40 segundos en milisegundos

// Configuración de seguridad
define('SESSION_TIMEOUT', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// Funciones de utilidad
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . strtolower($extension);
}

function isValidFileType($mimeType) {
    return in_array($mimeType, ALLOWED_MIME_TYPES);
}

function isValidFileSize($size) {
    return $size <= MAX_FILE_SIZE && $size > 0;
}

function logError($message) {
    $logDir = __DIR__ . '/../logs/';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logDir . 'error.log', $logMessage, FILE_APPEND | LOCK_EX);
}

function logActivity($action, $details = '') {
    $logDir = __DIR__ . '/../logs/';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user = $_SESSION['admin_username'] ?? 'Sistema';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $logMessage = "[$timestamp] Usuario: $user | IP: $ip | Acción: $action | Detalles: $details" . PHP_EOL;
    file_put_contents($logDir . 'activity.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Función para verificar sesión activa
function checkSession() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Verificar timeout de sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Función para limpiar archivos antiguos
function cleanupOldFiles($days = 30) {
    $uploadDir = UPLOAD_DIR;
    $files = glob($uploadDir . '*');
    $cutoff = time() - ($days * 24 * 60 * 60);
    
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $cutoff) {
            // Verificar si el archivo está en la base de datos antes de eliminar
            $filename = basename($file);
            // Esta verificación se haría con una consulta a la base de datos
            // Por ahora, comentamos la eliminación automática
            // unlink($file);
        }
    }
}

// Configuración de headers de seguridad
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Aplicar headers de seguridad
setSecurityHeaders();
?>