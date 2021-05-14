<?php

namespace App\Http;

use App\Models\Category;
use App\Models\Product;
use App\System\Collection;
use App\System\Request;

class MainController extends Controller
{
    const PER_PAGE = 16;

    public function index()
    {
        $categories = Category::whereHas('products')->random(3)->get();

        return view('main')->with(['categoriesWithProducts' => $categories]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \App\System\Template\View|void
     * @throws \Exception
     */
    public function category(Request $request, $id)
    {
        /**
         * @var Category|null $category
         */
        $category = Category::query()->find($id);

        if (!$category) return redirect()->route('not-found');

        /**
         * @var int $page
         */
        $page = intval($request->page) ?: 1;
        // если продуктов у родительской категории нет - грузим продукты дочерних категорий
        if ($category->products()->count() === 0) {

            $children = $category->children;
            // если нет НИ детей НИ продуктов - нечего тут делать =D
            if ($children->count() === 0) return redirect()->back();

            $total = 0;
            $ids = [];

            /**
             * @var Category $child
             */
            foreach ($children as $child) {
                $total += $child->products()->count();

                $ids[] = $child->category_id;

                continue;
            }

            $products = Product::whereIn('category_id', $ids)->forPage($page, static::PER_PAGE)->get()->all();

        } else {
            /**
             * @var Collection $products
             */
            $products = $category->products()
                ->forPage($page, static::PER_PAGE)
                ->get();

            $total = $category->products()->count();
        }

        $title = $category->parent_id ? $category->parent->name . ": " . $category->name : $category->name;

        return view('entity/category')->with([
            'category' => $category,
            'products' => $products,
            'total' => $total,
            'per_page' => static::PER_PAGE,
            'title' => $title
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \App\System\Template\View|void
     * @throws \Exception
     */
    public function product(Request $request, $id)
    {
        /**
         * @var Product|null $product
         */
        $product = Product::query()->with('description')->find($id);

        if (!$product) return redirect()->route('not-found');

        if (!session()->has('previous')) session()->put('previous', []);

        /**
         * @var array $breadcrumbs
         */
        $breadcrumbs = $product->breadcrumbs();
        /**
         * @var Category|null $category
         */
        $category = $product->category_id ? $product->category : null;
        /**
         * @var Collection $parameters
         */
        $parameters = $product->parameters;
        /**
         * @var Collection $duplicates
         */
        $duplicates = $product->duplicates;

        $amounts = [];
        $ids = [];

        if ($product->prefix === Product::PRODUCT_PREFIX_CERTIFICATE && $duplicates->count()) {
            $duplicates->each(function (\App\Models\Product $product) use(&$denominations, &$ids) {
                $product->parameters->where('name', 'Номинал')
                    ->each(function (\App\Models\Parameter $parameter) use (&$denominations, $product) {
                        $denominations[] = [
                            'denomination' => intval($parameter->pivot->value),
                            'price' => intval($product->price)
                        ];
                    });

                $ids[] = $product->product_id;
            });

            collect($parameters)->where('name', 'Номинал')
                ->each(function (\App\Models\Parameter $parameter) use (&$denominations, $product) {
                    $denominations[] = [
                        'denomination' => intval($parameter->pivot->value),
                        'price' => intval($product->price)
                    ];
                });

            $ids[] = $product->product_id;

            $amounts = collect($denominations)->sort(function (array $denominationA, array $denominationB){
                if ($denominationA['denomination'] === $denominationB['denomination'] && $denominationA['price'] === $denominationB['price']) return 0;
                if ($denominationA['denomination'] < $denominationB['denomination'] && $denominationA['price'] < $denominationB['price']) return -1;
                return 1;
            })->unique('denomination')->unique('price')->values()->all();
        } else {
            $amounts[] = [
                'price' => $product->price
            ];

            if ($duplicates->count()) {
                $duplicates->each(function (Product $product) use (&$ids) {
                    $ids[] = $product->product_id;
                });

                $ids[] = $product->product_id;
            }
        }

        if ($category) {
            /**
             * @var Collection $sameProducts
             */
            $sameProducts = $ids ? $category->products()->whereNotIn('product_id', $ids)->random(3)->get() : $category->products()->whereNotIn('product_id', [$product->product_id])->random(3)->get();
        } else {
            /**
             * @var Collection $sameProducts
             */
            $sameProducts = collect([]);
        }

        $previous = session()->get('previous');

        $previousIds = collect($previous)->filter(function ($id) use ($product) {
            return intval($id) !== intval($product->product_id);
        })->values()->all();

        $previous[] = $product->product_id;

        session()->put('previous', $previous);

        if (count($previousIds)) {
            $previousProducts = Product::whereIn('product_id', $previousIds)->random(3)->get();
        } else {
            $previousProducts = collect([]);
        }
        /**
         * @var string $title
         */
        $title = $category ? ($category->name . ": " . $product->name) : $product->name;

        return view('entity/product')->with([
            'breadcrumbs' => $breadcrumbs,
            'category' => $category,
            'parameters' => $parameters,
            'product' => $product,
            'amounts' => $amounts,
            'same_products' => $sameProducts,
            'previous' => $previousProducts,
            'title' => $title
        ]);
    }
}