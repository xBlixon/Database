<?php

namespace Velsym\Database;

use ReflectionClass;
use ReflectionProperty;

class ModelManager
{
    /** @var string|BaseModel $model */
    private string|BaseModel $model;
    private string $where = "";
    private string $queryStart;
    private string $operation;
    private string $limit = "";

    public function __construct(?string $model=NULL)
    {
        if($model) {
            $this->model = $model;
        }
    }

    public function useModel(string $model): void
    {
        $this->model = $model;
    }

    private function fetch(): void
    {
        $tableName = $this->model::TABLE_NAME;
        $this->queryStart = "SELECT * FROM $tableName";
    }

    public function fetchAll(): self
    {
        $this->fetch();
        $this->operation = "fetchAll";
        return $this;
    }

    public function fetchOne(): self
    {
        $this->fetch();
        $this->limit(1);
        $this->operation = "fetchOne";
        return $this;
    }

    public function whereIs(string $property, mixed $value): self
    {
        $this->where = "WHERE $property = '$value'";
        return $this;
    }

    public function andWhereIs(string $property, mixed $value): self
    {
        $this->where .= " AND $property = '$value'";
        return $this;
    }

    public function orWhereIs(string $property, mixed $value): self
    {
        $this->where .= " OR $property = '$value'";
        return $this;
    }

    public function whereGreater(string $property, mixed $value): self
    {
        $this->where = "WHERE $property > '$value'";
        return $this;
    }

    public function andWhereGreater(string $property, mixed $value): self
    {
        $this->where .= " AND $property > '$value'";
        return $this;
    }

    public function orWhereGreater(string $property, mixed $value): self
    {
        $this->where .= " OR $property > '$value'";
        return $this;
    }

    public function whereGreaterOrEqual(string $property, mixed $value): self
    {
        $this->where = "WHERE $property >= '$value'";
        return $this;
    }

    public function andWhereGreaterOrEqual(string $property, mixed $value): self
    {
        $this->where .= " AND $property >= '$value'";
        return $this;
    }

    public function orWhereGreaterOrEqual(string $property, mixed $value): self
    {
        $this->where .= " OR $property >= '$value'";
        return $this;
    }

    public function whereLesser(string $property, mixed $value): self
    {
        $this->where = "WHERE $property < '$value'";
        return $this;
    }

    public function andWhereLesser(string $property, mixed $value): self
    {
        $this->where .= " AND $property < '$value'";
        return $this;
    }

    public function orWhereLesser(string $property, mixed $value): self
    {
        $this->where .= " OR $property < '$value'";
        return $this;
    }

    public function whereLesserOrEqual(string $property, mixed $value): self
    {
        $this->where = "WHERE $property <= '$value'";
        return $this;
    }

    public function andWhereLesserOrEqual(string $property, mixed $value): self
    {
        $this->where .= " AND $property <= '$value'";
        return $this;
    }

    public function orWhereLesserOrEqual(string $property, mixed $value): self
    {
        $this->where .= " OR $property <= '$value'";
        return $this;
    }

    public function like(string $property, mixed $value): self
    {
        $this->where = "WHERE $property LIKE '$value'";
        return $this;
    }

    public function andLike(string $property, mixed $value): self
    {
        $this->where .= " AND $property LIKE '$value'";
        return $this;
    }

    public function orLike(string $property, mixed $value): self
    {
        $this->where .= " OR $property LIKE '$value'";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = "LIMIT $limit";
        return $this;
    }

    public function getSQL(): string
    {
        return "$this->queryStart $this->where $this->limit;";
    }

    public function query(): mixed
    {
        $sql = $this->getSQL();
        $queryResult = DatabaseManager::query($sql);
        return match ($this->operation) {
            "fetchAll" => $this->manyResultsToModels($queryResult),
            "fetchOne" => $this->resultToModel($queryResult->fetch()),
            default => NULL,
        };
    }

    public function save(BaseModel $model): mixed
    {
        $tableName = $this->model::TABLE_NAME;
        $modelReflection = new ReflectionClass($model);
        $id = $model->getId();
        if($id !== -1)
        {
            $updateString = "";
            foreach ($modelReflection->getProperties() as $property) {
                $value = ($property->isInitialized($model) ? $property->getValue($model) : "NULL");
                $updateString .= "$property->name = '$value', ";
            }
            $updateString = rtrim($updateString, ", ");
            $sql = "UPDATE $tableName SET $updateString WHERE id = '$id'";
        }
        else
        {
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
            if(is_int($property)) {
                continue;
            }
            (new ReflectionProperty($modelInstance, $property))->setValue($modelInstance, $value);
        }
        return $modelInstance;
    }

}