<?php

namespace App\Models;

use App\System\Collection;
use App\System\Database\DB;
use App\System\Database\QueryBuilder;
use App\System\Database\Relations\Pivot;
use App\Traits\CallToTrait;
use App\Traits\CastsTrait;
use App\Traits\MigrationTraits\UnderscoreAndCamelCaseTrait;
use App\Traits\Relations;

abstract class Model implements \JsonSerializable
{
    use Relations;
    use UnderscoreAndCamelCaseTrait;
    use CastsTrait;
    use CallToTrait;

    /**
     * @var string $table
     */
    protected $table;
    /**
     * @var string $primaryKey
     */
    protected $primaryKey = 'id';
    /**
     * @var bool $exists
     */
    public $exists = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';
    /**
     * @var array $fillable
     */
    protected $fillable = [];

    protected $dates = [];

    protected $data = [];

    protected $with = [];

    protected $dateFormat;

    protected $query;

    /**
     * @var DB $db
     */
    protected static $db;

    protected static $unguarded = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes)->setConnection();
    }

    /**
     * @param array $attributes
     * @param bool $exists
     * @return Model
     */
    public static function newInstance($attributes = [], bool $exists = false): Model
    {
        $model = new static($attributes);

        $model->exists = $exists;

        return $model;
    }

    public static function query(): QueryBuilder
    {
        return DB::getInstance()->newQuery()->getQuery()->setModel(new static());
    }

    /**
     * @param int $id
     * @return Model|null
     * @throws \Exception
     */
    public static function find(int $id)
    {
        $instance = new static();

        return static::query()
            ->setModel($instance)
            ->where([$instance->getKeyName() => $id])
            ->first();
    }

    /**
     * @param $relations
     * @return QueryBuilder
     * @throws \Exception
     */
    public static function with($relations)
    {
        return static::query()->with($relations);
    }

    /**
     * @return Collection
     */
    public static function all(): Collection
    {
        $instance = new static();

        return DB::getInstance()
            ->getQuery()
            ->setModel($instance)
            ->get();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public static function count(): int
    {
        return DB::getInstance()
            ->setQuery(new QueryBuilder())
            ->getQuery()
            ->setModel(new static())
            ->count();
    }

    /**
     * @param array $attributes
     * @return Collection|bool|int
     * @throws \Exception
     */
    protected function create(array $attributes)
    {
        return $this->getQuery()
            ->create($attributes)
            ->execute();
    }

    /**
     * @param array $attributes
     * @return bool
     * @throws \Exception
     */
    public function update(array $attributes = []): bool
    {
        if (count($attributes) > 0) {
            return $this->getQuery()
                ->update($this->fromFillable($attributes))
                ->save();
        } elseif (count($this->data)) {
            $attributes = [];

            foreach ($this->fromFillable($this->data) as $key => $value) {
                $attributes[$key] = $value;
            }

            $this->reset();

            return $this->update($attributes);
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function save()
    {
        $attributes = [];

        foreach ($this->fromFillable($this->data) as $key => $value) {
            $attributes[$key] = $value;
        }

        $this->reset();

        if (!is_null($this->getKey())) {
            return $this->update($attributes);
        } else {
            return $this->getQuery()->create($attributes)->execute();
        }
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        return DB::getInstance()
            ->setModel($this)
            ->delete()
            ->where([$this->primaryKey => $this->{$this->primaryKey}])
            ->execute();
    }

    /**
     * @param array $attributes
     * @return $this
     * @throws \Exception
     */
    public function fill(array $attributes)
    {
        if (count($attributes) === 0) return $this;

        foreach ($this->fromFillable($attributes) as $key => $value) {
            $this->setData($key, $value);
        }

        if (count($this->with) > 0) {
            $this->loadRelations();
        }

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this|mixed
     */
    public function forceFill(array $attributes)
    {
        if (count($attributes) === 0) return $this;

        $model = $this;

        return static::unguarded(function () use ($model, $attributes) {
            return $model->fill($attributes);
        });
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function fromFillable(array $attributes): array
    {
        if (count($this->fillable) > 0 && !static::$unguarded) {
            return collect($attributes)->filter(function ($value, string $key) {
                return in_array($key, $this->fillable) || $key === $this->getPrimary();
            })->all();
        }

        return $attributes;
    }

    public function setConnection()
    {
        static::$db = DB::getInstance();

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function setTable(string $table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * @param null $key
     * @return $this
     */
    public function reset($key = null)
    {
        if (!is_null($key) && isset($this->data[$key])) {
            unset($this->data[$key]);
        } elseif (is_null($key)) {
            $this->data = [];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPrimary(): string
    {
        return $this->primaryKey;
    }

    /**
     * @return string
     */
    protected function getDateFormat(): string
    {
        return $this->dateFormat ?: DB::getDateFormat();
    }

    public function getQuery(): QueryBuilder
    {
        return DB::getInstance()
            ->setQuery(new QueryBuilder())
            ->getQuery()
            ->setModel($this);
    }

    /**
     * @param $key
     * @param $value
     * @throws \Exception
     */
    public function __set($key, $value)
    {
        $this->setData($key, $value);
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function __get($key)
    {
        return $this->getData($key);
    }

    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function getExistsAttribute(): bool
    {
        return !is_null($this->{$this->primaryKey}) && $this->getQuery()->where([$this->primaryKey => $this->{$this->primaryKey}])->count() > 0;
    }

    public static function unguarded(\Closure $callback)
    {
        if (static::$unguarded) return $callback();

        static::unguard();

        try {
            return $callback();
        } finally {
            static::reguard();
        }
    }

    public static function __callStatic(string $method, array $arguments)
    {
        /**
         * @var QueryBuilder $query
         */
        $query = static::query();
        $instance = new static();

        if (method_exists($query, $method) && !method_exists($instance, $method)) {
            return $query->setModel($instance)->$method(...$arguments);
        } elseif (method_exists($instance, $method)) {
            return $instance->$method(...$arguments);
        } else {
            throw new \BadMethodCallException(sprintf('Метод %s не объявлен в классе %s', $method, __CLASS__));
        }
    }

    public function __call(string $method, array $arguments)
    {
        /**
         * @var QueryBuilder $query
         */
        $query = $this->getQuery();

        if (method_exists($query, $method) && !method_exists($this, $method)) {

            return $query->$method(...$arguments);

        } elseif (method_exists($this, sprintf('scope%s', ucfirst($method)))) {
            return $this->{sprintf('scope%s', ucfirst($method))}($query, ...$arguments);
        } else {
            throw new \BadMethodCallException(sprintf('Метод %s не объявлен в классе %s', $method, get_class($this)));
        }
    }

    /**
     * @return string
     */
    public function getForeignKey(): string
    {
        return sprintf(
            '%s_id',
            str_replace('App/Models', '', $this->camelCaseToUnderScore(class_basename($this)))
        );
    }

    /**
     * @return string
     */
    public function getQualifiedKeyName(): string
    {
        return $this->table . '.' . $this->primaryKey;
    }

    /**
     * @return string|null
     */
    public function getKeyName(): ?string
    {
        return !is_null($this->primaryKey) ? $this->primaryKey : $this->getForeignKey();
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function getKey()
    {
        return $this->getData($this->getKeyName());
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->fromFillable($this->data);
    }

    public static function unguard()
    {
        static::$unguarded = true;
    }

    public static function reguard()
    {
        static::$unguarded = false;
    }

    /**
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @return Pivot
     * @throws \Exception
     */
    public function newPivot($attributes, string $table, bool $exists = false)
    {
        return (new Pivot($attributes, $table, $exists));
    }

    /**
     * @return array
     */
    public function getAllData(): array
    {
        return $this->data;
    }
}