<?php
// Script para crear la tabla de casillas con títulos personalizados
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
        // Crear tabla de casillas si no existe
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
        
        $stmt = $db->prepare($createTableQuery);
        $stmt->execute();
        
        echo "<h3>✅ Tabla 'casillas' creada exitosamente</h3>";
        
        // Insertar casillas por defecto (1-9) si no existen
        for ($i = 1; $i <= 9; $i++) {
            $insertQuery = "
                INSERT INTO casillas (numero, titulo) 
                VALUES (?, ?) 
                ON CONFLICT (numero) DO NOTHING
            ";
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$i, "Casilla $i"]);
        }
        
        echo "<h3>✅ Casillas por defecto inicializadas</h3>";
        echo "<br><a href='dashboard.php'>Volver al Dashboard</a>";
        
    } else {
        echo "❌ Error de conexión a la base de datos";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Casillas - Cartelera Digital</title>
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
        <h1>⚙️ Configuración de Casillas</h1>
        <p>Este script configura la tabla de casillas para permitir títulos personalizados.</p>
        <hr>
    </div>
</body>
</html>