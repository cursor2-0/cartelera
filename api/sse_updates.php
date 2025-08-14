<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Evitar que el script termine por timeout
set_time_limit(0);
ini_set('max_execution_time', 0);

require_once '../config/database.php';

// Función para enviar eventos SSE
function sendSSE($data, $event = 'message') {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Función para obtener el último timestamp de modificación
function getLastModified($db) {
    try {
        $query = "SELECT MAX(fecha_subida) as last_modified FROM documentos";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_modified'] ?? '1970-01-01 00:00:00';
    } catch (Exception $e) {
        return '1970-01-01 00:00:00';
    }
}

// Función para obtener el conteo de documentos
function getDocumentCount($db) {
    try {
        $query = "SELECT COUNT(*) as total FROM documentos";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener estado inicial
    $lastModified = getLastModified($db);
    $lastCount = getDocumentCount($db);
    
    // Enviar evento de conexión establecida
    sendSSE([
        'type' => 'connected',
        'message' => 'Conexión SSE establecida',
        'timestamp' => date('Y-m-d H:i:s')
    ], 'connected');
    
    // Loop principal para detectar cambios
    while (true) {
        // Verificar si la conexión sigue activa
        if (connection_aborted()) {
            break;
        }
        
        // Obtener estado actual
        $currentModified = getLastModified($db);
        $currentCount = getDocumentCount($db);
        
        // Verificar si hay cambios
        if ($currentModified !== $lastModified || $currentCount !== $lastCount) {
            // Determinar tipo de cambio
            $changeType = 'update';
            if ($currentCount > $lastCount) {
                $changeType = 'insert';
            } elseif ($currentCount < $lastCount) {
                $changeType = 'delete';
            }
            
            // Enviar evento de cambio
            sendSSE([
                'type' => 'document_change',
                'changeType' => $changeType,
                'timestamp' => $currentModified,
                'documentCount' => $currentCount,
                'message' => 'Documentos actualizados'
            ], 'update');
            
            // Actualizar estado
            $lastModified = $currentModified;
            $lastCount = $currentCount;
        }
        
        // Enviar heartbeat cada 30 segundos
        static $lastHeartbeat = 0;
        if (time() - $lastHeartbeat >= 30) {
            sendSSE([
                'type' => 'heartbeat',
                'timestamp' => date('Y-m-d H:i:s')
            ], 'heartbeat');
            $lastHeartbeat = time();
        }
        
        // Esperar 2 segundos antes de la siguiente verificación
        sleep(2);
    }
    
} catch (Exception $e) {
    sendSSE([
        'type' => 'error',
        'message' => 'Error en SSE: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], 'error');
}
?>