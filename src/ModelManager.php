<?php

namespace Velsym\Database;

use ReflectionClass;
use ReflectionProperty;

class ModelManager
{
    /** @var string|BaseModel $model */
    private string|BaseModel $model;
    private string $query;
    private int $limit = -1;

    public function __construct(?string $model = NULL)
    {
        if ($model) {
            $this->model = $model;
        }
    }

    public function useModel(string $model): void
    {
        $this->model = $model;
    }

    public function fetch(): self
    {
        $tableName = $this->model::TABLE_NAME;
        $this->query = "SELECT * FROM $tableName";
        return $this;
    }

    public function where(): self
    {
        $this->query .= " WHERE";
        return $this;
    }

    public function and(): self
    {
        $this->query .= " AND";
        return $this;
    }

    public function or(): self
    {
        $this->query .= " OR";
        return $this;
    }

    public function equal(string $property, mixed $value): self
    {
        $this->query .= " $property = '$value'";
        return $this;
    }

    public function greater(string $property, mixed $value): self
    {
        $this->query .= " $property > '$value'";
        return $this;
    }

    public function greaterOrEqual(string $property, mixed $value): self
    {
        $this->query .= " $property >= '$value'";
        return $this;
    }

    public function lesser(string $property, mixed $value): self
    {
        $this->query .= " $property < '$value'";
        return $this;
    }

    public function lesserOrEqual(string $property, mixed $value): self
    {
        $this->query .= " $property <= '$value'";
        return $this;
    }

    public function between(string $property, mixed $min, mixed $max): self
    {
        $this->query .= " $property BETWEEN '$min' AND '$max'";
        return $this;
    }

    public function like(string $property, mixed $value): self
    {
        $this->query .= " $property LIKE '$value'";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        $this->query .= " LIMIT $limit";
        return $this;
    }

    public function getSQL(): string
    {
        return $this->query;
    }

    public function query(): mixed
    {
        $sql = $this->getSQL();
        $queryResult = DatabaseManager::query($sql);
        if ($this->limit === 1) {
            return $this->resultToModel($queryResult->fetch());
        }
        return $this->manyResultsToModels($queryResult);
    }

    public function save(BaseModel $model): mixed
    {
        $tableName = $this->model::TABLE_NAME;
        $modelReflection = new ReflectionClass($model);
        $id = $model->getId();
        if ($id !== -1) {
            $updateString = "";
            foreach ($modelReflection->getProperties() as $property) {
                $value = ($property->isInitialized($model) ? $property->getValue($model) : "NULL");
                $updateString .= "$property->name = '$value', ";
            }
            $updateString = rtrim($updateString, ", ");
            $sql = "UPDATE $tableName SET $updateString WHERE id = '$id'";
        } else {
            $columns = implode(", ", array_keys(DatabaseManager::getModelColumns($model)));
            $insertString = "";
            foreach ($modelReflection->getProperties() as $property) {
                $value = ($property->isInitialized($model) ? $property->getValue($model) : "NULL");
                $insertString .= "'$value', ";
            }
            $insertString = rtrim($insertString, ", ");
            $sql = "INSERT INTO $tableName ($columns) VALUES ($insertString);";
        }

        DatabaseManager::query($sql);
        $lastId = ($id !== -1 ? $id : DatabaseManager::lastInsertId());
        $queryResult = DatabaseManager::query("SELECT * FROM $tableName WHERE id = $lastId");
        return $this->resultToModel($queryResult->fetch());
    }

    private function manyResultsToModels(mixed $results): array
    {
        $models = [];
        foreach ($results as $sqlModel) {
            $models[] = $this->resultToModel($sqlModel);
        }
        return $models;
    }

    private function resultToModel(mixed $sqlModel): mixed
    {
        $modelInstance = new $this->model();
        (new ReflectionProperty(BaseModel::class, 'id'))->setValue($modelInstance, $sqlModel['id']);
        unset($sqlModel['id']);
        foreach ($sqlModel as $property => $value) {
            if (is_int($property)) {
                continue;
            }
            (new ReflectionProperty($modelInstance, $property))->setValue($modelInstance, $value);
        }
        return $modelInstance;
    }

}