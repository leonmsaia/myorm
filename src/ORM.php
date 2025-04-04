<?php

namespace MyORM;

use PDO;
use PDOException;

class ORM {
    protected static $pdo;
    protected static $table;
    protected static $config = [];

    public static function connect($configPath = __DIR__ . '/../config/database.php') {
        self::$config = require $configPath;

        try {
            self::$pdo = new PDO(
                self::$config['driver'] . ":host=" . self::$config['host'] . ";dbname=" . self::$config['database'] . ";charset=" . self::$config['charset'],
                self::$config['username'],
                self::$config['password']
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Conexión fallida: " . $e->getMessage());
        }
    }

    public static function table($table) {
        self::$table = $table;
        return new static;
    }

    public function all($withDeleted = false) {
        $sql = "SELECT * FROM `" . self::$table . "`";

        if (self::$config['audit'] && !$withDeleted) {
            $check = self::$pdo->prepare("SHOW COLUMNS FROM `" . self::$table . "` LIKE 'deleted_at'");
            $check->execute();

            if ($check->rowCount() > 0) {
                $sql .= " WHERE `deleted_at` IS NULL";
            }
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $sql = "SELECT * FROM `" . self::$table . "` WHERE `id` = :id";
        if (self::$config['audit']) {
            $sql .= " AND `deleted_at` IS NULL";
        }
        $sql .= " LIMIT 1";

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function where($field, $value) {
        $sql = "SELECT * FROM `" . self::$table . "` WHERE `$field` = :value";
        if (self::$config['audit']) {
            $sql .= " AND `deleted_at` IS NULL";
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(['value' => $value]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        if (self::$config['audit']) {
            if (!isset($data['created_at'])) {
                echo "⚠️ Advertencia: 'created_at' no especificado, se usará automático.
";
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($data['updated_at'])) {
                echo "⚠️ Advertencia: 'updated_at' no especificado, se usará automático.
";
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        $columns = implode(',', array_map(fn($key) => "`$key`", array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));
        $stmt = self::$pdo->prepare("INSERT INTO `" . self::$table . "` ($columns) VALUES ($placeholders)");

        try {
            $stmt->execute($data);
            return self::$pdo->lastInsertId();
        } catch (PDOException $e) {
            die("Error al insertar: " . $e->getMessage());
        }
    }

    public function update($id, $data) {
        if (self::$config['audit']) {
            if (!isset($data['updated_at'])) {
                echo "⚠️ Advertencia: 'updated_at' no especificado, se usará automático.
";
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        $fields = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($data)));
        $data['id'] = $id;

        $stmt = self::$pdo->prepare("UPDATE `" . self::$table . "` SET $fields WHERE `id` = :id");

        try {
            return $stmt->execute($data);
        } catch (PDOException $e) {
            die("Error al actualizar: " . $e->getMessage());
        }
    }

    public function delete($id, $soft = true) {
        try {
            if (self::$config['audit'] && $soft) {
                $check = self::$pdo->prepare("SHOW COLUMNS FROM `" . self::$table . "` LIKE 'deleted_at'");
                $check->execute();

                if ($check->rowCount() === 0) {
                    throw new \Exception("La tabla '" . self::$table . "' no tiene el campo 'deleted_at'.");
                }

                $stmt = self::$pdo->prepare("UPDATE `" . self::$table . "` SET `deleted_at` = NOW() WHERE `id` = :id");
            } else {
                $stmt = self::$pdo->prepare("DELETE FROM `" . self::$table . "` WHERE `id` = :id");
            }

            return $stmt->execute(['id' => $id]);
        } catch (\PDOException $e) {
            die("Error en delete(): " . $e->getMessage());
        } catch (\Exception $e) {
            die("Error en delete(): " . $e->getMessage());
        }
    }

    public function validate(array $data, array $rules): array {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $ruleList = explode('|', $ruleSet);

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && (!isset($data[$field]) || trim($data[$field]) === '')) {
                    $errors[$field][] = 'Este campo es obligatorio.';
                }

                if ($rule === 'email' && isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Debe ser un email válido.';
                }
            }
        }

        return $errors;
    }

    public function hasOne($relatedTable, $foreignKey, $localKey = 'id') {
        $stmt = self::$pdo->prepare("SELECT * FROM `$relatedTable` WHERE `$foreignKey` = :localKey LIMIT 1");
        $stmt->execute(['localKey' => $this->{$localKey}]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hasMany($relatedTable, $foreignKey, $localKey = 'id') {
        $stmt = self::$pdo->prepare("SELECT * FROM `$relatedTable` WHERE `$foreignKey` = :localKey");
        $stmt->execute(['localKey' => $this->{$localKey}]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function belongsTo($relatedTable, $foreignKey, $ownerKey = 'id') {
        $stmt = self::$pdo->prepare("SELECT * FROM `$relatedTable` WHERE `$ownerKey` = :foreignKey LIMIT 1");
        $stmt->execute(['foreignKey' => $this->{$foreignKey}]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}