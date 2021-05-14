<?php

namespace App\Traits\MigrationTraits;

trait UnderscoreAndCamelCaseTrait
{
    /**
     * Меняет строку с нижним_подчеркиванием на ВерблюжийРегистр
     * @param string $input
     * @param string $separator
     * @return string
     */
    protected function underscoreToCamelCase(string $input, string $separator = '_'): string
    {
        return str_replace($separator, '', ucwords($input, $separator));
    }

    /**
     * Меняет строку с ВерблюжьимРегистром на строку_с_нижним_подчеркиванием
     *
     * @param string $input
     * @return string
     */
    protected function camelCaseToUnderScore(string $input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);

        $result = current($matches);

        foreach ($result as &$chunk) {
            $chunk = $chunk === strtoupper($chunk) ? strtolower($chunk) : lcfirst($chunk);
        }

        return implode('_', $result);
    }
}