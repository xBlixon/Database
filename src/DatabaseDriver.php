<?php

namespace Velsym\Database;

enum DatabaseDriver: string
{
    case MYSQL = "mysql";
    case POSTGRESQL = "pgsql";
    case SQLITE = "sqlite";
}
