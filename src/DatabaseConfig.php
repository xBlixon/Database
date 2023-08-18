<?php

namespace Velsym\Database;

readonly class DatabaseConfig
{
    public string $hostname;
    public string $username;
    public string $password;
    public string $database;
    public int $port;
    public string $socket;

    public function __construct(
        ?string $hostname = NULL,
        ?string $username = NULL,
        ?string $password = NULL,
        ?string $database = NULL,
        ?int $port = NULL,
        ?string $socket = NULL
    )
    {
        if($hostname) $this->hostname = $hostname;
        if($username) $this->username = $username;
        if($password) $this->password = $password;
        if($database) $this->database = $database;
        if($port) $this->port = $port;
        if($socket) $this->socket = $socket;
    }

    public function setHostname(string $hostname): self
    {
        $this->hostname = $hostname;
        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setDatabase(string $database): self
    {
        $this->database = $database;
        return $this;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    public function setSocket(string $socket): self
    {
        $this->socket = $socket;
        return $this;
    }
}