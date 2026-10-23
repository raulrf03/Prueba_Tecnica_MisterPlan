# API de Gestión de Reservas

Aplicación web y API REST para gestionar reservas de alojamiento: creación, consulta, filtrado, confirmación, cancelación e historial de cambios de estado.

El proyecto está construido con PHP nativo, MySQL, Twig y una arquitectura en capas, sin utilizar un framework. Incluye validación de entrada, consultas preparadas, control CSRF y pruebas automatizadas.

## Índice

- [Índice](#índice)
- [Funcionalidades](#funcionalidades)
- [Tecnologías usadas](#tecnologías-usadas)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Requisitos](#requisitos)
- [Puesta en marcha local](#puesta-en-marcha-local)
- [Endpoints principales](#endpoints-principales)
- [Pruebas](#pruebas)
- [Decisiones de diseño](#decisiones-de-diseño)
- [Qué mejoraría con más tiempo](#qué-mejoraría-con-más-tiempo)
- [Escalabilidad: 1.000 reservas por hora](#escalabilidad-1000-reservas-por-hora)
- [Despliegue](#despliegue)

## Funcionalidades

- Listado de reservas con filtros por estado, fechas y huésped.
- Creación de reservas con validación de datos.
- Consulta del detalle de una reserva.
- Confirmación y cancelación de reservas.
- Registro de eventos cada vez que cambia el estado de una reserva.
- API JSON bajo `/api`.
- Interfaz web con Twig y JavaScript.
- Pruebas unitarias y smoke tests.

## Tecnologías usadas

- PHP 8.4.4 (recomendado 8.4.27 o superior, que corrige CVE-2026-109618 y CVE-2026-109619)
- MySQL 9.7.2
- PDO
- Twig 3.30.0
- phpdotenv 5.7.0 (carga variables desde `.env`)
- Composer
- PHPUnit 13.4.3
- HTML, CSS y JavaScript

## Estructura del proyecto

```text
public/          Punto de entrada público y recursos estáticos
config/          Configuración de la aplicación
server.php       Enrutador para el servidor de desarrollo PHP
src/
  Controllers/   Controladores HTTP y API
  Database/      Conexión y configuración de base de datos
  Exceptions/    Excepciones de la aplicación
  Repositories/  Acceso a datos mediante PDO
  Routes/        Enrutamiento HTTP
  Security/      Protección CSRF y utilidades de seguridad
  Services/      Reglas de negocio y transiciones de estado
  Validators/    Validación y saneamiento de entrada
  View/          Configuración y renderizado de vistas
templates/       Vistas Twig
tests/
  Smoke/         Comprobaciones rápidas de entorno y clases
  Unit/          Pruebas unitarias
database/
  reservas.sql   Esquema y datos de ejemplo
  usuario_app.sql  Creación del usuario de aplicación
```

## Requisitos

- PHP 8.4 o superior
- Composer
- MySQL 8 o superior
- Extensión PHP `pdo_mysql`

Comprueba que tienes la extensión necesaria:

```bash
php -m | grep -i pdo_mysql
```

Si no aparece, habilita `extension=pdo_mysql` en el `php.ini` activo (`php --ini`).

## Puesta en marcha local

### 1. Clonar el repositorio

```bash
git clone https://github.com/raulrf03/Prueba_Tecnica_MisterPlan.git
cd Prueba_Tecnica_MisterPlan
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Crear la base de datos

Inicia el servicio de MySQL según tu instalación. Desde la raíz del proyecto,
importa el esquema y los datos:

```bash
mysql -u root -p < database/reservas.sql
```

El script crea la base de datos `reservas` con sus tablas y datos de ejemplo.

### 4. Crear el usuario de aplicación

Importa el archivo de usuario:

```bash
mysql -u root -p < database/usuario_app.sql
```

Este script crea un usuario con permisos limitados sobre la base `reservas`.
Antes de importarlo, cambia la contraseña `prueba` del script por una propia;
usa el mismo valor en `DB_PASSWORD` en el archivo `.env`.

No utilices el usuario `root` desde la aplicación.

### 5. Configurar las variables de entorno

Copia el archivo de ejemplo:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Edita `.env` y ajusta la contraseña:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=reservas
DB_USER=reservas_app
DB_PASSWORD=contrasena
DB_SSL_CA=/ruta/certificado/aiven-ca.pem
```

El archivo `.env` no debe subirse al repositorio porque contiene credenciales locales. El repositorio incluye `.env.example` como plantilla.

### 6. Arrancar el servidor

Con MySQL activo, ejecuta desde la raíz del proyecto:

```bash
php -S localhost:8000 -t public server.php
```

Abre la interfaz web en:

[http://localhost:8000](http://localhost:8000)

Para consultar el listado de reservas en JSON, visita:

[http://localhost:8000/api/reservas](http://localhost:8000/api/reservas)

Las rutas usan el prefijo `/api`; `/api` por sí sola no es un endpoint.

## Endpoints principales

| Método  | Ruta                        | Descripción                        |
| ------- | --------------------------- | ---------------------------------- |
| `GET`   | `/api/reservas`             | Lista reservas; admite filtros     |
| `GET`   | `/api/reservas/{id}`        | Obtiene una reserva y su historial |
| `POST`  | `/api/reservas`             | Crea una reserva                   |
| `PATCH` | `/api/reservas/{id}/estado` | Cambia el estado de una reserva    |

`GET /api/reservas` admite los filtros `estado`, `fecha_desde`, `fecha_hasta` y `huesped`.

Las operaciones `POST` y `PATCH` requieren la cookie de sesión y el token CSRF.

### Ejemplos con curl

Ejemplos Bash. Obtén la cookie y el token CSRF de la página principal:

```bash
BASE_URL=http://localhost:8000
COOKIE_JAR=$(mktemp)

HTML=$(curl -fsS -c "$COOKIE_JAR" "$BASE_URL/")
CSRF_TOKEN=$(printf '%s\n' "$HTML" | sed -n 's/.*<meta name="csrf-token" content="\([^"]*\)".*/\1/p')
```

Consultar reservas filtradas no requiere token CSRF:

```bash
curl -sS --get "$BASE_URL/api/reservas" \
  --data-urlencode 'estado=pendiente' \
  --data-urlencode 'huesped=Ana'
```

Crear una reserva:

```bash
curl -i -sS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{
    "nombre_huesped": "Ana García",
    "email_huesped": "ana@example.com",
    "nombre_alojamiento": "Apartamento Centro",
    "fecha_entrada": "2026-12-01",
    "fecha_salida": "2026-12-05",
    "num_personas": 2,
    "importe": "320.50",
    "notas": "Llegada por la tarde"
  }' \
  "$BASE_URL/api/reservas"
```

Cambiar el estado de una reserva pendiente (sustituye `1` por su identificador):

```bash
curl -i -sS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  -X PATCH \
  -H "X-CSRF-Token: $CSRF_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"estado":"confirmada"}' \
  "$BASE_URL/api/reservas/1/estado"
```

Al terminar, elimina el archivo temporal con `rm "$COOKIE_JAR"`.

## Pruebas

Ejecuta todos los tests con PHPUnit:

```bash
./vendor/bin/phpunit
```

En Windows PowerShell:

```powershell
.\vendor\bin\phpunit
```

Para ejecutar un test concreto:

```bash
./vendor/bin/phpunit tests/Unit/InputValidatorTest.php
```

Los smoke tests se pueden ejecutar por separado:

```bash
php tests/Smoke/DatabaseSmoke.php
php tests/Smoke/InputValidatorSmoke.php
```

## Decisiones de diseño

### Arquitectura en capas

El proyecto separa responsabilidades en controladores, servicios, repositorios y validadores.

- **Controllers**: reciben la petición HTTP, validan parámetros básicos y devuelven respuestas JSON o vistas.
- **Services**: contienen las reglas de negocio, como las transiciones válidas de estado.
- **Repositories**: encapsulan el acceso a MySQL mediante PDO y consultas preparadas.
- **Validators**: centralizan la validación y el saneamiento de datos recibidos.

### MySQL

MySQL almacena las reservas y sus eventos relacionados. `reserva_evento` conserva
el historial de cambios sin sobrescribir eventos anteriores.

### Seguridad

- Uso de PDO con consultas preparadas para evitar inyección SQL.
- Validación y saneamiento de todos los datos de entrada.
- Protección CSRF en formularios y acciones de escritura.
- Usuario de base de datos con permisos mínimos.
- Errores técnicos registrados en el log, devolviendo al cliente mensajes genéricos.
- Validación de identificadores de ruta antes de consultar la base de datos.

## Qué mejoraría con más tiempo

- Añadiría autenticación y autorización por roles, por ejemplo administrador y recepción.
- Añadiría pruebas de integración que cubran las peticiones HTTP, la validación, CSRF y la persistencia.
- Añadiría migraciones versionadas en lugar de depender únicamente de un archivo SQL inicial.
- Mejoraría la observabilidad con logs estructurados, métricas y alertas.
- Introduciría caché solo para consultas de lectura repetidas y después de definir cómo invalidarla.
- Añadiría paginación al listado; usaría cursores si el volumen de datos lo justificara.
- Añadiría documentación OpenAPI/Swagger para la API.
- Haría pruebas de usabilidad de los flujos de creación, filtrado y cambio de estado en móvil y escritorio.

## Escalabilidad: 1.000 reservas por hora

Con 1.000 reservas por hora, la aplicación necesitaría cambios en varios niveles.

### Base de datos

- Revisaría con `EXPLAIN` si los índices existentes cubren las consultas más frecuentes por estado, fechas y huésped; los ampliaría solo si las métricas lo justifican.
- Utilizaría conexiones persistentes o un pool de conexiones.
- Separaría lecturas y escrituras si el volumen de consultas de listado creciera mucho.
- Consideraría réplicas de lectura para descarga del nodo principal.

### Aplicación

- Movería la aplicación a PHP-FPM detrás de Nginx o Apache, en lugar de usar el servidor de desarrollo de PHP.
- Aplicaría rate limiting para proteger los endpoints públicos.
- Colocaría la aplicación detrás de un balanceador si hubiera varias instancias.

### Procesamiento asíncrono

- Las acciones que generan eventos, notificaciones o emails pasarían a una cola, por ejemplo con Redis, RabbitMQ o Amazon SQS.
- La creación de reserva respondería rápidamente y el procesamiento secundario se haría de forma asíncrona.
- Los trabajos fallidos tendrían reintentos, registro y alertas.

### Infraestructura

- Contenedorizaría la aplicación con Docker para despliegues reproducibles.
- Usaría CI/CD para ejecutar tests, análisis estático y despliegues automáticos.
- Añadiría monitorización de latencia, tasa de errores, uso de CPU, memoria y conexiones a base de datos.
- Configuraría backups automáticos y pruebas periódicas de restauración.

## Despliegue

La aplicación está alojada en Alwaysdata y utiliza Aiven para la base de datos MySQL.
Enlace [rrf-gestion-reservas.alwaysdata.net](https://rrf-gestion-reservas.alwaysdata.net/)

### Preparación del despliegue

1. Descargar el proyecto e instalar las dependencias de producción:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   ```

2. Crear la base de datos e importar el esquema y los datos iniciales de `database/reservas.sql`.
3. Crear un usuario MySQL para la aplicación con permisos `SELECT`, `INSERT` y `UPDATE` sobre la base de datos del proyecto.
4. Configurar las variables de conexión del `.env` con los datos del entorno de despliegue, sin subir las credenciales al repositorio.
5. Guardar el certificado CA de Aiven fuera del directorio público y configurar su ruta mediante `DB_SSL_CA`, manteniendo activada la verificación del certificado del servidor.
6. Configurar el servidor web para utilizar `public/` como raíz pública y dirigir las rutas de la aplicación a `index.php` mediante `public/.htaccess`.
7. Comprobar el listado, los filtros, la creación de reservas, los cambios de estado y el historial.
