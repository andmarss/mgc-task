<?php

namespace Seeds;

use App\Api\MGCApi;
use App\Models\Category;
use App\Models\Model;
use App\Models\Parameter;
use App\System\Database\Seeder;

class CategoryTableSeeder extends Seeder
{
    protected $api;

    public function __construct()
    {
        parent::__construct();

        $this->api = new MGCApi();
    }

    public function run(): void
    {
        $categories = $this->api->getCategories();

        foreach ($categories as $category) {

            if (isset($category['@attributes']) && isset($category['@attributes']['parentId']) && $category['@attributes']['parentId']) {
                /**
                 * @var Category|null $parent
                 */
                $parent = Category::where(['mgc_id' => $category['@attributes']['parentId']])->first();
            } else {
                $parent = null;
            }

            /**
             * @var Category $newCategory
             */
            $newCategory = Category::create([
                'name' => $category['name'],
                'mgc_id' => isset($category['@attributes']) && isset($category['@attributes']['id']) ? $category['@attributes']['id'] : null,
                'parent_id' => $parent ? $parent->category_id : null,
            ]);

            if (isset($category['Params']) && isset($category['Params']['Param']) && $category['Params']['Param']) {
                foreach ($category['Params']['Param'] as $param) {
                    if (!isset($param['name'])) continue;

                    $pivotAttributes = [];

                    if (is_array($param['value'])) {
                        $pivotAttributes['array_value'] = $param['value'];
                    } else {
                        $pivotAttributes['value'] = $param['value'];
                    }

                    if (Parameter::query()->where(['mgc_id' => $param['@attributes']['id'], 'name' => $param['name']])->count() > 0) {
                        /**
                         * @var Parameter $parameter
                         */
                        $parameter = Parameter::query()->where(['mgc_id' => $param['@attributes']['id'], 'name' => $param['name']])->first();

                        $newCategory->parameters()->attach($parameter->parameter_id, $pivotAttributes);
                    } else {
                        /**
                         * @var Parameter $parameter
                         */
                        $parameter = Parameter::create([
                            'mgc_id' => $param['@attributes']['id'],
                            'name' => $param['name']
                        ]);

                        $newCategory->parameters()->attach($parameter->parameter_id, $pivotAttributes);
                    }
                }
            }
        }
    }
}