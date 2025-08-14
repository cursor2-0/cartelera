// Ejemplo de modelo de tabla para cartelera y usuarios
// Ejecuta estas sentencias en tu base de datos PostgreSQL

-- Tabla de usuarios administradores
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL
);

-- Tabla de documentos de la cartelera
CREATE TABLE IF NOT EXISTS documentos (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    archivo VARCHAR(255) NOT NULL,
    casilla INTEGER NOT NULL CHECK (casilla BETWEEN 1 AND 9),
    paginas INTEGER DEFAULT 1,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
