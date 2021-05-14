<?php

namespace Seeds;

use App\Api\MGCApi;
use App\Models\Category;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\ProductDescription;
use App\System\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    protected $api;

    public function __construct()
    {
        parent::__construct();

        $this->api = new MGCApi();
    }

    public function run(): void
    {
        Category::all()->each(function (Category $category) {
            $products = $this->api->getProduct(intval($category->mgc_id));

            if (!$products) return;

            foreach ($products as $product) {
                if (!isset($product['Name'])
                    || !isset($product['Price'])
                    || !isset($product['Vendor'])
                    || !isset($product['InStock'])
                    || !isset($product['Available'])
                    || !isset($product['Downloadable'])
                    || !isset($product['Price'])) continue;
                /**
                 * @var Product $newProduct
                 */
                $newProduct = Product::create([
                    'name' => $product['Name'],
                    'vendor' => $product['Vendor'],
                    'brand' => $product['Model'],
                    'prefix' => $product['TypePrefix'],
                    'in_stock' => intval($product['InStock']),
                    'available' => $product['Available'] === 'true',
                    'downloadable' => $product['Downloadable'] === 'true',
                    'price' => floatval($product['Price']),
                    'discount' => isset($product['DiscountPrice']) ? (floatval($product['Price']) - floatval($product['DiscountPrice'])) : 0,
                    'category_id' => $category->category_id,
                    'mgc_id' => $product['Id']
                ]);

                if ($newProduct) {
                    ProductDescription::create([
                        'product_id' => $newProduct->product_id,
                        'picture' => isset($product['Picture']) && $product['Picture'] ? $product['Picture'] : null,
                        'annotation' => isset($product['Annotation']) && $product['Annotation'] ? $product['Annotation'] : null,
                        'terms_conditions' => isset($product['TermsConditions']) && $product['TermsConditions'] ? $product['TermsConditions'] : null,
                        'activation_rules' => isset($product['ActivationRules']) && $product['ActivationRules'] ? $product['ActivationRules'] : null,
                        'terms_of_use' => isset($product['TermsOfUse']) && $product['TermsOfUse'] ? $product['TermsOfUse'] : null,
                    ]);

                    if (isset($product['Params']) && isset($product['Params']['Param']) && is_array($product['Params']['Param']) && count($product['Params']['Param']) > 0) {

                        foreach ($product['Params']['Param'] as $param) {
                            if (!isset($param['name'])) continue;

                            $pivotAttributes = [];

                            if (is_array($param['value'])) {
                                $pivotAttributes['array_value'] = $param['value'];
                            } else {
                                $pivotAttributes['value'] = $param['value'];
                            }

                            if (Parameter::query()->where(['name' => $param['name']])->count() > 0) {
                                /**
                                 * @var Parameter $parameter
                                 */
                                $parameter = Parameter::query()->where(['name' => $param['name']])->first();

                                $newProduct->parameters()->attach($parameter->parameter_id, $pivotAttributes);
                            } else {
                                /**
                                 * @var Parameter $parameter
                                 */
                                $parameter = Parameter::create([
                                    'name' => $param['name']
                                ]);

                                $newProduct->parameters()->attach($parameter->parameter_id, $pivotAttributes);
                            }
                        }
                    }
                }
            }
        });
    }
}