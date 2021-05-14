
<?php \App\System\Template\Section::start('content'); ?>
    <?php if(isset($product)): ?>
        <!-- START Хлебные крошки -->
        <?php
            $i = 1;
        ?>
        <ul class="breadcrumbs">
            <?php foreach($breadcrumbs as $breadcrumb): ?>
                <?php if($i === count($breadcrumbs)): ?>
                    <li><?=$breadcrumb['name'];?></li>
                <?php else: ?>
                    <li><a href="<?=$breadcrumb['url'];?>"><?=$breadcrumb['name'];?></a></li>
                    <?php
                        ++$i;
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <!-- END Хлебные крошки -->
        <!-- START Заголовок -->
        <h2 class="h2"><?=$product->vendor;?> - <?=$product->name;?></h2>
        <!-- END Заголовок -->
        <!-- START Информация о продукте -->
        <div class="row mb-3 product-page">
            <div class="col-md-5 col-xl-4 col-image">
                <div class="product-image--wrapper">
                    <img src="<?=$product->description->picture;?>" alt="<?=$product->vendor;?>" class="img-fluid product-image">
                </div>
            </div>
            <div class="col-md-7 col-xl-8 text-left">
                <?php if($product->prefix === \App\Models\Product::PRODUCT_PREFIX_CERTIFICATE): ?>
                    <div class="row">
                        <?php
                            $currentDenomination = count($amounts) > 0 ? current($amounts)['denomination'] : $product->price;
                            $currentPrice = count($amounts) > 0 ? current($amounts)['price'] : $product->price;
                        ?>
                        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                            <label>Номинал</label>
                            <div>
                                <button class="btn dropdown-toggle text-uppercase btn-denomination" aria-expanded="true" id="amounts-dropdown" data-toggle="dropdown" aria-haspopup="true">
                                    <span class="denomination"><?=$currentDenomination;?></span>
                                    <i class="fa fa-rub"></i>
                                </button>
                                <div class="dropdown-menu vertical-list product-price--dropdown" aria-labelledby="amounts-dropdown">
                                    <?php foreach($amounts as $index => $amount): ?>
                                        <li class="dropdown dropdown-item product-nominal" data-nominal="<?=$amount['denomination'];?>" data-price="<?=$amount['price'];?>">
                                            <?=$amount['denomination'];?> <i class="fa fa-rub"></i>
                                        </li>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                            <label>Стоимость</label>
                            <div>
                                <span id="price"><?=$currentPrice;?></span> <i class="fa fa-rub"></i>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 product-price--wrapper">
                        <label>Цена</label>
                        <div>
                            <span id="price"><?=current($amounts)['price'];?></span> <i class="fa fa-rub"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- END Информация о продукте -->
        <!-- START Параметры продукта -->
        <?php
            $parameters = $parameters->count() ? $parameters->filter(function (\App\Models\Parameter $parameter){
                return $parameter->name !== 'Номинал';
            })->values() : collect([]);
        ?>
        <?php if($parameters->count()): ?>
            <div class="divider" style="margin-top: 50px"></div>
            <div class="row p-0 m-0">
                <div class="col-sm-12 col-lg-12 col-xs-12 col-xl-12">
                    <dl>
                        <div class="row">
                            <?php foreach($parameters as $parameter): ?>

                            <div class="col-md-6 col-xl-6 col-sm-6 col-xl-6">
                                <dt>
                                    <i class="fa fa-check-square-o"></i>
                                    <span><?=$parameter->name;?></span>
                                </dt>
                                <dd>
                                    <?php if($parameter->name === 'Гарантированный срок действия'): ?>
                                    Не менее <?=$parameter->pivot->value;?> <?=pluralize(intval($parameter->pivot->value), ['месяц', 'месяца', 'месяцев']);?>
                                    <?php else: ?>
                                    <?=$parameter->pivot->value;?>
                                    <?php endif; ?>
                                </dd>
                            </div>

                            <?php endforeach; ?>
                        </div>
                    </dl>
                </div>
            </div>
            <div class="divider"></div>
        <?php endif; ?>
        <!-- END Параметры продукта -->
        <!-- START Похожие продукты -->
        <?php if($same_products->count()): ?>
            <h2 class="h2">Похожие товары</h2>
            <div class="row">
                <?php foreach($same_products as $product): ?>
                <div class="col-lg-3 col-xl-3 col-md-6 col-sm-6 col-xs-12 lead category-product">
                    <div class="product-link--wrapper">
                        <a href="<?=route('product', ['id' => $product->product_id]);?>" class="product-image--link img-responsive">
                            <div class="product-image--wrapper">
                                <img src="<?=$product->description->picture;?>" alt="<?=$product->vendor;?>" class="img-fluid product-image">
                            </div>
                        </a>
                    </div>
                    <div class="product--description">
                        <a href="<?=route('product', ['id' => $product->product_id]);?>" class="h3">
                            <?=$product->vendor;?>
                        </a>
                    </div>
                    <div class="product--footer row m-0 p-0">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 product-price--wrapper">
                                            <span class="product--price">
                                                <?=$product->price;?> <i class="fa fa-rub"></i>
                                            </span>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right product-more--wrapper">
                            <a href="<?=route('product', ['id' => $product->product_id]);?>" class="btn btn-sm btn-colored">
                                Подробнее
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <!-- END Похожие продукты -->
        <!-- START Раннее просматриваемые продукты -->
        <?php if($previous->count()): ?>
            <h2 class="h2">Раннее просматриваемые</h2>
            <div class="row">
                <?php foreach($previous as $product): ?>
                <div class="col-lg-3 col-xl-3 col-md-6 col-sm-6 col-xs-12 lead category-product">
                    <div class="product-link--wrapper">
                        <a href="<?=route('product', ['id' => $product->product_id]);?>" class="product-image--link img-responsive">
                            <div class="product-image--wrapper">
                                <img src="<?=$product->description->picture;?>" alt="<?=$product->vendor;?>" class="img-fluid product-image">
                            </div>
                        </a>
                    </div>
                    <div class="product--description">
                        <a href="<?=route('product', ['id' => $product->product_id]);?>" class="h3">
                            <?=$product->vendor;?>
                        </a>
                    </div>
                    <div class="product--footer row m-0 p-0">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 product-price--wrapper">
                                            <span class="product--price">
                                                <?=$product->price;?> <i class="fa fa-rub"></i>
                                            </span>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 text-right product-more--wrapper">
                            <a href="<?=route('product', ['id' => $product->product_id]);?>" class="btn btn-sm btn-colored">
                                Подробнее
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <!-- END Раннее просматриваемые продукты -->

    <?php endif; ?>
<?php \App\System\Template\Section::stop(); ?>

<?=view('layers/index')->with(get_defined_vars())->render();