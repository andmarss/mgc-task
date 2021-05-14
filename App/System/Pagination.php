<?php

namespace App\System;

class Pagination
{
    protected $per_page;
    protected $current;
    protected $total;
    protected $range;
    protected $showText;

    /**
     * Pagination constructor.
     * @param int $per_page
     * @param int $total
     * @param int $current
     * @param int $range
     */
    public function __construct(int $per_page = 20, int $total = 1, int $current = 1, $range = 1)
    {
        $this->per_page = $per_page;
        $this->current = isset($_GET['page']) ? (intval($_GET['page'])) : $current;
        $this->total = $total;
        $this->range = $range;
        $this->showText = false;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Сколько элементов отображается на странице
     *
     * @param int $per_page
     * @return $this
     */

    public function setPerPage(int $per_page = 10)
    {
        $this->per_page = $per_page;

        return $this;
    }

    /**
     * @param int $total
     * @return $this
     *
     * Устанавливает общее количество элементов
     */

    public function setTotal(int $total = 1)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @param int $range
     * @return $this
     *
     * Устанавливает разрыв, по которому будут проставляться ...
     */

    public function setRange(int $range = 1)
    {
        $this->range = $range;

        return $this;
    }

    /**
     * @return int
     *
     * Возвращает текущий индекс
     */

    protected function getCurrent(): int
    {
        return $this->current;
    }

    /**
     * @return $this
     *
     * Устанавливает текущий индекс
     */

    public function setCurrent()
    {
        if(!is_null($_GET['page'])) {
            $this->current = (int) $_GET['page'];
        } else {
            $this->current = 1;
        }

        return $this;
    }

    /**
     * @return int
     *
     * Возвращает номер следующей страницы
     */

    protected function nextPage(): int
    {
        return $this->getCurrent() < $this->totalPages() ? ($this->getCurrent()+1) : $this->getCurrent();
    }

    /**
     * @return int
     *
     * Возвращает номер предыдущей страницы
     */

    protected function prevPage(): int
    {
        return $this->getCurrent() > 1 ? ($this->getCurrent()-1) : $this->getCurrent();
    }

    /**
     * @return int
     *
     * общее количество страниц
     */

    protected function totalPages(): int
    {
        return (int) ceil($this->total / $this->per_page);
    }

    /**
     * @return bool
     *
     * Возвращает, есть ли у текущего индекса предыдущие
     */

    protected function hasPrev(): bool
    {
        return $this->current > 1;
    }

    /**
     * @return bool
     *
     * Возвращает, есть ли у текущего индекса следующие
     */

    protected function hasNext(): bool
    {
        return $this->current < $this->totalPages();
    }

    /**
     * @return int
     *
     * Возвращает разрыв между элементами в начале
     */

    protected function rangeStart(): int
    {
        return ($this->current - $this->range) > 0 ? $this->current - $this->range : 1;
    }

    /**
     * @return int
     *
     * Возвращает разрыв между элементами в конце
     */

    protected function rangeEnd(): int
    {
        return ($this->current + $this->range) < $this->totalPages() ? $this->current + $this->range : $this->totalPages();
    }

    /**
     * @return array
     *
     * Устанавливает нумерацию страниц
     */

    protected function pages(): array
    {
        $pages = [];

        for($i = $this->rangeStart(); $i <= $this->rangeEnd(); $i++) {
            $pages[] = $i;
        }

        if(!count($pages)) {
            $pages[] = 1;
        }

        return $pages;
    }

    /**
     * @return bool
     *
     * Возвращает true, по которому скрываются <<
     */

    protected function hasFirst(): bool
    {
        return $this->rangeStart() !== 1;
    }

    /**
     * @return bool
     * Возвращает true, по которому скрываются >>
     */

    protected function hasLast(): bool
    {
        return $this->rangeEnd() < $this->totalPages();
    }

    /**
     * @param bool|null $show
     * @return $this
     */
    public function showText(bool $show): Pagination
    {
        if (is_bool($show)) {
            $this->showText = $show;
        }

        return $this;
    }

    /**
     * @return string
     *
     * Генерирует html-структуру для пагинации
     */

    public function render(): string
    {
        if ($this->total < $this->per_page) return '';

        /**
         * @var string $html
         */
        $html = '';
        /**
         * @var array $pages
         */
        $pages = $this->pages();
        /**
         * @var null|int $page
         */
        $page = null;
        // если количество страниц === 1, то скрываем пагинацию
        $html .= '<ul class="pagination' . ((count($pages) === 1 && (!$this->hasNext() && !$this->hasPrev())) ? ' hidden' : '') . '" id="' .  '">';

        if($this->hasPrev()) {
            $html .= '<li><a href="' . build_url_query(['page' => $this->prevPage()]) . '">&#171;</a></li>';
        }

        if($this->hasFirst()) {
            $html .= '<li class="' . ($this->getCurrent() === 1 ? 'active' : '') . '"><a href="' . build_url_query(['page' =>  1]) .'">1</a></li>';
            // троеточие между кнопкой первой страницы и текущей
            if(($this->getCurrent() - $this->range) > 2) {
                $html .= '<li><a href="javascript:void(0);">...</a></li>';
            }
        }

        for($i = 0; $i < count($pages); $i++) {
            $page = $pages[$i];

            $html .= '<li class="' . ($this->getCurrent() === $page ? 'active' : '') . '"><a href="'. build_url_query(['page' => $page]) . '">' . $page . '</a></li>';
        }

        if($this->hasLast()) {
            // троеточие между текущей кнопкой страницы и последней
            if((1 + ($this->getCurrent() + $this->range)) < $this->totalPages()) {
                $html .= '<li><a href="javascript:void(0);">...</a></li>';
            }

            $html .= '<li class="' . ($this->getCurrent() === $this->totalPages() ? 'active' : '') . '"><a href="' . build_url_query(['page' => $this->totalPages()]) . '">' . $this->totalPages() . '</a></li>';
        }

        if($this->hasNext()) {
            $html .= '<li><a href="'. build_url_query(['page' => $this->nextPage()]) . '">&#187;</a></li>';
        }

        $html .= '</ul>';

        if ($this->showText) {
            /**
             * @var int $from
             */
            $from = (($this->current - 1) * $this->per_page) + 1;
            /**
             * @var int $to
             */
            $to = $this->current * $this->per_page;
            /**
             * @var int $of
             */
            $of = $this->total;
            /**
             * @var int $totalPages
             */
            $totalPages = $this->totalPages();
            // если $to больше общего количества сущностей
            // присваиваем общее количество сущностей
            if ($to > $this->total) {
                $to = $this->total;
            }

            $html .= sprintf('<div class="results">Показано с %d по %d из %d (всего страниц: %d)</div>', $from, $to, $of, $totalPages);
        }

        return $html;
    }
}