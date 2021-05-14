<?php

namespace App\Http;

use App\System\Request;
use App\System\Validator;

class Controller
{
    /**
     * @var Validator $validator
     */
    protected $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    /**
     * @param Request $request
     * @param array $rules
     * @return Validator
     */
    protected function validate(Request $request, array $rules = [])
    {
        return $this->validator->validate($request, $rules);
    }
}