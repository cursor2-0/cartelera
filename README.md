# Cartelera Digital para Documentos Fiscales

Una aplicación web especializada para mostrar documentos fiscales de manera organizada y profesional en una cartelera digital con 9 casillas.

## Características

- **Cartelera Digital**: Pantalla optimizada para 1080x1920 con 9 casillas organizadas
- **Panel de Administración**: Interfaz responsive para gestionar documentos
- **Soporte Multimedia**: PDFs e imágenes (JPG, PNG, GIF)
- **Actualización Automática**: Detección automática de cambios
- **Navegación Intuitiva**: Navegación entre múltiples documentos por casilla
- **Timeout Automático**: Regreso automático al inicio después de 40 segundos de inactividad

## Requisitos del Sistema

- **Servidor Web**: Apache/Nginx con PHP 7.4+
- **Base de Datos**: PostgreSQL 12+
- **PHP Extensions**: PDO, pdo_pgsql
- **Opcional**: Imagick (para contar páginas de PDF)

## Instalación

### 1. Configurar Base de Datos

```sql
-- Crear base de datos
CREATE DATABASE cartelera;

-- Ejecutar el script db.sql
\i db.sql
```

### 2. Configurar Conexión

Editar `config/database.php` con sus credenciales:

```php
private $host = 'localhost';
private $db_name = 'cartelera';
private $username = 'su_usuario';
private $password = 'su_contraseña';
private $port = '5432';
```

### 3. Configuración Inicial

1. Acceder a `http://localhost/cartelera/setup.php`
2. Crear usuario administrador
3. Configurar permisos de carpeta `uploads/`

```bash
chmod 755 uploads/
```

### 4. Acceso a la Aplicación

- **Cartelera Pública**: `http://localhost/cartelera/`
- **Panel de Administración**: `http://localhost/cartelera/admin/`

## Estructura del Proyecto

```
cartelera/
├── config/
│   └── database.php          # Configuración de base de datos
├── admin/
│   ├── index.php            # Login del panel
│   ├── dashboard.php        # Panel principal
│   ├── add_documento.php    # Agregar documentos
│   ├── delete_documento.php # Eliminar documentos
│   └── logout.php          # Cerrar sesión
├── api/
│   └── get_documentos.php   # API para obtener documentos
├── uploads/                 # Archivos subidos
├── index.php               # Cartelera principal
├── setup.php               # Configuración inicial
├── db.sql                  # Script de base de datos
└── README.md               # Este archivo
```

## Uso

### Para Administradores

1. **Acceder al Panel**: Ir a `/admin/` e iniciar sesión
2. **Gestionar Documentos**: 
   - Agregar nuevos documentos
   - Organizar por casillas (1-9)
   - Editar información
   - Eliminar documentos
3. **Tipos de Archivo Soportados**:
   - PDF: Facturas, reportes, declaraciones
   - Imágenes: Escaneos, certificados, avisos

### Para Usuarios/Visitantes

1. **Ver Cartelera**: Acceder a la página principal
2. **Consultar Documentos**: Hacer clic en cualquier casilla
3. **Navegar**: Usar botones anterior/siguiente para múltiples documentos
4. **Regreso Automático**: La cartelera regresa al inicio tras 40 segundos de inactividad

## Funcionalidades Técnicas

### Actualización Automática
- Verificación cada 30 segundos
- Detección de cambios en tiempo real
- Sin necesidad de recargar manualmente

### Seguridad
- Autenticación de administradores
- Validación de tipos de archivo
- Protección contra subida de archivos maliciosos
- Sesiones seguras

### Optimización
- Carga asíncrona de documentos
- Interfaz responsive para administración
- Optimizado para pantallas 1080x1920

## Configuración Avanzada

### Límites de Archivo

Editar en `admin/add_documento.php`:

```php
// Cambiar límite de tamaño (actualmente 10MB)
if ($tamanoArchivo > 10 * 1024 * 1024) {
```

### Timeout de Inactividad

Editar en `index.php`:

```javascript
// Cambiar timeout (actualmente 40 segundos)
inactivityTimer = setTimeout(() => {
    closeModal();
}, 40000);
```

### Frecuencia de Actualización

Editar en `index.php`:

```javascript
// Cambiar intervalo (actualmente 30 segundos)
setInterval(loadCartelera, 30000);
```

## Solución de Problemas

### Error de Conexión a Base de Datos
- Verificar credenciales en `config/database.php`
- Asegurar que PostgreSQL esté ejecutándose
- Verificar que la base de datos 'cartelera' exista

### Archivos No Se Suben
- Verificar permisos de carpeta `uploads/`
- Revisar límites de PHP (`upload_max_filesize`, `post_max_size`)
- Verificar espacio en disco

### Cartelera No Se Actualiza
- Verificar conexión a internet
- Revisar consola del navegador para errores JavaScript
- Verificar que la API responda correctamente

## Soporte

Para soporte técnico o consultas:
- Revisar logs de error del servidor
- Verificar configuración de PHP y PostgreSQL
- Consultar documentación de XAMPP/servidor web

## Licencia

Este proyecto está desarrollado para uso interno de gestión de documentos fiscales.