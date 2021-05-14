<?php

namespace App\System;

use App\System\Database\DB;
use App\Traits\PluralizeTrait;

final class Validator
{
    use PluralizeTrait;

    protected static $errors = [];

    /**
     * @param $request
     * @param array $validationArray
     * @return $this
     *
     * Функция принимает объект Router'а, и сравнивает, совпадают ли переданные в запросе данные с теми
     * что указаны в правилах массива items
     * @throws \Exception
     */

    public function validate(Request $request, array $validationArray = [])
    {
        $this->clearErrors();

        foreach ($validationArray as $field => $rules) {
            foreach ($rules as $rule => $rule_value) {

                $value = $request->get($field);

                switch ($rule) {
                    case 'required' && empty(trim($value)):
                        $this->addError(sprintf('%s.%s', $field, $rule),"Поле {$field} обязательно для заполнения");
                        break;
                    case 'min':
                        if(mb_strlen(trim($value)) < $rule_value) {
                            $this->addError(sprintf('%s.%s', $field, $rule),sprintf("\"{$field}\" должно быть больше {$rule_value} %s.", $this->pluralize(intval($rule_value), ['знака', 'знаков','знаков'])));
                        }
                        break;
                    case 'max':
                        if(mb_strlen(trim($value)) > $rule_value) {
                            $this->addError(sprintf('%s.%s', $field, $rule),sprintf("\"{$field}\" должно быть меньше {$rule_value} %s.", $this->pluralize(intval($rule_value), ['знака', 'знаков','знаков'])));
                        }
                        break;
                    case 'match':
                        if (trim($value) !== $request->{$rule_value}) {
                            $this->addError(sprintf('%s.%s', $field, $rule),"\"{$field}\" должно быть совпадать с {$rule_value}.");
                        }
                        break;
                    case 'unique':
                        $check = DB::table($rule_value)->where([$field => $value])->first();

                        if ($check) {
                            $this->addError(sprintf('%s.%s', $field, $rule),sprintf('Поле "%s" должно быть уникально. Указанные данные "%s" в таблице "%s" уже существуют', $field, $value, $rule_value));
                        }
                        break;
                    case 'exists':
                        $check = DB::table($rule_value)->where([$field => $value])->first();

                        if (!$check) {
                            $this->addError(sprintf('%s.%s', $field, $rule),sprintf('Данные с указанным значением "%s=%s" отсутствуют в таблице %s', $field, $value, $rule_value));
                        }

                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError(sprintf('%s.%s', $field, $rule),"\"{$field}\" должно быть валидным email-адресом.");
                        }
                        break;
                    case 'int':
                        if (!filter_var((int) $value, FILTER_VALIDATE_INT)) {
                            $this->addError(sprintf('%s.%s', $field, $rule),"\"{$field}\" должно иметь целочисленное значение.");
                        }
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * @param $item
     * @param $error
     *
     * Добавляет ошибку в массив по имени поля
     */

    protected function addError($item, $error)
    {
        if(!isset(static::$errors[$item])) {
            static::$errors[$item] = [];
        }

        static::$errors[$item][] = $error;
    }

    /**
     * @return array
     *
     * Получить весь массив ошибок
     */

    public function getErrors()
    {
        return static::$errors;
    }

    public function hasError(string $error)
    {
        return isset(static::$errors[$error]);
    }

    /**
     * @param string $error
     * @return mixed|null
     */
    public function getError(string $error)
    {
        return $this->hasError($error) ? static::$errors[$error] : null;
    }

    /**
     * Очищает массив ошибок
     */

    protected function clearErrors()
    {
        static::$errors = [];
    }

    /**
     * @return bool
     *
     * Проверяет, есть ли ошибки в щапросе
     */

    public function passed()
    {
        return count(static::$errors) === 0;
    }
}