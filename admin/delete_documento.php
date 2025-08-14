<?php
session_start();
header('Content-Type: application/json');

// Verificar si está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once '../config/database.php';

try {
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de documento inválido']);
        exit();
    }
    
    $documentoId = (int)$input['id'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener información del documento antes de eliminarlo
    $query = "SELECT archivo FROM documentos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $documentoId);
    $stmt->execute();
    
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$documento) {
        echo json_encode(['success' => false, 'message' => 'Documento no encontrado']);
        exit();
    }
    
    // Eliminar de la base de datos
    $deleteQuery = "DELETE FROM documentos WHERE id = :id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $documentoId);
    
    if ($deleteStmt->execute()) {
        // Eliminar archivo físico
        $rutaArchivo = '../uploads/' . $documento['archivo'];
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Documento eliminado exitosamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el documento de la base de datos']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>