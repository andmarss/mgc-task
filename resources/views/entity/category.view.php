@extend('layers/index')

@section('content')
    @if(isset($category))

        <?php
            $breadcrumbs = $category->breadcrumbs();
            $i = 1;
        ?>
        <ul class="breadcrumbs">
            @foreach($breadcrumbs as $breadcrumb)
                @if($i === count($breadcrumbs))
                    <li>{{$breadcrumb['name']}}</li>
                @else
                    <li><a href="{{$breadcrumb['url']}}">{{$breadcrumb['name']}}</a></li>
                    <?php
                        ++$i;
                    ?>
                @endif
            @endforeach
        </ul>

        <h2 class="h2">{{$category->parent_id ? $category->parent->name . ': ' : ''}}{{$category->name}}</h2>
        <div class="row">
        @forelse($products as $product)
        <div class="col-lg-3 col-md-3 col-xl-3 col-sm-6 col-xs-12 lead category-product">
            <div class="product-link--wrapper">
                <a href="{{route('product', ['id' => $product->product_id])}}" class="product-image--link img-responsive">
                    <span class="product-image--wrapper">
                        <img src="{{$product->description->picture}}" alt="{{$product->vendor}}" class="img-fluid product-image">
                    </span>
                </a>
            </div>
            <div class="product--description">
                <a href="{{route('product', ['id' => $product->product_id])}}" class="h3">
                    {{$product->vendor}}
                </a>
            </div>
            <div class="product--footer row m-0 p-0">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 product-price--wrapper">
                                            <span class="product--price">
                                                {{$product->price}} <i class="fa fa-rub"></i>
                                            </span>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right product-more--wrapper">
                    <a href="{{route('product', ['id' => $product->product_id])}}" class="btn btn-sm btn-colored">
                        Подробнее
                    </a>
                </div>
            </div>
        </div>
        @empty
            <h2 class="h2">Список продуктов данной категории пуст</h2>
        @endforelse
        </div>

        <div class="col-sm-12 col-md-12 col-lg-12 col-xs-12 text-center">
            {{pagination($per_page, $total)}}
        </div>
    @endif
@endsection