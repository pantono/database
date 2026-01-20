<?php

declare(strict_types=1);

namespace Pantono\Database\Connection;

use Pantono\Database\Adapter\Db;
use Pantono\Database\Repository\MysqlRepository;
use Pantono\Database\Repository\HubSqlRepository;
use Pantono\Database\Repository\StocklinkSqlRepository;
use Pantono\Database\Repository\MssqlRepository;
use Pantono\Database\Repository\PgsqlRepository;

class ConnectionCollection
{
    /**
     * @var array<mixed>
     */
    private array $connections = [];

    public function addConnection(string $name, string $type, Db $db): void
    {
        if ($type === 'mysql') {
            $parentRepository = MysqlRepository::class;
        } elseif ($type === 'mssql') {
            $parentRepository = MssqlRepository::class;
        } elseif ($type === 'pgsql') {
            $parentRepository = PgsqlRepository::class;
        } else {
            throw new \RuntimeException('Invalid database connection type ' . $type);
        }
        $this->connections[$name] = [
            'parent' => $parentRepository,
            'type' => $type,
            'connection' => $db
        ];
    }

    public function getConnectionForParent(string $parent): Db
    {
        foreach ($this->connections as $connection) {
            if ($connection['parent'] === $parent) {
                return $connection['connection'];
            }
        }
        return $this->getDefaultConnection();
    }

    public function getDefaultConnection(): Db
    {
        if (empty($this->connections)) {
            throw new \RuntimeException('No connection registered for type');
        }
        $keys = array_keys($this->connections);
        return $this->connections[$keys[0]];
    }

    public function closeConnections(): void
    {
        foreach ($this->connections as $name => $connection) {
            $this->connections[$name]['connection'] = null;
        }
    }

    public function getConnectionByName(string $name): ?Db
    {
        return $this->connections[$name]['connection'] ?? null;
    }
}
