<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        $query = "SELECT id, titulo, descripcion, archivo, casilla, paginas, fecha_subida 
                 FROM documentos 
                 ORDER BY casilla ASC, fecha_subida DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $documentos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Verificar que el archivo existe físicamente
            $filePath = '../uploads/' . $row['archivo'];
            if (file_exists($filePath)) {
                $documentos[] = [
                    'id' => $row['id'],
                    'titulo' => $row['titulo'],
                    'descripcion' => $row['descripcion'],
                    'archivo' => $row['archivo'],
                    'casilla' => (int)$row['casilla'],
                    'paginas' => (int)$row['paginas'],
                    'fecha_subida' => $row['fecha_subida']
                ];
            }
        }
        
        echo json_encode($documentos);
    } else {
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>