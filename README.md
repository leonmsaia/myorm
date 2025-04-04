# MyORM - Mini ORM en PHP 🐘

**MyORM** es una librería ORM (Object Relational Mapping) ligera escrita en PHP puro. Permite mapear tablas SQL a objetos PHP de forma sencilla, sin frameworks externos.

Ideal para proyectos chicos, educativos o scripts PHP organizados que necesiten:
- Acceso a bases de datos SQL (MySQL/MariaDB)
- Relaciones entre tablas
- Validaciones simples
- Soft deletes y timestamps automáticos (opcional)

---

## 🚀 Instalación

1. Clona o descarga este repositorio.
2. Ejecutá `composer dump-autoload` si usás Composer (opcional).
3. Editá el archivo de configuración:

```php
// myorm/config/database.php
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'nombre_base',
    'username' => 'usuario',
    'password' => 'clave',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'audit' => true // Habilita created_at, updated_at, deleted_at
];
```

---

## 🧠 Ejemplo de uso

```php
require_once 'myorm/src/ORM.php';

use MyORM\ORM;

// Conectarse
ORM::connect();

// Leer registros
$productos = ORM::table('products')->all();
print_r($productos);

// Crear nuevo
ORM::table('products')->create([
    'name' => 'Zapatilla',
    'price' => 1999.99
]);

// Actualizar
ORM::table('products')->update(1, [
    'price' => 1499.99
]);

// Eliminar (soft delete por defecto)
ORM::table('products')->delete(1);

// Hard delete
ORM::table('products')->delete(1, false);

// Validaciones
$errors = (new ORM())->validate([
    'email' => 'test@invalid'
], [
    'email' => 'required|email'
]);

if (!empty($errors)) {
    print_r($errors);
}
```

---

## 🔁 Relaciones

```php
// hasOne
$userProfile = (new ORM())->hasOne('profiles', 'user_id', 'id');

// belongsTo
$author = (new ORM())->belongsTo('users', 'author_id', 'id');

// hasMany
$posts = (new ORM())->hasMany('posts', 'user_id', 'id');
```

---

## 🧱 Estructura recomendada

```
myorm/
├── config/
│   └── database.php
├── src/
│   └── ORM.php
├── examples/
│   └── example.php
├── README.md
```

---

## 🧪 SQL de ejemplo

```sql
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100),
  `price` DECIMAL(10,2),
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL
);
```

---

## 🧩 Requisitos

- PHP 7.4+
- Extensión PDO habilitada
- MySQL / MariaDB

---

## 📌 Notas

- Todas las consultas usan PDO con prepared statements.
- Si `audit` está en `true`, se agregan automáticamente `created_at`, `updated_at` y `deleted_at`.
- Si `audit` está en `false`, podés usar la base sin esas columnas.

---

## 📃 Licencia

MIT © 2025 - Creado por vos 💙
