-- Script para crear la tabla de casillas
CREATE TABLE IF NOT EXISTS casillas (
    id SERIAL PRIMARY KEY,
    numero INTEGER UNIQUE NOT NULL,
    titulo VARCHAR(255) NOT NULL DEFAULT 'Casilla',
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar casillas por defecto (1-9)
INSERT INTO casillas (numero, titulo) VALUES 
(1, 'Casilla 1'),
(2, 'Casilla 2'),
(3, 'Casilla 3'),
(4, 'Casilla 4'),
(5, 'Casilla 5'),
(6, 'Casilla 6'),
(7, 'Casilla 7'),
(8, 'Casilla 8'),
(9, 'Casilla 9')
ON CONFLICT (numero) DO NOTHING;

-- Verificar que se crearon correctamente
SELECT * FROM casillas ORDER BY numero;