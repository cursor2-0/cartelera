<?php
session_start();

// Verificar si está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

// Obtener documentos
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM documentos ORDER BY casilla ASC, fecha_subida DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Agrupar por casilla
$documentosPorCasilla = [];
for ($i = 1; $i <= 9; $i++) {
    $documentosPorCasilla[$i] = [];
}

foreach ($documentos as $doc) {
    $documentosPorCasilla[$doc['casilla']][] = $doc;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel de Administración</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 24px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .actions-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .casilla-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .casilla-header {
            background: #34495e;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .casilla-header h3 {
            font-size: 18px;
        }

        .document-count {
            background: #3498db;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }

        .casilla-content {
            padding: 20px;
        }

        .document-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .document-info h4 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .document-info p {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .document-actions {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .empty-casilla {
            text-align: center;
            color: #7f8c8d;
            padding: 40px 20px;
        }

        .empty-casilla .icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 30px;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            opacity: 0.7;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .actions-bar {
                flex-direction: column;
                gap: 15px;
            }

            .grid-container {
                grid-template-columns: 1fr;
            }

            .document-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Panel de Administración - Cartelera Digital</h1>
        <div class="header-actions">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="../index.php" class="btn btn-primary" target="_blank">Ver Cartelera</a>
            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>

    <div class="container">
        <div class="actions-bar">
            <h2>Gestión de Documentos</h2>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-success" onclick="openAddModal()">+ Agregar Documento</button>
                <a href="cleanup_documentos.php" class="btn btn-warning" onclick="return confirm('¿Estás seguro de que quieres limpiar los documentos huérfanos? Esta acción no se puede deshacer.')">🧹 Limpiar Huérfanos</a>
            </div>
        </div>

        <div class="grid-container">
            <?php for ($i = 1; $i <= 9; $i++): ?>
                <div class="casilla-card">
                    <div class="casilla-header">
                        <h3 id="casilla-titulo-<?php echo $i; ?>">Casilla <?php echo $i; ?></h3>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <button class="btn btn-warning btn-sm" onclick="editCasillaTitle(<?php echo $i; ?>)" title="Editar título">✏️</button>
                            <span class="document-count"><?php echo count($documentosPorCasilla[$i]); ?> docs</span>
                        </div>
                    </div>
                    <div class="casilla-content">
                        <?php if (empty($documentosPorCasilla[$i])): ?>
                            <div class="empty-casilla">
                                <div class="icon">📄</div>
                                <p>No hay documentos en esta casilla</p>
                                <button class="btn btn-primary btn-sm" onclick="openAddModal(<?php echo $i; ?>)">Agregar Documento</button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($documentosPorCasilla[$i] as $doc): ?>
                                <div class="document-item">
                                    <div class="document-info">
                                        <h4><?php echo htmlspecialchars($doc['titulo']); ?></h4>
                                        <p><?php echo htmlspecialchars($doc['descripcion']); ?></p>
                                        <p><strong>Archivo:</strong> <?php echo htmlspecialchars($doc['archivo']); ?></p>
                                        <p><strong>Subido:</strong> <?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></p>
                                    </div>
                                    <div class="document-actions">
                                        <a href="../uploads/<?php echo htmlspecialchars($doc['archivo']); ?>" class="btn btn-primary btn-sm" target="_blank">Ver</a>
                                        <button class="btn btn-warning btn-sm" onclick="editDocument(<?php echo $doc['id']; ?>)">Editar</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteDocument(<?php echo $doc['id']; ?>)">Eliminar</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <button class="btn btn-primary btn-sm" onclick="openAddModal(<?php echo $i; ?>)">+ Agregar más</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Modal para agregar/editar documento -->
    <div id="documentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Agregar Documento</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="documentForm" enctype="multipart/form-data">
                    <input type="hidden" id="documentId" name="id">
                    
                    <div class="form-group">
                        <label for="titulo">Título:</label>
                        <input type="text" id="titulo" name="titulo" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="casilla">Casilla:</label>
                        <select id="casilla" name="casilla" required>
                            <option value="">Seleccionar casilla</option>
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                                <option value="<?php echo $i; ?>">Casilla <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="archivo">Archivo (PDF o Imagen):</label>
                        <input type="file" id="archivo" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.gif">
                        <small>Formatos permitidos: PDF, JPG, PNG, GIF. Máximo 10MB.</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Guardar Documento</button>
                        <button type="button" class="btn btn-danger" onclick="closeModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let isEditing = false;

        function openAddModal(casillaNum = null) {
            isEditing = false;
            document.getElementById('modalTitle').textContent = 'Agregar Documento';
            document.getElementById('documentForm').reset();
            document.getElementById('documentId').value = '';
            
            if (casillaNum) {
                document.getElementById('casilla').value = casillaNum;
            }
            
            document.getElementById('documentModal').style.display = 'block';
        }

        function editDocument(id) {
            // Aquí implementarías la lógica para cargar los datos del documento
            isEditing = true;
            document.getElementById('modalTitle').textContent = 'Editar Documento';
            document.getElementById('documentId').value = id;
            document.getElementById('documentModal').style.display = 'block';
        }

        function deleteDocument(id) {
            if (confirm('¿Está seguro de que desea eliminar este documento?')) {
                fetch('delete_documento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: id})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar el documento: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el documento');
                });
            }
        }

        function closeModal() {
            document.getElementById('documentModal').style.display = 'none';
        }

        // Manejar envío del formulario
        document.getElementById('documentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = isEditing ? 'update_documento.php' : 'add_documento.php';
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            });
        });

        // Cargar títulos de casillas
        function loadCasillasTitles() {
            fetch('../api/get_casillas.php')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(casilla => {
                            const titleElement = document.getElementById(`casilla-titulo-${casilla.numero}`);
                            if (titleElement) {
                                titleElement.textContent = casilla.titulo;
                            }
                        });
                    }
                })
                .catch(error => console.error('Error cargando títulos:', error));
        }

        // Editar título de casilla
        function editCasillaTitle(numero) {
            const currentTitle = document.getElementById(`casilla-titulo-${numero}`).textContent;
            const newTitle = prompt('Ingrese el nuevo título para la casilla:', currentTitle);
            
            if (newTitle && newTitle.trim() !== '' && newTitle !== currentTitle) {
                fetch('../api/update_casilla.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        numero: numero,
                        titulo: newTitle.trim()
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`casilla-titulo-${numero}`).textContent = data.casilla.titulo;
                        alert('Título actualizado exitosamente');
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al actualizar el título');
                });
            }
        }

        // Cargar títulos al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadCasillasTitles();
        });

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('documentModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>