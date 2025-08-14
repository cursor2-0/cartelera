<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartelera Digital - Documentos Fiscales</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/modal.css">
    <link rel="stylesheet" href="css/responsive.css">

</head>
<body>
    <div class="header">
        <h1>Cartelera Fiscal Corporacion Teno C.A.</h1>
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

        // Inicializar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCartelera);
        } else {
            initCartelera();
        }
    </script>
</body>
</html>