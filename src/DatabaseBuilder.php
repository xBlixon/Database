<?php

namespace Velsym\Database;

use ReflectionClass;

class DatabaseBuilder
{
    public static function buildModelTableQuery(string|BaseModel $model): string|null
    {
        if (!DatabaseManager::isUsingBaseModel($model)) return NULL;
        $modelReflectionClass = new ReflectionClass($model);
        $tableName = $modelReflectionClass->getConstant('TABLE_NAME');

        $columns = DatabaseManager::getModelColumns($model);
        foreach ($columns as $columnName => $columnType) {
            $columns[$columnName] = DatabaseManager::varTypeToDbType($columnType);
        }

        $stringifiedColumns = "";
        foreach ($columns as $columnName => $columnType) {
            $stringifiedColumns .= "$columnName $columnType, ";
        }

        return "CREATE TABLE IF NOT EXISTS $tableName ( id INT NOT NULL AUTO_INCREMENT, {$stringifiedColumns}PRIMARY KEY (id) );";
    }

    public static function buildModelTable(string|BaseModel $model): void
    {
        if (!$sql = self::buildModelTableQuery($model)) return;
        DatabaseManager::query($sql);
    }
}