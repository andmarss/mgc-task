<?php

use App\System\Template\View;

if (!function_exists('collect')) {
    /**
     * @param array $collection
     * @return \App\System\Collection
     */
    function collect($collection = []): \App\System\Collection
    {
        return new \App\System\Collection($collection);
    }
}

if (!function_exists('class_basename'))
{
    /**
     * @param $class
     * @return string
     */
    function class_basename($class)
    {
        $class = is_object($class) ? get_class($class) : $class;

        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('dd'))
{
    /**
     * @param mixed ...$data
     */
    function dd(...$data)
    {
        echo sprintf('<pre style="color: %s; background-color: %s">', 'orange', 'black');
        foreach ($data as $item) {
            echo print_r($item, true). '<br>';
        }
        echo '</pre>';
        die;
    }
}

if (!function_exists('dump'))
{
    /**
     * @param mixed ...$data
     */
    function dump(...$data)
    {
        echo sprintf('<pre style="color: %s; background-color: %s">', 'orange', 'black');
        foreach ($data as $item) {
            echo print_r($item, true) . '<br>';
        }
        echo '</pre>';
    }
}

if (!function_exists('response'))
{
    /**
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return \App\System\Response
     */
    function response(string $content = '', int $status = 200, array $headers = []): \App\System\Response
    {
        return new \App\System\Response($content, $status, $headers);
    }
}

if (!function_exists('root'))
{
    /**
     * @param string|null $path
     * @return string
     */
    function root(string $path = null): string
    {
        return !$path ? dirname(__FILE__) : dirname(__FILE__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, trim($path, '/'));
    }
}

if (!function_exists('config'))
{
    /**
     * @param string $configPath
     * @return mixed|null
     */
    function config(string $configPath)
    {
        return \App\System\Config::get($configPath);
    }
}

if (!function_exists('slug'))
{
    /**
     * Кирилицу в латиницу
     * @param string $value
     * @return string
     */
    function slug(string $value): string
    {
        $cyrillic = [
            'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
            'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
            'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'
        ];

        $latin = [
            'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p',
            'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya',
            'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P',
            'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'
        ];

        return strtolower(preg_replace('/[\s]+/', '-', str_replace($cyrillic, $latin, $value)));
    }
}

if (!function_exists('factory'))
{
    /**
     * @param string $class
     * @param int $num
     * @return \App\System\Factory\Factory
     */
    function factory(string $class, int $num = 1): \App\System\Factory\Factory
    {
        return new App\System\Factory\Factory($class, $num);
    }
}

if (!function_exists('root')) {
    /**
     * @param string|null $path
     * @return string
     */
    function root(string $path = null): string
    {
        return !$path ? dirname(__FILE__) : (dirname(__FILE__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    }
}

if (!function_exists('session')) {
    /**
     * @param array $data
     * @return \App\System\Session
     */
    function session(array $data = []): \App\System\Session
    {
        $session = \App\System\Session::getInstance();

        if ($data) {
            foreach ($data as $key => $value) {
                $session->put($key, $value);
            }
        }

        return $session;
    }
}

if (!function_exists('view')) {
    /**
     * @param string $path
     * @param array $data
     * @return View
     */
    function view(string $path, array $data = []): View
    {
        if(strpos($path, '.view.php')){
            return ( new View("{$path}", $data) );
        } else {
            return ( new View("{$path}.view.php", $data) );
        }
    }
}

if (!function_exists('domain')) {
    /**
     * @param string $url
     * @return string
     */
    function domain(string $url = ''): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];

        return sprintf('%s/%s', $protocol . $domainName, ltrim($url, '/'));
    }
}

if (!function_exists('route')) {
    /**
     * @param string $name
     * @param array $params
     * @return string
     * @throws Exception
     */
    function route(string $name, array $params = []): string
    {
        return domain(\App\Routing\Router::convertUri($name, $params));
    }
}

if (!function_exists('asset')) {
    /**
     * @param string $path
     * @return string
     */
    function asset(string $path): string
    {
        return domain('/public/' . trim($path, '/'));
    }
}

if (!function_exists('response'))
{
    /**
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return \App\System\Response
     */
    function response(string $content = '', int $status = 200, array $headers = []): \App\System\Response
    {
        return new \App\System\Response($content, $status, $headers);
    }
}

if (!function_exists('request')) {
    /**
     * @return \App\System\Request
     */
    function request(): \App\System\Request
    {
        return \App\System\Request::current();
    }
}

if (!function_exists('config')) {
    /**
     * @param array $config
     * @return \App\System\Config
     */
    function config(array $config = []): \App\System\Config {
        return new \App\System\Config($config);
    }
}

if (!function_exists('redirect')) {
    /**
     * @param string $path
     * @param array $data
     * @return \App\System\Redirect
     */
    function redirect(string $path = '', array $data = []): \App\System\Redirect
    {
        return new \App\System\Redirect($path, $data);
    }
}

if (!function_exists('url')) {
    /**
     * @param bool $ignore_query
     * @return string
     */
    function url(bool $ignore_query = false): string
    {
        return $ignore_query ? domain() . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) :
            domain() . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
            . '?' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY));
    }
}

if (!function_exists('build_url_query')) {
    /**
     * @param array $rules
     * @return string
     */
    function build_url_query(array $rules): string
    {
        $query = http_build_query(array_merge($_GET, $rules), '', '&');

        return url(true) . '?' . $query;
    }
}

if (!function_exists('pagination')) {
    /**
     * @param int $per_page
     * @param int $total
     * @return \App\System\Pagination
     */
    function pagination(int $per_page = 10, int $total = 1): \App\System\Pagination
    {
        return new \App\System\Pagination($per_page, $total);
    }
}

if (!function_exists('pluralize')) {
    /**
     * @param int $number
     * @param array $words
     * @return string
     */
    function pluralize(int $number, array $words): string
    {
        if($number % 10 == 1 && $number % 100 != 11) $plural = 0;
        else $plural = $number % 10 >= 2 && $number % 10 <= 4 && ( $number % 100 < 10 || $number % 100 >= 20 ) ? 1 : 2;

        return $words[$plural];
    }
}