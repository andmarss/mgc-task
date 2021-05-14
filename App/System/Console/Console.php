<?php


namespace App\System\Console;


class Console extends OutputColor
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Текст информации - зеленый текст, черный фон
     *
     * @param string $value
     */
    public function info(string $value)
    {
        echo $this->getColoredString($value, 'light_green');
    }

    /**
     * Ошибка - черный текст, красный фон
     *
     * @param string $value
     */
    public function error(string $value)
    {
        echo $this->getColoredString($value, 'black', 'red');
    }

    /**
     * Предупреждение - черный текст, желтый фон
     *
     * @param string $value
     */
    public function warning(string $value)
    {
        echo $this->getColoredString($value, 'black', 'yellow');
    }

    /**
     * @param string $question
     * @param bool $default
     * @return bool
     */
    public function confirm(string $question, $default = false): bool
    {
        $question = sprintf('%s (yes/no) [%s]', $question, $default ? 'yes' : 'no');

        $this->info($question);

        $confirmation = $this->getConfirmation();

        if ($confirmation === 'yes') {
            return true;
        } elseif ($confirmation === 'no') {
            return false;
        } else {
            return $default;
        }
    }

    /**
     * @param string $value
     * @param string $fontColor
     * @param string|null $backgroundColor
     * @return string
     */
    protected function getColoredString(string $value, string $fontColor, string $backgroundColor = 'black'): string
    {
        if (isset($this->font_color[$fontColor]) && isset($this->background_color[$backgroundColor])) {
            $result = sprintf("\033[%s%sm", $this->font_color[$fontColor], $this->background_color[$backgroundColor]);
        } else {
            $result = sprintf("\033[%s%sm", $this->font_color['white'], $this->background_color['black']);
        }

        $result .= sprintf("%s\033[0m", $value);

        return sprintf("%s\r\n", $result);
    }

    /**
     * @param string $command
     * @param array $arguments
     */
    public function call(string $command, array $arguments = [])
    {
        if (Invoker::has($command)) Invoker::run($command, $arguments);
    }

    /**
     * @return string
     */
    protected function getConfirmation()
    {
        $handle = fopen ("php://stdin","r");

        return trim(fgets($handle));
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $instance = new static();

        if (method_exists($instance, $method)) {
            return $instance->{$method}(...$arguments);
        }
    }
}