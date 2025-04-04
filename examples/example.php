<?php

require_once __DIR__ . '/../src/ORM.php';

use MyORM\ORM;

ORM::connect();

$products = ORM::table('products')->all();
print_r($products);

$id = ORM::table('products')->create([
    'name' => 'Producto Test',
    'price' => 1000
]);

ORM::table('products')->update($id, ['price' => 1200]);
ORM::table('products')->delete($id);

$errors = (new ORM())->validate(['email' => 'prueba'], ['email' => 'required|email']);
print_r($errors);
