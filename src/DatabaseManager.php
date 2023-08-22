<?php

namespace Velsym\Database;

use PDO;
use PDOStatement;
use ReflectionClass;

class DatabaseManager
{
    private static ?DatabaseConfig $config = NULL;
    private static string $DSN = "";

    private function __construct()
    {
    }

    public static function setConfig(DatabaseConfig $config): void
    {
        self::$config = $config;
        self::$DSN = $config->getDSN();
    }

    public static function getConfig(): ?DatabaseConfig
    {
        return self::$config;
    }

    private static function getPDO(): PDO
    {
        return new PDO(self::$DSN, self::$config->username, self::$config->password);
    }

    public static function query(string $query): false|PDOStatement
    {
        return self::getPDO()->query($query);
    }

    public static function createModelTable(string|BaseModel $model): void
    {
        if (!$sql = self::createModelTableQuery($model)) return;
        self::getPDO()->query($sql);
    }

    public static function createModelTableQuery(string|BaseModel $model): string|null
    {
        if (!self::isUsingBaseModel($model)) return NULL;
        $modelReflectionClass = new ReflectionClass($model);
        $tableName = $modelReflectionClass->getConstant('TABLE_NAME');

        $columns = self::getModelColumns($model);
        foreach ($columns as $columnName => $columnType) {
            $columns[$columnName] = self::varTypeToDbType($columnType);
        }

        $stringifiedColumns = "";
        foreach ($columns as $columnName => $columnType) {
            $stringifiedColumns .= "$columnName $columnType, ";
        }

        return /** @lang text */ "CREATE TABLE $tableName ( id INT NOT NULL AUTO_INCREMENT, {$stringifiedColumns}PRIMARY KEY (id) );";
    }

    public static function varTypeToDbType(string $type): string
    {
        return match ($type) {
            'string' => 'TEXT',
            'int' => 'INT',
        };
    }

    /** @return string[]|NULL */
    public static function getModelColumns(string|BaseModel $model): array|null
    {
        if (!self::isUsingBaseModel($model)) return NULL;
        $reflectionClass = new ReflectionClass($model);
        $propertiesReflection = $reflectionClass->getProperties();
        $columns = [];
        foreach ($propertiesReflection as $property) {
            $columns[$property->name] = $property->getType()->getName();
        }
        unset($columns['id']);
        return $columns;
    }

    public static function isUsingBaseModel(string|BaseModel $model): bool
    {
        return is_subclass_of($model, BaseModel::class);
    }

    public static function saveModel(BaseModel $modelInstance): void
    {
        $tableName = $modelInstance::TABLE_NAME;
        $columns = self::getModelColumns($modelInstance);
        if ($modelInstance->getId() !== -1) {
            $setString = "";
            foreach ($columns as $column => $type) {
                $setString .= "$column {$modelInstance->{$column}}, ";
            }
            $setString = rtrim($setString, ", ");

            $sql = /** @lang text */
                "UPDATE $tableName SET $setString WHERE 'id' = {$modelInstance->getId()};";
            self::query($sql);
        } else {
            $insertColumns = implode(", ", array_keys($columns));
            $insertValues = "";
            foreach ($columns as $column => $type) {
                $column = ucfirst($column);
                $insertValues .= "'{$modelInstance->{"get$column"}()}', ";
            }
            $insertValues = rtrim($insertValues, ", ");
            $sql = /** @lang text */
                "INSERT INTO $tableName ($insertColumns) VALUES ( $insertValues );";
            self::query($sql);
        }
    }

    /**
     * @template Model
     * @param Model|string|BaseModel $modelClass
     * @return Model
     */
    public static function getModel(string|BaseModel $modelClass, array $params = [])
    {
        $sql = /** @lang text */
            "SELECT * FROM " . $modelClass::TABLE_NAME;
        if (!empty($params)) {
            $whereParams = [];
            foreach ($params as $column => $value) {
                $whereParams[] = "$column = $value";
            }
            $whereParams = implode(", ", $whereParams);
            $sql .= " WHERE $whereParams";
        }

        $sql .= ";";
        $model = new $modelClass();
        $modelSQL = self::getPDO()->query($sql)->fetch();
        (new \ReflectionProperty(BaseModel::class, 'id'))->setValue($model, $modelSQL['id']);
        unset($modelSQL['id']);
        foreach ($modelSQL as $column => $value) {
            if(is_int($column)) {
                unset($modelSQL[$column]);
                continue;
            }
            (new \ReflectionProperty($model, $column))->setValue($model, $value);
        }
        return $model;
    }

    public static function isPresentInDatabase(BaseModel $model): bool
    {
        $fetchedModel = DatabaseManager::getModel($model::class, ['id' => $model->getId()]);
        return $model == $fetchedModel;
    }
}