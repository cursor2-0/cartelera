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
        // Crear tabla si no existe
        $createTableQuery = "
            CREATE TABLE IF NOT EXISTS casillas (
                id SERIAL PRIMARY KEY,
                numero INTEGER UNIQUE NOT NULL,
                titulo VARCHAR(255) NOT NULL DEFAULT 'Casilla',
                descripcion TEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $db->exec($createTableQuery);
        
        $query = "SELECT numero, titulo, descripcion FROM casillas ORDER BY numero ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $casillas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $casillas[] = [
                'numero' => (int)$row['numero'],
                'titulo' => $row['titulo'],
                'descripcion' => $row['descripcion']
            ];
        }
        
        // Si no hay casillas, crear las por defecto
        if (empty($casillas)) {
            for ($i = 1; $i <= 9; $i++) {
                $insertQuery = "INSERT INTO casillas (numero, titulo) VALUES (?, ?) ON CONFLICT (numero) DO NOTHING";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([$i, "Casilla $i"]);
                
                $casillas[] = [
                    'numero' => $i,
                    'titulo' => "Casilla $i",
                    'descripcion' => null
                ];
            }
        }
        
        echo json_encode($casillas);
    } else {
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>