# Comandos utilizados hasta el momento

## Iniciar el servicio MySQL (powershell)

```bash
net start MySQL97
```

## Coprobar estado del servicio (powershell)

```bash
sc.exe query MySQL97
```

## Comprobar que PHP puede conectarse a MySQL

Buscar la ruta del fichero .ini

```bash
php --ini
```

Comprobar si la extensión pdo_mysql está cargada en PHP

```bash
php -r "echo extension_loaded('pdo_mysql') ? 'pdo_mysql disponible' : 'Falta pdo_mysql';"
```

Si falta el pdo_mysql, descomentar la linea correspondiente en el .ini

## Instalar las dependencias

```bash
composer install
```

## Definir las variables de entorno (powershell) o archivo .env

```bash
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "3306"
$env:DB_NAME = "reservas"
$env:DB_USER = "reservas_app"
$env:DB_PASSWORD = "pruebas"
```

php -S localhost:8000 -t public

## Iniciar el servidor / alternativa: usar php server

```bash
php -S localhost:8000 -t public server.php   
```

## Ejecutar los tests

### Smoke tests

```bash
php tests/Smoke/DatabaseSmoke.php 
```

```bash
php tests/Smoke/InputValidatorSmoke.php 
```

### Unit tests

```bash
php tests/Unit/DatabaseTest.php 
```

```bash
php tests/Unit/InputValidatorTest.php 
```
