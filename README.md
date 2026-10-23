# API de Gestión de Reservas

Aplicación web y API REST para gestionar reservas de alojamiento: creación, consulta, filtrado, confirmación, cancelación e historial de cambios de estado.

El proyecto está construido con PHP nativo, MySQL, Twig y una arquitectura en capas, sin utilizar un framework. Incluye validación de entrada, consultas preparadas, control CSRF, logging técnico y pruebas automatizadas.

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

- PHP 8.4
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
src/
  Controllers/   Controladores HTTP y API
  Repositories/  Acceso a datos mediante PDO
  Services/      Reglas de negocio y transiciones de estado
  Validators/    Validación y saneamiento de entrada
  Database/      Conexión y configuración de base de datos
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
php -r "echo extension_loaded('pdo_mysql') ? 'pdo_mysql disponible' : 'Falta pdo_mysql';"
```

Si falta, abre el `php.ini` indicado por:

```bash
php --ini
```

Y descomenta o añade:

```ini
extension=pdo_mysql
```

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

Esto crea la base de datos `reservas`, sus tablas y datos de ejemplo.

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
DB_PASSWORD=contraseña
```

El archivo `.env` no debe subirse al repositorio porque contiene credenciales locales. El repositorio incluye `.env.example` como plantilla.

### 6. Arrancar el servidor

Desde la raíz del proyecto:

```bash
php -S localhost:8000 -t public server.php
```

Abre la interfaz web en:

[http://localhost:8000](http://localhost:8000)

Para consultar el listado de reservas en JSON, visita:

[http://localhost:8000/api/reservas](http://localhost:8000/api/reservas)

Las rutas de la API usan el prefijo `/api`; `/api` por sí sola no es un endpoint.

## Endpoints principales

| Método  | Ruta                        | Descripción                        |
| ------- | --------------------------- | ---------------------------------- |
| `GET`   | `/api/reservas`             | Lista reservas; admite filtros     |
| `GET`   | `/api/reservas/{id}`        | Obtiene una reserva y su historial |
| `POST`  | `/api/reservas`             | Crea una reserva                   |
| `PATCH` | `/api/reservas/{id}/estado` | Cambia el estado de una reserva    |

El endpoint `GET /api/reservas` admite los filtros `estado`, `fecha_desde`,
`fecha_hasta` y `huesped`.

Ejemplo del cuerpo JSON enviado a `POST /api/reservas`:

```json
{
  "nombre_huesped": "Ana García",
  "email_huesped": "ana@example.com",
  "nombre_alojamiento": "Apartamento Centro",
  "fecha_entrada": "2026-11-01",
  "fecha_salida": "2026-11-05",
  "num_personas": 2,
  "importe": "320.50",
  "notas": "Llegada por la tarde"
}
```

En `POST` y `PATCH`, envía la cookie de sesión y el token CSRF en la cabecera
`X-CSRF-Token`. Puedes obtener el token de la etiqueta
`meta[name="csrf-token"]` que devuelve una página de la aplicación.

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

```bash
./vendor/bin/phpunit tests/Unit/DatabaseTest.php
```

También puedes lanzar los smoke tests de forma individual:

```bash
php tests/Smoke/DatabaseSmoke.php
php tests/Smoke/InputValidatorSmoke.php
```

Los smoke tests comprueban rápidamente que el entorno, la conexión a MySQL y las clases principales funcionan.

## Decisiones de diseño

### Arquitectura en capas

El proyecto separa responsabilidades en controladores, servicios, repositorios y validadores.

- **Controllers**: reciben la petición HTTP, validan parámetros básicos y devuelven respuestas JSON o vistas.
- **Services**: contienen las reglas de negocio, como las transiciones válidas de estado.
- **Repositories**: encapsulan el acceso a MySQL mediante PDO y consultas preparadas.
- **Validators**: centralizan la validación y el saneamiento de datos recibidos.

Esta separación evita que la lógica de negocio quede mezclada con SQL o con el código HTTP, facilita las pruebas y permite cambiar la interfaz o el almacenamiento con menos riesgo.

### MySQL

Elegí MySQL porque es una base de datos relacional adecuada para reservas: los datos tienen relaciones claras entre reservas, estados y eventos, y se necesitan consultas por fechas, estado y huésped.

La tabla `reserva` almacena la información principal, mientras que
`reserva_evento` conserva los eventos de creación y los cambios de estado para
consultar su historial sin sobrescribir eventos anteriores.

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

La aplicación está desplegada en:

[Abrir la aplicación desplegada](https://rrf-gestion-reservas.alwaysdata.net/)

Para desplegar en producción habría que:

- Configurar variables de entorno seguras.
- Usar HTTPS.
- Desactivar la visualización de errores de PHP en producción.
- Crear un usuario MySQL con permisos mínimos.
- Configurar logs y rotación de logs.
- Importar el esquema con `database/reservas.sql`.
- Ejecutar la suite de tests antes del despliegue.
