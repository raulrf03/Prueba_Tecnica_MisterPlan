-- Active: 1792256761628@@127.0.0.1@3306@reservas
-- ==================================================
-- PROYECTO: RESERVAS DE ALOJAMIENTO
-- Creación de estructura y datos de ejemplo
-- ==================================================

-- BLOQUE 1: Crear y seleccionar la base de datos

CREATE DATABASE IF NOT EXISTS reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE reservas;

-- BLOQUE 2: Crear tablas

-- Tabla reserva: almacena cada reserva con sus datos (huésped, fechas, importe, estado)

CREATE TABLE IF NOT EXISTS reserva (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_huesped VARCHAR(120) NOT NULL,
    email_huesped VARCHAR(180) NOT NULL,
    alojamiento VARCHAR(120) NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    num_personas TINYINT UNSIGNED NOT NULL,
    importe_total DECIMAL(10, 2) NOT NULL,
    estado ENUM(
        'pendiente',
        'confirmada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente',
    notas TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_reserva_fechas CHECK (fecha_salida > fecha_entrada),
    CONSTRAINT chk_reserva_personas CHECK (num_personas > 0),
    CONSTRAINT chk_reserva_importe CHECK (importe_total >= 0),
    INDEX idx_reserva_estado (estado),
    INDEX idx_reserva_fechas (fecha_entrada, fecha_salida),
    INDEX idx_reserva_huesped (nombre_huesped)
) ENGINE = InnoDB;

-- Tabla de reserva_evento: registra el historial de cambios (creación, confirmación, cancelación, modificación) de cada reserva

CREATE TABLE IF NOT EXISTS reserva_evento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT UNSIGNED NOT NULL,
    tipo_evento ENUM(
        'creacion',
        'confirmacion',
        'cancelacion',
        'modificacion'
    ) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_evento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_evento_reserva FOREIGN KEY (reserva_id) REFERENCES reserva (id) ON DELETE CASCADE,
    INDEX idx_reserva_evento_reserva (reserva_id),
    INDEX idx_reserva_evento_fecha (fecha_evento)
) ENGINE = InnoDB;

-- BLOQUE 3: Insertar datos de ejemplo
-- Los identificadores son fijos para que este archivo pueda ejecutarse varias veces sin duplicar los datos de ejemplo.

-- Datos de ejemplo para la tabla reserva
INSERT INTO
    reserva (
        id,
        nombre_huesped,
        email_huesped,
        alojamiento,
        fecha_entrada,
        fecha_salida,
        num_personas,
        importe_total,
        estado,
        notas,
        fecha_creacion
    )
SELECT 1, 'Laura Gómez', 'laura.gomez@example.com', 'Apartamento Vista Mar', '2026-11-10', '2026-11-15', 2, 450.00, 'pendiente', 'Solicita cuna para bebé.', '2026-10-01 09:15:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva
        WHERE
            id = 1
    );

INSERT INTO
    reserva (
        id,
        nombre_huesped,
        email_huesped,
        alojamiento,
        fecha_entrada,
        fecha_salida,
        num_personas,
        importe_total,
        estado,
        notas,
        fecha_creacion
    )
SELECT 2, 'Carlos Ruiz', 'carlos.ruiz@example.com', 'Casa Rural El Roble', '2026-12-20', '2026-12-27', 4, 780.00, 'confirmada', NULL, '2026-10-02 11:30:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva
        WHERE
            id = 2
    );

INSERT INTO
    reserva (
        id,
        nombre_huesped,
        email_huesped,
        alojamiento,
        fecha_entrada,
        fecha_salida,
        num_personas,
        importe_total,
        estado,
        notas,
        fecha_creacion
    )
SELECT 3, 'Marta López', 'marta.lopez@example.com', 'Estudio Centro Histórico', '2026-10-05', '2026-10-08', 2, 210.00, 'cancelada', 'Cancelada por cambio de planes.', '2026-09-25 17:45:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva
        WHERE
            id = 3
    );

INSERT INTO
    reserva (
        id,
        nombre_huesped,
        email_huesped,
        alojamiento,
        fecha_entrada,
        fecha_salida,
        num_personas,
        importe_total,
        estado,
        notas,
        fecha_creacion
    )
SELECT 4, 'Javier Martín', 'javier.martin@example.com', 'Apartamento Vista Mar', '2026-10-12', '2026-10-18', 3, 540.00, 'confirmada', NULL, '2026-09-20 08:00:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva
        WHERE
            id = 4
    );

INSERT INTO
    reserva (
        id,
        nombre_huesped,
        email_huesped,
        alojamiento,
        fecha_entrada,
        fecha_salida,
        num_personas,
        importe_total,
        estado,
        notas,
        fecha_creacion
    )
SELECT 5, 'Elena Navarro', 'elena.navarro@example.com', 'Casa Rural El Roble', '2027-01-08', '2027-01-12', 2, 320.00, 'pendiente', 'Llegada prevista por la tarde.', '2026-10-10 19:20:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva
        WHERE
            id = 5
    );

INSERT INTO
    reserva (
        id,
        nombre_huesped,
        email_huesped,
        alojamiento,
        fecha_entrada,
        fecha_salida,
        num_personas,
        importe_total,
        estado,
        notas,
        fecha_creacion
    )
SELECT 6, 'Sergio Alonso', 'sergio.alonso@example.com', 'Estudio Centro Histórico', '2026-09-01', '2026-09-04', 1, 150.00, 'cancelada', NULL, '2026-08-15 12:10:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva
        WHERE
            id = 6
    );

-- Datos de ejemplo para la tabla reserva_evento
INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 1, 1, 'creacion', 'Reserva creada por Laura Gómez.', '2026-10-01 09:15:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 1
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 2, 2, 'creacion', 'Reserva creada por Carlos Ruiz.', '2026-10-02 11:30:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 2
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 3, 2, 'confirmacion', 'Reserva confirmada por el alojamiento.', '2026-10-03 10:00:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 3
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 4, 3, 'creacion', 'Reserva creada por Marta López.', '2026-09-25 17:45:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 4
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 5, 3, 'cancelacion', 'Reserva cancelada por el huésped.', '2026-09-28 14:30:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 5
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 6, 4, 'creacion', 'Reserva creada por Javier Martín.', '2026-09-20 08:00:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 6
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 7, 4, 'confirmacion', 'Reserva confirmada por el alojamiento.', '2026-09-21 09:30:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 7
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 8, 5, 'creacion', 'Reserva creada por Elena Navarro.', '2026-10-10 19:20:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 8
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 9, 6, 'creacion', 'Reserva creada por Sergio Alonso.', '2026-08-15 12:10:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 9
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo_evento,
        descripcion,
        fecha_evento
    )
SELECT 10, 6, 'cancelacion', 'Reserva cancelada por el huésped.', '2026-08-18 16:00:00'
WHERE
    NOT EXISTS (
        SELECT 1
        FROM reserva_evento
        WHERE
            id = 10
    );

-- Apartado de pruebas, comandos temporales para comprobar que todo funciona
SELECT DATABASE();

SHOW TABLES;

DESCRIBE reserva;

DESCRIBE reserva_evento;

SELECT * FROM reserva;

SELECT * FROM reserva_evento;

SELECT r.id, r.nombre_huesped, r.estado, COUNT(e.id) AS total_eventos
FROM reserva r
    LEFT JOIN reserva_evento e ON e.reserva_id = r.id
GROUP BY
    r.id,
    r.nombre_huesped,
    r.estado
ORDER BY r.id;

-- Creacion de un usuario específico para la aplicación de reservas con permisos limitados
CREATE USER IF NOT EXISTS 'reservas_app' @'localhost' IDENTIFIED BY 'pruebas';

GRANT
SELECT,
INSERT
,
UPDATE ON reservas.* TO 'reservas_app' @'localhost';

FLUSH PRIVILEGES;

-- Para recibir el resultado deseado hay que cambiar la conexión activa
SELECT DATABASE(), CURRENT_USER();