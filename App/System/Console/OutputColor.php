<?php

namespace App\System\Console;

abstract class OutputColor
{
    protected $font_color = [];
    protected $background_color = [];

    public function __construct()
    {
        $this->font_color['light_green'] = '1;32;';
        $this->font_color['red'] = '0;31;';
        $this->font_color['black'] = '1;37;';
        $this->font_color['white'] = '0;30;';

        $this->background_color['black'] = '40';
        $this->background_color['red'] = '41';
        $this->background_color['green'] = '42';
        $this->background_color['yellow'] = '43';
        $this->background_color['blue'] = '44';
        $this->background_color['magenta'] = '45';
        $this->background_color['cyan'] = '46';
        $this->background_color['light_gray'] = '47';
    }
}