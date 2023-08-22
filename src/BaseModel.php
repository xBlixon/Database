<?php

namespace Velsym\Database;

abstract class BaseModel
{
    const TABLE_NAME = NULL;

    private int $id = -1;

    public function getId(): int
    {
        return $this->id;
    }

    final protected function setId(): void {}
}