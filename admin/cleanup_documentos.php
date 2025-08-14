<?php
// Script para limpiar documentos huérfanos (registros sin archivo físico)
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        // Obtener todos los documentos de la base de datos
        $query = "SELECT id, archivo FROM documentos";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $documentosEliminados = 0;
        $documentosRevisados = 0;
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $documentosRevisados++;
            $filePath = '../uploads/' . $row['archivo'];
            
            // Si el archivo no existe, eliminar el registro de la base de datos
            if (!file_exists($filePath)) {
                $deleteQuery = "DELETE FROM documentos WHERE id = ?";
                $deleteStmt = $db->prepare($deleteQuery);
                $deleteStmt->execute([$row['id']]);
                $documentosEliminados++;
                
                echo "Eliminado registro huérfano: {$row['archivo']}<br>";
            }
        }
        
        echo "<h3>Limpieza completada:</h3>";
        echo "Documentos revisados: {$documentosRevisados}<br>";
        echo "Registros huérfanos eliminados: {$documentosEliminados}<br>";
        echo "<br><a href='dashboard.php'>Volver al Dashboard</a>";
        
    } else {
        echo "Error de conexión a la base de datos";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpieza de Documentos - Cartelera Digital</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f4f4f4;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
        }
        a {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        a:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Limpieza de Documentos Huérfanos</h1>
        <p>Este script elimina de la base de datos los registros de documentos cuyos archivos físicos no existen.</p>
        <hr>
    </div>
</body>
</html>