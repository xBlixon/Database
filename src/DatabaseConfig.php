<?php

namespace Velsym\Database;

readonly class DatabaseConfig
{
    public DatabaseDriver $driver;
    public string $hostname;
    public string $username;
    public string $password;
    public string $database;
    public int $port;
    public array $options;
    public string $socket;

    public function __construct(
        ?DatabaseDriver $driver = NULL,
        ?string $hostname = NULL,
        ?string $username = NULL,
        ?string $password = NULL,
        ?string $database = NULL,
        ?int $port = NULL,
        ?array $options = NULL,
        ?string $socket = NULL
    )
    {
        if($driver) $this->driver = $driver;
        if($hostname) $this->hostname = $hostname;
        if($username) $this->username = $username;
        if($password) $this->password = $password;
        if($database) $this->database = $database;
        if($port) $this->port = $port;
        if($options) $this->options = $options;
        if($socket) $this->socket = $socket;
    }

    public static function fromURL(string $URL): DatabaseConfig
    {
        // driver://username:password@host:port/database?options
        $pattern = '/(?<driver>.*?):\/\/(?<username>.*?):(?<password>.*?)@(?<host>.*?)(:(?<port>.*?))?\/(?<database>[^?]*)\??(?<options>.*)/';
        preg_match($pattern, $URL, $matches, PREG_UNMATCHED_AS_NULL);
        if($matches['options'] !== "") parse_str($matches['options'], $matches['options']);

        $driver = DatabaseDriver::from($matches['driver']);
        $port = (int)($matches['port'] ?? match ($driver) {
            DatabaseDriver::MYSQL => 3306,
            DatabaseDriver::POSTGRESQL => 5432,
        });


        return (new DatabaseConfig())
            ->setDriver($driver)
            ->setHostname($matches['host'])
            ->setUsername($matches['username'])
            ->setPassword($matches['password'])
            ->setDatabase($matches['database'])
            ->setPort($port)
            ->setOptions($matches['options']);
    }

    public function setDriver(DatabaseDriver $driver): self
    {
        $this->driver = $driver;
        return $this;
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

    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function setSocket(string $socket): self
    {
        $this->socket = $socket;
        return $this;
    }

    public function getDSN(): string
    {
        if(isset($this->socket))
            return "{$this->driver->value}:$this->socket";
        return "{$this->driver->value}:host=$this->hostname;port=$this->port;dbname=$this->database";
    }
}