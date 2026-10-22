-- Active: 1792171317899@@127.0.0.1@3306@mysql
-- ==================================================
-- PROYECTO: RESERVAS DE ALOJAMIENTO
-- Estructura y datos de ejemplo listos para importar
-- ==================================================

CREATE DATABASE IF NOT EXISTS reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE reservas;

-- --------------------------------------------------
-- Tabla reserva
-- --------------------------------------------------

CREATE TABLE IF NOT EXISTS reserva (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_huesped VARCHAR(120) NOT NULL,
    email_huesped VARCHAR(180) NOT NULL,
    nombre_alojamiento VARCHAR(120) NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    num_personas TINYINT UNSIGNED NOT NULL,
    importe DECIMAL(10, 2) NOT NULL,
    estado ENUM(
        'pendiente',
        'confirmada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente',
    notas TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_reserva_fechas CHECK (fecha_salida > fecha_entrada),
    CONSTRAINT chk_reserva_personas CHECK (num_personas > 0),
    CONSTRAINT chk_reserva_importe CHECK (importe >= 0),
    INDEX idx_reserva_estado (estado),
    INDEX idx_reserva_fechas (fecha_entrada, fecha_salida),
    INDEX idx_reserva_huesped (nombre_huesped)
) ENGINE = InnoDB;

-- --------------------------------------------------
-- Tabla reserva_evento
-- --------------------------------------------------

CREATE TABLE IF NOT EXISTS reserva_evento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT UNSIGNED NOT NULL,
    tipo ENUM(
        'creacion',
        'confirmacion',
        'cancelacion',
        'modificacion'
    ) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserva_evento_reserva FOREIGN KEY (reserva_id) REFERENCES reserva (id) ON DELETE CASCADE,
    INDEX idx_reserva_evento_reserva (reserva_id),
    INDEX idx_reserva_evento_fecha (fecha_creacion)
) ENGINE = InnoDB;

-- --------------------------------------------------
-- Datos de ejemplo
-- --------------------------------------------------

INSERT INTO
    reserva (
        id,
        nombre_huesped,
        email_huesped,
        nombre_alojamiento,
        fecha_entrada,
        fecha_salida,
        num_personas,
        importe,
        estado,
        notas,
        fecha_creacion
    )
VALUES (
        1,
        'Laura Gómez',
        'laura.gomez@example.com',
        'Apartamento Vista Mar',
        '2026-11-10',
        '2026-11-15',
        2,
        450.00,
        'pendiente',
        'Solicita cuna para bebé.',
        '2026-10-01 09:15:00'
    ),
    (
        2,
        'Carlos Ruiz',
        'carlos.ruiz@example.com',
        'Casa Rural El Roble',
        '2026-12-20',
        '2026-12-27',
        4,
        780.00,
        'confirmada',
        NULL,
        '2026-10-02 11:30:00'
    ),
    (
        3,
        'Marta López',
        'marta.lopez@example.com',
        'Estudio Centro Histórico',
        '2026-10-05',
        '2026-10-08',
        2,
        210.00,
        'cancelada',
        'Cancelada por cambio de planes.',
        '2026-09-25 17:45:00'
    ),
    (
        4,
        'Javier Martín',
        'javier.martin@example.com',
        'Apartamento Vista Mar',
        '2026-10-12',
        '2026-10-18',
        3,
        540.00,
        'confirmada',
        NULL,
        '2026-09-20 08:00:00'
    ),
    (
        5,
        'Elena Navarro',
        'elena.navarro@example.com',
        'Casa Rural El Roble',
        '2027-01-08',
        '2027-01-12',
        2,
        320.00,
        'pendiente',
        'Llegada prevista por la tarde.',
        '2026-10-10 19:20:00'
    ),
    (
        6,
        'Sergio Alonso',
        'sergio.alonso@example.com',
        'Estudio Centro Histórico',
        '2026-09-01',
        '2026-09-04',
        1,
        150.00,
        'cancelada',
        NULL,
        '2026-08-15 12:10:00'
    );

INSERT INTO
    reserva_evento (
        id,
        reserva_id,
        tipo,
        descripcion,
        fecha_creacion
    )
VALUES (
        1,
        1,
        'creacion',
        'Reserva creada por Laura Gómez.',
        '2026-10-01 09:15:00'
    ),
    (
        2,
        2,
        'creacion',
        'Reserva creada por Carlos Ruiz.',
        '2026-10-02 11:30:00'
    ),
    (
        3,
        2,
        'confirmacion',
        'Reserva confirmada por el alojamiento.',
        '2026-10-03 10:00:00'
    ),
    (
        4,
        3,
        'creacion',
        'Reserva creada por Marta López.',
        '2026-09-25 17:45:00'
    ),
    (
        5,
        3,
        'cancelacion',
        'Reserva cancelada por el huésped.',
        '2026-09-28 14:30:00'
    ),
    (
        6,
        4,
        'creacion',
        'Reserva creada por Javier Martín.',
        '2026-09-20 08:00:00'
    ),
    (
        7,
        4,
        'confirmacion',
        'Reserva confirmada por el alojamiento.',
        '2026-09-21 09:30:00'
    ),
    (
        8,
        5,
        'creacion',
        'Reserva creada por Elena Navarro.',
        '2026-10-10 19:20:00'
    ),
    (
        9,
        6,
        'creacion',
        'Reserva creada por Sergio Alonso.',
        '2026-08-15 12:10:00'
    ),
    (
        10,
        6,
        'cancelacion',
        'Reserva cancelada por el huésped.',
        '2026-08-18 16:00:00'
    );