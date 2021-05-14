@extend('layers/index')

@section('content')
    @if(isset($categoriesWithProducts))
        @foreach($categoriesWithProducts as $category)
            <div>
                <a class="h2" href="{{route('category', ['id' => $category->category_id])}}">
                    <span>
                        {{$category->name}}
                    </span>
                </a>
                <div class="row">
                    <?php
                        $products = $category->products()->random(4)->get();
                    ?>

                    @foreach($products as $product)
                        <div class="col-lg-3 col-md-3 col-xl-3 col-sm-6 col-xs-12">
                            <div>
                                <a href="{{route('product', ['id' => $product->product_id])}}" class="product-image--link">
                                    <img src="{{$product->description->picture}}" alt="{{$product->vendor}}" class="img-fluid product-image">
                                </a>
                            </div>
                            <div class="description">
                                <a href="{{route('product', ['id' => $product->product_id])}}" class="h3">
                                    {{$product->vendor}}
                                </a>
                            </div>
                            <div class="product--footer row m-0 p-0">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                    <span class="product--price">
                                        {{$product->price}} <i class="fa fa-rub"></i>
                                    </span>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right">
                                    <a href="{{route('product', ['id' => $product->product_id])}}" class="btn btn-sm btn-colored">
                                        Подробнее
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <a href="{{route('category', ['id' => $category->category_id])}}" class="btn btn-block btn-category text-center p-2 bord">Показать еще</a>
            </div>
        @endforeach
    @endif
@endsection