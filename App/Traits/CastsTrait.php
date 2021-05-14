<?php

namespace App\Traits;

use App\System\Database\Relations\Relation;

trait CastsTrait
{
    protected $casts = [];

    protected $relations = [];

    /**
     * @param $key
     * @return mixed|void
     * @throws \Exception
     */
    public function getData($key)
    {
        if (!$key) return null;

        if (array_key_exists($key, $this->data) || $this->hasGetMutator($key)) {
            return $this->getDataValue($key);
        }

        if (method_exists(self::class, $key)) return;

        return $this->getRelation($key);
    }

    /**
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    protected function getDataValue($key)
    {
        $value = $this->data[$key] ?? null;

        if ($this->hasGetMutator($key)) {
            return $this->getMutatedDataValue($key, $value);
        }

        if ($this->hasCast($key)) {
            return $this->cast($key, $value);
        }

        if (in_array($key, $this->dates) && !is_null($value)) {
            return $this->dateTime($value);
        }

        return $value;
    }

    /**
     * @param $key
     * @return bool
     */
    protected function hasGetMutator($key): bool
    {
        return method_exists($this, 'get' . $this->underscoreToCamelCase($key) . 'Attribute');
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function getMutatedDataValue($key, $value)
    {
        return $this->{'get' . $this->underscoreToCamelCase($key) . 'Attribute'}($value);
    }

    /**
     * @param $key
     * @param array|null $types
     * @return bool
     */
    protected function hasCast($key, ?array $types = null): bool
    {
        if (array_key_exists($key, $this->casts)) {
            return $types ? in_array($this->getCastType($key), $types, true) : true;
        }

        return false;
    }

    /**
     * @param $key
     * @return string
     */
    protected function getCastType($key): string
    {
        if (strpos('decimal:', trim(strtolower($key))) === 0) {
            return 'decimal';
        }

        return trim(strtolower($this->casts[$key]));
    }

    /**
     * @param $key
     * @param $value
     * @return \App\System\Collection|bool|\DateTime|float|int|mixed|string
     * @throws \Exception
     */
    protected function cast($key, $value)
    {
        if (is_null($value)) return $value;

        $key = trim(strtolower($key));

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return intval($value);
                break;
            case 'real':
            case 'float':
            case 'double':
                return floatval($value);
                break;
            case 'decimal':

                if (strpos('decimal:', $this->casts[$key]) === 0) {
                    $decimals = current(array_reverse(explode(':', $this->casts[$key], 2)));
                } else {
                    $decimals = 2;
                }

                return number_format($value, $decimals, '.', '');
                break;
            case 'string':
                return strval($value);
                break;
            case 'bool':
            case 'boolean':
                return boolval($value);
                break;
            case 'object':
                return json_decode($value);
                break;
            case 'array':
            case 'json':
                return json_decode($value, true);
                break;
            case 'collection':
                return collect(json_decode($value, true));
                break;
            case 'date':
                return $this->date($value);
                break;
            case 'datetime':
                return $this->dateTime($value);
                break;
            case 'timestamp':
                return $this->timeStamp($value);
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * @param $value
     * @return bool|\DateTime
     * @throws \Exception
     */
    protected function date($value)
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', (new \DateTime($value))->format('Y-m-d 00:00:00'));
    }

    /**
     * @param $value
     * @return bool|\DateTime
     */
    protected function dateTime($value)
    {
        if (!$value) return $value;

        $format = $this->getDateFormat();

        // https://bugs.php.net/bug.php?id=75577
        if (version_compare(PHP_VERSION, '7.3.0-dev', '<')) {
            $format = str_replace('.v', '.u', $format);
        }

        return \DateTime::createFromFormat($format, $value);
    }

    /**
     * @param $value
     * @return int
     */
    protected function timeStamp($value)
    {
        return $this->dateTime($value)->getTimestamp();
    }

    /**
     * @param $key
     * @param $value
     * @return $this|mixed
     * @throws \Exception
     */
    public function setData($key, $value)
    {
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedDataValue($key, $value);
        } elseif ($value && in_array($key, $this->dates)) {
            $value = $this->fromDateTime($value);
        }

        if ($this->isJsonCast($key) && !is_null($value) && is_array($value)) {
            $value = json_encode($value);

            if ($value === false) throw new \Exception("Ошибка при кодировке аттрибута (" . $key . ") для модели (" . get_class($this) . ") в JSON-формат: " . json_last_error_msg());
        }

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    protected function hasSetMutator($key): bool
    {
        return method_exists($this, 'set' . $this->underscoreToCamelCase($key) . 'Attribute');
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function setMutatedDataValue($key, $value)
    {
        return $this->{'set' . $this->underscoreToCamelCase($key) . 'Attribute'}($value);
    }

    /**
     * @param $value
     * @return string
     */
    protected function fromDateTime($value)
    {
        return (!$value) ? $value : $this->dateTime($value)->format(
            $this->getDateFormat()
        );
    }

    /**
     * @param $key
     * @return bool
     */
    protected function isJsonCast($key): bool
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * @param $relation
     * @throws \Exception
     */
    public function loadRelation($relation)
    {
        if (is_string($relation)) $relation = (array) $relation;

        foreach ($relation as $item) {
            if ($this->hasRelation($item)) {
                $this->getRelation($item);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function loadRelations()
    {
        if (count($this->with)) {
            foreach ($this->with as $relation) {
                $this->getRelation($relation);
            }
        }
    }

    /**
     * @param $name
     * @return bool
     */
    protected function hasRelation($name)
    {
        if (is_array($name)) {
            return collect($name)->every(function ($name) {
                return (array_key_exists($name, $this->relations)) || (method_exists($this, $name) && ($this->{$name}() instanceof Relation));
            });
        }

        return (array_key_exists($name, $this->relations)) || (method_exists($this, $name) && ($this->{$name}() instanceof Relation));
    }

    /**
     * @param $method
     * @return mixed
     * @throws \Exception
     */
    protected function getRelation($method)
    {
        if (array_key_exists($method, $this->relations)) return $this->relations[$method];
        if (!method_exists($this, $method)) return null;

        /**
         * @var Relation $relation
         */
        $relation = $this->{$method}();

        if (!($relation instanceof Relation)) {
            if (is_null($relation)) throw new \Exception(sprintf('%s::%s должен был вернуть экземпляр отношения, но вернулся null', static::class, $method));
            throw new \Exception(sprintf('%s::%s должен был вернуть экземпляр отношения', static::class, $method));
        }

        $this->relations[$method] = $relation->getResults();

        return $this->relations[$method];
    }

    /**
     * @param $relation
     * @param $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }
}