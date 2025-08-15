<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="css/GP.ico" type="image/x-icon">
    <title>Cartelera Digital - Documentos Fiscales</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/responsive.css">

</head>
<body>
    <div class="header">
        <img src="css/logo.png" alt="Cartelera Fiscal Corporacion Teno C.A." class="logo">
    </div>

    <div class="grid-container" id="gridContainer">
        <!-- Las casillas se cargarán dinámicamente -->
    </div>

    <!-- Modal para visualizar documentos -->
    <div id="documentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="navigation">
                <button class="nav-btn" id="prevBtn" onclick="previousDocument()">⬅ Anterior</button>
                <span id="documentInfo">Documento 1 de 1</span>
                <button class="nav-btn" id="nextBtn" onclick="nextDocument()">Siguiente ➡</button>
                <button class="nav-btn" id="openNewTab" onclick="openInNewTab()" style="background: #27ae60;">🔗 Abrir en nueva pestaña</button>
            </div>
            <iframe id="documentViewer" class="documento-viewer"></iframe>
        </div>
    </div>

    <script>
        let currentCasilla = 0;
        let currentDocumentIndex = 0;
        let documents = [];
        let inactivityTimer;

        // Función para detectar si un archivo es una imagen
        function isImageFile(filename) {
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            const extension = filename.split('.').pop().toLowerCase();
            return imageExtensions.includes(extension);
        }

        // Cargar datos de la cartelera
        function loadCartelera() {
            // Primero cargar títulos de casillas, luego documentos
            loadCasillasTitles().then(() => {
                const timestamp = new Date().getTime();
                fetch(`api/get_documentos.php?t=${timestamp}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Datos recibidos:', data);
                        if (Array.isArray(data)) {
                            renderCasillas(data);
                        } else if (data.error) {
                            console.error('Error del servidor:', data.error);
                        } else {
                            console.error('Formato de datos inesperado:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar cartelera:', error);
                        // Mostrar mensaje de error al usuario
                        const container = document.getElementById('gridContainer');
                        container.innerHTML = '<div style="text-align: center; color: red; font-size: 18px;">Error al cargar los documentos. Verifique la conexión.</div>';
                    });
            });
        }

        // Variable global para almacenar títulos de casillas
        let casillasTitles = {};

        // Cargar títulos de casillas
        function loadCasillasTitles() {
            return fetch('api/get_casillas.php')
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data)) {
                        data.forEach(casilla => {
                            casillasTitles[casilla.numero] = casilla.titulo;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error cargando títulos de casillas:', error);
                    // Usar títulos por defecto si hay error
                    for (let i = 1; i <= 9; i++) {
                        casillasTitles[i] = `Casilla ${i}`;
                    }
                });
        }

        // Renderizar las 9 casillas
        function renderCasillas(data) {
            const container = document.getElementById('gridContainer');
            if (!container) {
                console.error('No se encontró el contenedor gridContainer');
                return;
            }
            
            container.innerHTML = '';

            for (let i = 1; i <= 9; i++) {
                const casilla = document.createElement('div');
                const documentosEnCasilla = data.filter(doc => doc && doc.casilla == i);
                
                if (documentosEnCasilla.length > 0) {
                    const primerDocumento = documentosEnCasilla[0];
                    
                    // Validar que el documento tenga los campos necesarios
                    if (!primerDocumento.archivo || !primerDocumento.titulo) {
                        console.warn(`Documento incompleto en casilla ${i}:`, primerDocumento);
                        continue;
                    }
                    
                    const esImagen = isImageFile(primerDocumento.archivo);
                    
                    casilla.className = 'casilla';
                    casilla.innerHTML = `
                        <div class="document-preview">
                            ${esImagen ? 
                                `<img src="uploads/${primerDocumento.archivo}" alt="${primerDocumento.titulo}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />` :
                                `<iframe src="uploads/${primerDocumento.archivo}" title="${primerDocumento.titulo}" onerror="console.error('Error cargando iframe:', this.src);"></iframe>`
                            }
                            <div class="preview-overlay">
                                <div class="play-icon">👁️</div>
                            </div>
                        </div>
                        <h3>${casillasTitles[i] || `Casilla ${i}`}</h3>
                        <div class="documento-count">${documentosEnCasilla.length} documento(s)</div>
                    `;
                    casilla.onclick = () => openCasilla(i, documentosEnCasilla);
                } else {
                    casilla.className = 'casilla empty-casilla';
                    casilla.innerHTML = `
                        <div class="icon">📄</div>
                        <h3>${casillasTitles[i] || `Casilla ${i}`}</h3>
                        <p>Sin documentos</p>
                    `;
                }
                
                container.appendChild(casilla);
            }
        }

        // Abrir casilla con documentos
        function openCasilla(casillaNum, docs) {
            currentCasilla = casillaNum;
            documents = docs;
            currentDocumentIndex = 0;
            showDocument();
            document.getElementById('documentModal').style.display = 'block';
            startInactivityTimer();
        }

        // Mostrar documento actual
        function showDocument() {
            if (documents.length > 0 && currentDocumentIndex < documents.length) {
                const doc = documents[currentDocumentIndex];
                
                // Validar que el documento existe y tiene archivo
                if (!doc || !doc.archivo) {
                    console.error('Documento inválido:', doc);
                    return;
                }
                
                const viewer = document.getElementById('documentViewer');
                const info = document.getElementById('documentInfo');
                
                if (viewer && info) {
                    let documentUrl = 'uploads/' + doc.archivo;
                    
                    // Si es un PDF, agregar parámetros para pantalla completa
                    if (doc.archivo.toLowerCase().endsWith('.pdf')) {
                        documentUrl += '#toolbar=0&navpanes=0&scrollbar=0&view=FitH';
                    }
                    
                    console.log('Cargando documento:', documentUrl);
                    
                    viewer.src = documentUrl;
                    info.textContent = 
                        `Documento ${currentDocumentIndex + 1} de ${documents.length} - ${doc.titulo || 'Sin título'}`;
                    
                    // Agregar manejo de eventos para el iframe
                    viewer.onload = function() {
                        console.log('Documento cargado exitosamente:', this.src);
                    };
                    
                    viewer.onerror = function() {
                        console.error('Error cargando documento:', this.src);
                        info.textContent += ' (Error al cargar)';
                    };
                    
                    // Timeout para detectar problemas de carga
                    setTimeout(() => {
                        if (!viewer.contentDocument && !viewer.contentWindow) {
                            console.warn('El documento puede no haberse cargado correctamente');
                        }
                    }, 3000);
                }
                
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');
                
                if (prevBtn && nextBtn) {
                    prevBtn.disabled = currentDocumentIndex === 0;
                    nextBtn.disabled = currentDocumentIndex === documents.length - 1;
                }
            }
        }

        // Navegación entre documentos
        function previousDocument() {
            if (currentDocumentIndex > 0) {
                currentDocumentIndex--;
                showDocument();
                resetInactivityTimer();
            }
        }

        function nextDocument() {
            if (currentDocumentIndex < documents.length - 1) {
                currentDocumentIndex++;
                showDocument();
                resetInactivityTimer();
            }
        }

        // Abrir documento en nueva pestaña
        function openInNewTab() {
            if (documents.length > 0 && currentDocumentIndex < documents.length) {
                const doc = documents[currentDocumentIndex];
                if (doc && doc.archivo) {
                    const documentUrl = 'uploads/' + doc.archivo;
                    console.log('Abriendo en nueva pestaña:', documentUrl);
                    window.open(documentUrl, '_blank');
                }
            }
        }

        // Cerrar modal
        function closeModal() {
            document.getElementById('documentModal').style.display = 'none';
            clearTimeout(inactivityTimer);
        }

        // Timer de inactividad (40 segundos)
        function startInactivityTimer() {
            inactivityTimer = setTimeout(() => {
                closeModal();
            }, 40000);
        }

        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            startInactivityTimer();
        }

        // Detectar actividad del usuario
        document.addEventListener('click', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);

        // Función de inicialización
        function initCartelera() {
            console.log('Inicializando cartelera...');
            
            // Verificar que los elementos necesarios existan
            const gridContainer = document.getElementById('gridContainer');
            const documentModal = document.getElementById('documentModal');
            
            if (!gridContainer) {
                console.error('Error: No se encontró el elemento gridContainer');
                return;
            }
            
            if (!documentModal) {
                console.error('Error: No se encontró el elemento documentModal');
                return;
            }
            
            // Cargar cartelera inicial
            loadCartelera();
            
            console.log('Cartelera inicializada correctamente');
        }

        // Variables para SSE
        let eventSource = null;
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 5;
        
        // Función para inicializar Server-Sent Events
        function initSSE() {
            if (eventSource) {
                eventSource.close();
            }
            
            console.log('Iniciando conexión SSE...');
            eventSource = new EventSource('api/sse_updates.php');
            
            eventSource.onopen = function(event) {
                console.log('Conexión SSE establecida');
                reconnectAttempts = 0;
                
                // Mostrar indicador de conexión
                showConnectionStatus('Conectado - Actualizaciones en tiempo real', 'success');
            };
            
            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    console.log('Evento SSE recibido:', data);
                    
                    if (data.type === 'document_change') {
                        console.log('Detectado cambio en documentos, recargando...');
                        loadCartelera();
                        showNotification('Documentos actualizados automáticamente', 'info');
                    }
                } catch (error) {
                    console.error('Error procesando evento SSE:', error);
                }
            };
            
            eventSource.addEventListener('connected', function(event) {
                const data = JSON.parse(event.data);
                console.log('SSE conectado:', data.message);
            });
            
            eventSource.addEventListener('update', function(event) {
                const data = JSON.parse(event.data);
                console.log('Actualización detectada:', data);
                loadCartelera();
                showNotification(`${data.message} (${data.changeType})`, 'success');
            });
            
            eventSource.addEventListener('heartbeat', function(event) {
                // Heartbeat silencioso para mantener la conexión
                console.log('SSE heartbeat recibido');
            });
            
            eventSource.addEventListener('error', function(event) {
                const data = JSON.parse(event.data);
                console.error('Error SSE:', data.message);
                showNotification('Error en actualizaciones automáticas', 'error');
            });
            
            eventSource.onerror = function(event) {
                console.error('Error en conexión SSE:', event);
                showConnectionStatus('Desconectado - Reintentando...', 'error');
                
                if (reconnectAttempts < maxReconnectAttempts) {
                    reconnectAttempts++;
                    console.log(`Reintentando conexión SSE (${reconnectAttempts}/${maxReconnectAttempts})...`);
                    setTimeout(() => {
                        initSSE();
                    }, 3000 * reconnectAttempts); // Incrementar delay
                } else {
                    console.error('Máximo de reintentos alcanzado para SSE');
                    showConnectionStatus('Sin actualizaciones automáticas', 'warning');
                }
            };
        }
        
        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 10000;
                opacity: 0;
                transition: opacity 0.3s ease;
                max-width: 300px;
            `;
            
            // Colores según tipo
            const colors = {
                success: '#27ae60',
                error: '#e74c3c',
                warning: '#f39c12',
                info: '#3498db'
            };
            notification.style.backgroundColor = colors[type] || colors.info;
            
            document.body.appendChild(notification);
            
            // Mostrar con animación
            setTimeout(() => {
                notification.style.opacity = '1';
            }, 100);
            
            // Ocultar después de 4 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }
        
        // Función para mostrar estado de conexión
        function showConnectionStatus(message, type) {
            let statusElement = document.getElementById('connection-status');
            if (!statusElement) {
                statusElement = document.createElement('div');
                statusElement.id = 'connection-status';
                statusElement.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    padding: 8px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    z-index: 9999;
                    transition: all 0.3s ease;
                `;
                document.body.appendChild(statusElement);
            }
            
            statusElement.textContent = message;
            
            const colors = {
                success: { bg: '#27ae60', text: 'white' },
                error: { bg: '#e74c3c', text: 'white' },
                warning: { bg: '#f39c12', text: 'white' }
            };
            
            const color = colors[type] || colors.success;
            statusElement.style.backgroundColor = color.bg;
            statusElement.style.color = color.text;
        }
        
        // Limpiar SSE al cerrar la página
        window.addEventListener('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
        });

        // Inicializar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initCartelera();
                // Iniciar SSE después de cargar la cartelera
                setTimeout(initSSE, 1000);
            });
        } else {
            initCartelera();
            setTimeout(initSSE, 1000);
        }
    </script>
</body>
</html>