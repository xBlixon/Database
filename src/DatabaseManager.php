<?php

namespace Velsym\Database;

use mysqli;
use ReflectionClass;

class DatabaseManager
{
    public static DatabaseConfig $config;

    private function __construct(){}

    public static function loadConfig(DatabaseConfig $config)
    {
        self::$config = $config;
    }

    private static function getMysqli(): mysqli
    {
        return new mysqli(
            self::$config->hostname ?? NULL,
            self::$config->username ?? NULL,
            self::$config->password ?? NULL,
            self::$config->database ?? NULL,
            self::$config->port ?? NULL,
            self::$config->socket ?? NULL
        );
    }

    public static function createModelTable(string $modelName): void
    {
        if(!$sql = self::createModelTableSQL($modelName)) return;
        self::getMysqli()->query($sql);
    }

    public static function createModelTableSQL(string $modelName): string|NULL
    {
        if (!is_subclass_of($modelName, BaseModel::class)) return NULL;
        $modelReflectionClass = new ReflectionClass($modelName);
        $tableName = $modelReflectionClass->getProperty("tableName")->getDefaultValue();
        $modelColumns = $modelReflectionClass->getProperties();
        $baseModelPropertiesNames = [];

        foreach ((new ReflectionClass(BaseModel::class))->getProperties() as $baseProperty) {
            $baseModelPropertiesNames[] = $baseProperty->name;
        }

        $columns = [];
        foreach ($modelColumns as $index => $modelColumn) {
            if (in_array($modelColumn->name, $baseModelPropertiesNames)) {
                unset($modelColumns[$index]);
                continue;
            }
            $columnTypeName = $modelColumn->getType()->getName();
            $columns[$modelColumn->name] = self::varTypeToDbType($columnTypeName);
        }

        if($columns['id']) {
            $isInt = $columns['id'] === 'INT';
            $columns['id'] .= " NOT NULL" . ($isInt ? " AUTO_INCREMENT": "");
        }


        $stringifiedColumns = "";
        foreach ($columns as $columnName => $columnType) {
            $stringifiedColumns .= "$columnName $columnType, ";
        }

        if($columns['id']) $stringifiedColumns .= "PRIMARY KEY (id)";
        else $stringifiedColumns = rtrim($stringifiedColumns, ", ");
        $sql = /** @lang text */ "CREATE TABLE $tableName ( $stringifiedColumns );";
        return $sql;
    }

    public static function varTypeToDbType(string $type): string
    {
        return match ($type) {
            'string' => 'TEXT',
            'int' => 'INT',
        };
    }
}