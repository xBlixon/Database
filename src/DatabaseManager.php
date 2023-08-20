<?php

namespace Velsym\Database;

use PDO;
use ReflectionClass;

class DatabaseManager
{
    private static ?DatabaseConfig $config = NULL;
    private static string $DSN = "";

    private function __construct(){}

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

    public static function createModelTable(string $modelName): void
    {
        if(!$sql = self::createModelTableSQL($modelName)) return;
        self::getPDO()->query($sql);
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
        return /** @lang text */ "CREATE TABLE $tableName ( $stringifiedColumns );";
    }

    public static function varTypeToDbType(string $type): string
    {
        return match ($type) {
            'string' => 'TEXT',
            'int' => 'INT',
        };
    }
}