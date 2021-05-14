<?php


namespace Service;


class XmlSettings
{
    public const OUTPUT = 'php://output';
    /**
     * Кодировка xml-файла
     *
     * @var string $encoding
     */
    protected $encoding = 'utf-8';
    /**
     * Строка разделитель
     *
     * @var string $indentString
     */
    protected $indentString = "\t";

    /**
     * Имя xml-файла. Если файла нет - ставится по умолчанию 'php://output'
     *
     * @var string $tpl
     */
    protected $tpl = null;
    /**
     * Использовать память вместо выгрузки в файл
     *
     * @var bool $memory
     */
    protected $memory = false;
    /**
     * @var string $fileName
     */
    protected $fileName = 'file.xml';

    /**
     * @param string $encoding
     * @return mixed
     */
    public function setEncoding(string $encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    /**
     * @param string $tpl
     * @return mixed
     */
    public function setTpl(string $tpl)
    {
        $this->tpl = $tpl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTpl(): ?string
    {
        return $this->tpl;
    }

    /**
     * @param string $indent
     * @return mixed
     */
    public function setIndentString(string $indent)
    {
        $this->indentString = $indent;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIndentString(): ?string
    {
        return $this->indentString;
    }

    /**
     * Выгрузить файл в память, и вернуть в виде строки
     *
     * @return $this
     */
    public function useMemory()
    {
        $this->memory = true;

        return $this;
    }

    /**
     * Не использовать память для выгрузки xml'а
     *
     * @return $this
     */
    public function dontUseMemory()
    {
        $this->memory = false;

        return $this;
    }

    /**
     * Получить значение переменно $memory
     *
     * @return bool
     */
    public function memory(): bool
    {
        return $this->memory;
    }

    /**
     * @param string $fileName
     * @return mixed
     */
    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }
}