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
    $database = new Database();
    $db = $database->getConnection();
    
    // Validar datos requeridos
    if (empty($_POST['titulo']) || empty($_POST['casilla'])) {
        echo json_encode(['success' => false, 'message' => 'Título y casilla son requeridos']);
        exit();
    }
    
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $casilla = (int)$_POST['casilla'];
    
    // Validar casilla
    if ($casilla < 1 || $casilla > 9) {
        echo json_encode(['success' => false, 'message' => 'Casilla debe estar entre 1 y 9']);
        exit();
    }
    
    // Manejar archivo subido
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un archivo']);
        exit();
    }
    
    $archivo = $_FILES['archivo'];
    $nombreOriginal = $archivo['name'];
    $tipoArchivo = $archivo['type'];
    $tamanoArchivo = $archivo['size'];
    $archivoTemporal = $archivo['tmp_name'];
    
    // Validar tipo de archivo
    $tiposPermitidos = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($tipoArchivo, $tiposPermitidos)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo PDF e imágenes.']);
        exit();
    }
    
    // Validar tamaño (10MB máximo)
    if ($tamanoArchivo > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 10MB.']);
        exit();
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
    $nombreArchivo = uniqid() . '_' . time() . '.' . $extension;
    
    // Crear directorio uploads si no existe
    $directorioUploads = '../uploads/';
    if (!file_exists($directorioUploads)) {
        mkdir($directorioUploads, 0755, true);
    }
    
    $rutaDestino = $directorioUploads . $nombreArchivo;
    
    // Mover archivo
    if (!move_uploaded_file($archivoTemporal, $rutaDestino)) {
        echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
        exit();
    }
    
    // Contar páginas si es PDF
    $paginas = 1;
    if ($tipoArchivo === 'application/pdf') {
        // Intentar contar páginas del PDF (requiere extensión imagick o similar)
        try {
            if (class_exists('Imagick')) {
                $imagick = new Imagick($rutaDestino);
                $paginas = $imagick->getNumberImages();
                $imagick->clear();
            }
        } catch (Exception $e) {
            // Si no se puede contar, dejar en 1
            $paginas = 1;
        }
    }
    
    // Insertar en base de datos
    $query = "INSERT INTO documentos (titulo, descripcion, archivo, casilla, paginas) 
             VALUES (:titulo, :descripcion, :archivo, :casilla, :paginas)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':archivo', $nombreArchivo);
    $stmt->bindParam(':casilla', $casilla);
    $stmt->bindParam(':paginas', $paginas);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Documento agregado exitosamente',
            'id' => $db->lastInsertId()
        ]);
    } else {
        // Eliminar archivo si falla la inserción
        unlink($rutaDestino);
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
    }
    
} catch (Exception $e) {
    // Eliminar archivo si existe y hay error
    if (isset($rutaDestino) && file_exists($rutaDestino)) {
        unlink($rutaDestino);
    }
    
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}
?>