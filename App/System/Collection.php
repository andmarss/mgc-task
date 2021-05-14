<?php

namespace App\System;
use App\Contracts\Arrayable;
use ArrayAccess;
use Traversable;

/**
 * Class Collection
 * @package App\System
 */
class Collection implements \Countable, \ArrayAccess, \JsonSerializable, \IteratorAggregate, Arrayable
{
    protected $collection;

    public function __construct($countable = [])
    {
        $this->collection = $this->arrayable($countable);
    }

    /**
     * @param $items
     * @return array
     */
    public function arrayable($items): array
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof Collection) {
            return $items->all();
        } elseif ($items instanceof Arrayable) {
            return $items->toArray();
        } elseif ($items instanceof \JsonSerializable) {
            return (array) $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * @param \Closure $func
     * @return $this
     *
     * Аналог foreach
     */

    public function each(\Closure $func): Collection
    {
        foreach ($this->collection as $key => $item) {
            $func($item, $key, $this->collection);
        }

        return $this;
    }

    /**
     * @param \Closure $func
     * @return $this
     *
     * Аналог array_map
     */

    public function map(\Closure $func): Collection
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            $result[] = $func($item, $key, $this->collection);
        }

        $this->collection = $result;

        return $this;
    }

    /**
     * @param \Closure $func
     * @return $this
     *
     * Аналог array_filter
     */

    public function filter(\Closure $func): Collection
    {
        $result = [];

        foreach ($this->collection as $key => $item) {
            if($func($item, $key, $this->collection)) {
                if (!is_numeric($key)) {
                    $result[$key] = $item;
                } else {
                    $result[] = $item;
                }
            }
        }

        $this->collection = $result;

        return $this;
    }

    /**
     * @param $condition
     * @param bool $strict
     * @return int
     *
     * Возвращает количество совпавших в колекции по условию
     * Если condition - функция - вызывает ей в контексте функции filter
     */

    public function search($condition, $strict = false): int
    {
        /**
         * @var int $find
         */
        $find = 0;

        if(!is_callable($condition)) {
            if($strict) {
                foreach ($this->collection as $key => $item) {
                    if($condition === $item) {
                        $find++;
                    }
                }
            } else {
                foreach ($this->collection as $key => $item) {
                    if($condition == $item) {
                        $find++;
                    }
                }
            }

            return $find;
        } elseif (is_callable($condition)) {
            return $this->filter($condition)->count();
        }
    }

    /**
     * @param \Closure $func
     * @return $this
     *
     * Обратно действию функции filter
     */

    public function reject(\Closure $func): Collection
    {
        /**
         * @var array $result
         */
        $result = [];

        foreach ($this->collection as $key => $item) {
            if(!$func($item, $key, $this->collection)) {
                $result[] = $item;
            }
        }

        $this->collection = $result;

        return $this;
    }

    /**
     * @param \Closure $func
     * @param $initial
     * @return $this|mixed
     *
     * аналог array_reduce
     */

    public function reduce(\Closure $func, $initial)
    {
        $accumulator = $initial;

        foreach ($this->collection as $key => $item) {
            $accumulator = $func($accumulator, $item);
        }

        if(is_array($accumulator)) {
            $this->collection = $accumulator;

            return $this;
        }

        return $accumulator;
    }

    /**
     * @param null $key
     * @param null $initial
     * @return Collection|mixed
     */
    public function min($key = null, $initial = null)
    {
        return $this->reduce(function ($total, $item) use ($key) {
            $value = $key instanceof \Closure ? $key($item) : $this->dataGet($item, $key);

            return is_null($total) || $value < $total ? $value : $total;
        }, $initial);
    }

    /**
     * @param null $key
     * @param null $initial
     * @return Collection|mixed
     */
    public function max($key = null, $initial = null)
    {
        return $this->reduce(function ($total, $item) use ($key) {
            $value = $key instanceof \Closure ? $key($item) : $this->dataGet($item, $key);

            return is_null($total) || $value > $total ? $value : $total;
        }, $initial);
    }

    /**
     * @param $key
     * @param $value
     * @param bool $strict
     * @return Collection
     */
    public function where($key, $value, bool $strict = false): Collection
    {
        return $this->filter(function ($item) use ($key, $value, $strict) {
            return $strict ? $this->dataGet($item, $key) === $value : $this->dataGet($item, $key) == $value;
        });
    }

    /**
     * @param $target
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    protected function dataGet($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $segment) {
            if (is_array($target)) {
                if (! array_key_exists($segment, $target)) {
                    return $default instanceof \Closure  ? $default() : $default;
                }

                $target = $target[$segment];
            } elseif ($target instanceof ArrayAccess) {
                if (! isset($target[$segment])) {
                    return $default instanceof \Closure  ? $default() : $default;
                }

                $target = $target[$segment];
            } elseif (is_object($target)) {
                if (! isset($target->{$segment})) {
                    return $default instanceof \Closure  ? $default() : $default;
                }

                $target = $target->{$segment};
            } else {
                return $default instanceof \Closure  ? $default() : $default;
            }
        }

        return $target;
    }

    /**
     * @param int $num
     * @return mixed
     */
    public function random(int $num = null)
    {
        if (!$this->count()) return null;
        if (is_null($num)) $num = 1;
        if ($this->count() < $num) $num = $this->count() - 1;

        return $this->collection[array_rand((array) $this->collection, $num)];
    }

    /**
     * @param \Closure $func
     * @return Collection|mixed
     *
     * Суммирует значения колекции, и возвращает это значение
     */

    public function sum(\Closure $func)
    {
        return $this->reduce(function ($total, $item) use ($func){
            return $total + $func($item);
        }, 0);
    }

    /**
     * @return int
     *
     * Возвращает количество элементов в колекции
     */

    public function count()
    {
        return count($this->collection);
    }

    /**
     * @param $items
     * @return static
     *
     * Превращает массив в объект-экземпляр класса Collect
     */

    public static function make($items)
    {
        return (new static($items));
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->collection);
    }

    public function offsetGet($offset)
    {
        return $this->collection[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if(!is_null($offset)) {
            $this->collection[] = $offset;
        } else {
            $this->collection[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->collection[$offset]);
    }

    /**
     * @param \Closure $func
     * @return bool
     *
     * Принимает функцию
     *
     * Перебирает коллекцию, возвращает true, если все условия, выполненые в функции func - верны
     * Иначе - false
     */

    public function every(\Closure $func): bool
    {
        foreach ($this->collection as $key => $item) {
            if(!$func($item, $key, $this->collection)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Closure|mixed $key
     * @return bool
     */
    public function contains($key): bool
    {
        if ($key instanceof \Closure) {
            return count(array_filter($this->collection, $key)) > 0;
        } else {
            return in_array($key, $this->collection);
        }
    }

    /**
     * @param \Closure $func
     * @return bool
     *
     * Перебирает коллекцию, возвращает true, если хотя бы одно условие, выполненое в функции func - верно
     * Иначе - false
     */

    public function some(\Closure $func): bool
    {
        $i = 0;

        foreach ($this->collection as $key => $item) {
            if($func($item, $key, $this->collection)) {
                $i++;
            }
        }

        return $i !== 0;
    }

    /**
     * @param \Closure|null $callback
     * @return Collection
     */
    public function sort(\Closure $callback = null)
    {
        $items = $this->collection;

        $callback ? uasort($items, $callback) : asort($items);

        return new static($items);
    }

    /**
     * @param \Closure $callback
     * @param int $options
     * @param bool $descending
     * @return Collection
     */
    public function sortBy(\Closure $callback, $options = SORT_REGULAR, bool $descending = false)
    {
        $items = [];

        $callback = $this->useAsCallback($callback);

        $i = 0;

        foreach ($this->collection as $key => $value) {
            $items[$key] = $callback($value, $key, $i);
            ++$i;
        }

        $descending ? arsort($items, $options) : asort($items, $options);

        foreach (array_keys($items) as $key) {
            $items[$key] = $this->collection[$key];
        }

        return new static($items);
    }

    /**
     * @param $callback
     * @param int $options
     * @return Collection
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * @param int $options
     * @param bool $descending
     * @return Collection
     */
    public function sortKeys($options = SORT_REGULAR, bool $descending = false)
    {
        $items = $this->collection;

        $descending ? krsort($items, $options) : ksort($items, $options);

        return new static($items);
    }

    /**
     * @param int $options
     * @return Collection
     */
    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->sortKeys($options, true);
    }

    /**
     * @param $value
     * @return \Closure
     */
    protected function useAsCallback($value)
    {
        if (is_callable($value)) return $value;

        return function ($item) use ($value) {
            return $this->dataGet($item, $value);
        };
    }

    /**
     * @param $item
     * @return $this
     */
    public function add($item)
    {
        $this->collection[] = $item;

        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     *
     * Вернуть массив коллекции
     */

    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->collection[$key];
        }

        return $default instanceof \Closure ? $default() : $default;
    }

    /**
     * @return array
     *
     * Аналог get
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @return $this
     *
     * Аналог array_keys
     */

    public function values(): Collection
    {
        $this->collection = array_values($this->collection);

        return $this;
    }

    /**
     * @param null $key
     * @param bool $strict
     * @return Collection
     */
    public function unique($key = null, bool $strict = false)
    {
        $callback = $this->useAsCallback($key);

        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }

            $exists[] = $id;
        });
    }

    /**
     * @return $this
     *
     * Аналог array_values
     */

    public function keys(): Collection
    {
        $this->collection = array_keys($this->collection);

        return $this;
    }

    /**
     * @param int $num
     * @return $this
     *
     * Аналог array_chunk
     */

    public function chunk(int $num)
    {
        $this->collection = array_chunk((array) $this->collection, $num);

        return $this;
    }

    /**
     * @return mixed|null
     *
     * Возвращает первый элемент коллекции, или null
     */

    public function first()
    {
        return $this->count() > 0 ? current((array) $this->collection) : null;
    }

    /**
     * @return array|null
     *
     * Возвращает последний элемент коллекции, или null
     */

    public function last()
    {
        return count((array) $this->collection) > 0 ? current(array_slice((array) $this->collection, -1)) : null;
    }

    /**
     * @param int $from
     * @param int $to
     * @return Collection
     *
     * Аналог array_slice
     */

    public function slice(int $from = 0, int $to = 1): Collection
    {
        $this->collection = array_slice((array) $this->collection, $from, $to);

        return $this;
    }

    /**
     * @return false|mixed|string
     */
    public function jsonSerialize()
    {
        return json_encode($this->collection);
    }

    /**
     * @return \ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return $value instanceof Arrayable ? $value->toArray() : $value;
        }, $this->collection);
    }
}