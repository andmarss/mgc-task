<?php

namespace App\System\Factory;
/**
 * @property string $name
 * @property string $middleName
 * @property string $lastName
 * @property string $fullName
 * @property string $firstNameLastName
 * @property string $date
 * @property string $dateTime
 * @property string $timestamp
 * @property string $dateBetween
 * @property string $word
 * @property string $words
 * @property string $sentence
 * @property string $paragraph
 * @property string $text
 * @property string $email
 * @property string $safeEmail
 * @property int $numberBetween
 * @property bool $randomBool
 *
 * @method string name(bool $male = true)
 * @method string middleName(bool $male = true)
 * @method string lastName(bool $male = true)
 * @method string fullName(bool $male = true)
 * @method string firstNameLastName(bool $male = true)
 * @method mixed randomElement(array $arr)
 * @method mixed randomElements(array $arr, int $num = 2)
 * @method string date(string $date = 'now', string $modify = 'now')
 * @method string dateTime(string $date = 'now', string $modify = 'now')
 * @method string timestamp(string $date = 'now', string $modify = 'now')
 * @method string dateBetween(string $from, string $to, string $format = 'Y-m-d H:i:s')
 * @method string word()
 * @method string words(int $num = 3)
 * @method string sentence(int $num = 7)
 * @method string paragraph(int $num = 15)
 * @method string text(int $num = 200)
 * @method string email(string $domain = '')
 * @method string safeEmail(string $domain = '')
 * @method int numberBetween(int $min = 1, int $max = 100)
 * @method bool randomBool()
 */
class Generator
{
    /**
     * @var Formatter $formatter
     */
    protected $formatter;

    public function __construct()
    {
        $this->formatter = new Formatter();
    }

    public function __get($key)
    {
        if (method_exists($this->formatter, $key)) {
            return $this->formatter->$key();
        }
    }

    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->formatter, $method)) {
            return $this->formatter->$method(...$arguments);
        }
    }
}