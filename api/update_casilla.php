<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['numero']) || !isset($input['titulo'])) {
        echo json_encode(['error' => 'Número de casilla y título son requeridos']);
        exit();
    }
    
    $numero = (int)$input['numero'];
    $titulo = trim($input['titulo']);
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : null;
    
    if ($numero < 1 || $numero > 9) {
        echo json_encode(['error' => 'Número de casilla debe estar entre 1 y 9']);
        exit();
    }
    
    if (empty($titulo)) {
        echo json_encode(['error' => 'El título no puede estar vacío']);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Actualizar o insertar casilla
        $query = "
            INSERT INTO casillas (numero, titulo, descripcion, fecha_actualizacion) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (numero) 
            DO UPDATE SET 
                titulo = EXCLUDED.titulo,
                descripcion = EXCLUDED.descripcion,
                fecha_actualizacion = CURRENT_TIMESTAMP
        ";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$numero, $titulo, $descripcion]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Casilla actualizada exitosamente',
                'casilla' => [
                    'numero' => $numero,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion
                ]
            ]);
        } else {
            echo json_encode(['error' => 'Error al actualizar la casilla']);
        }
    } else {
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>