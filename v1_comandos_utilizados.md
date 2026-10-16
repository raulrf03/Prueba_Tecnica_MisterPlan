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
