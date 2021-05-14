<?php

namespace App\System\Database;

use App\System\Collection;
use App\System\Config;
use App\Traits\SingletonTrait;

class DB
{
    use SingletonTrait;
    /**
     * @var \PDO|null $connection
     */
    protected $connection;
    /**
     * @var QueryBuilder|null $query
     */
    protected $query;
    /**
     * @var DB|null $instance
     */
    protected static $instance;

    protected function setConnection(string $connectionName = 'database'): DB
    {
        /**
         * @var array $config
         */
        $config = Config::has(sprintf('connections/%s', $connectionName)) ? Config::get(sprintf('connections/%s', $connectionName)) : [];
        /**
         * @var \PDO
         */
        $this->connection = new \PDO(
            sprintf('%s;dbname=%s', $config['connection'], $config['name']),
            $config['username'],
            $config['password'],
            $config['options']
        );

        return $this;
    }

    /**
     * @return QueryBuilder|null
     */
    public function getQuery(): ?QueryBuilder
    {
        return $this->query;
    }

    /**
     * @param QueryBuilder $builder
     * @return DB
     */
    public function setQuery(QueryBuilder $builder): DB
    {
        $this->query = $builder;

        return $this;
    }

    /**
     * @return DB
     */
    public function newQuery()
    {
        return $this->setQuery(new QueryBuilder());
    }

    /**
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * @param string|null $table
     * @return QueryBuilder
     */
    public static function table(string $table = null): QueryBuilder
    {
        if (is_null($table) && !is_null(static::getInstance()->getQuery()->getModel())) {
            static::getInstance()
                ->getQuery()
                ->table(static::$instance->getQuery()->getModel()->getTable());
        } elseif (!is_null($table)) {
            static::getInstance()->setQuery(new QueryBuilder())->getQuery()->table($table);
        }

        return static::$instance->getQuery();
    }

    /**
     * @param string|null $sql
     * @param bool $count
     * @return Collection|null
     */
    public function query(string $sql = null, bool $count = false)
    {
        /**
         * @var QueryBuilder $query
         */
        $query = $this->getQuery();

        $isDeleting = $query->isDelete();
        $isUpdating = $query->isUpdate();
        $isInserting = $query->isInsert();
        $isSelecting = $query->isSelect();
        $isExists = $query->isExists();

        $statement = $this->getConnection()->prepare($sql ? $sql : (string) $query);

        $model = null;

        try {
            $statement->execute();

            if ($isSelecting && !$count && !$isExists) {
                $model = $query->getModel();

                return collect($statement->fetchAll(\PDO::FETCH_CLASS, !is_null($model) ? get_class($model) : \stdClass::class));
            } elseif ($isSelecting && $count) {
                return current($statement->fetch());
            } elseif ($isExists && $isSelecting) {
                $result = $statement->fetch(\PDO::FETCH_NUM);
                return $result ? $result : [];
            }
        } catch (\PDOException $exception) {
            var_dump(strval($sql ? $sql : $query), $exception->getTraceAsString());
            dd($exception->getMessage());
        }

        return null;
    }

    /**
     * @return DB
     */
    public static function connect(): DB
    {
        return static::getInstance();
    }

    /**
     * @return int
     */
    public static function lastId(): int
    {
        $statement = static::$instance->connection->prepare('SELECT LAST_INSERT_ID()');

        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * @param string $connection
     * @return DB
     */
    public static function connection(string $connection): DB
    {
        return static::$instance->setConnection($connection);
    }

    /**
     * @param $value
     * @return string
     */
    public static function escape($value): string
    {
        if (is_null(static::$instance->connection)) {
            static::getInstance();
        }

        if (is_array($value) || is_object($value) || (!is_numeric($value) && !is_string($value) && !$value)) {
            return '';
        };

        return static::$instance->connection->quote($value);
    }

    /**
     * @return string
     */
    public static function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @return DB|null
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        if (is_null(static::$instance->connection)) {
            static::$instance->setConnection();
        }

        if (is_null(static::$instance->getQuery())) {
            static::$instance->setQuery(new QueryBuilder());
        }

        return static::$instance;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic(string $method, array $arguments)
    {
        if (method_exists(static::getInstance()->query, $method)) {
            return static::$instance->query->$method(...$arguments);
        } elseif (method_exists(static::getInstance(), $method)) {
            return static::$instance->$method(...$arguments);
        } else {
            throw new \Exception('Метод не объявлен');
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->query, $method)) {
            return $this->query->$method(...$arguments);
        } elseif (method_exists($this, $method)) {
            return $this->$method(...$arguments);
        } else {
            throw new \Exception('Метод не объявлен');
        }
    }
}