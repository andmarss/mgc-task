<?php
    $categories = \App\Models\Category::whereNull('parent_id')->whereHas('products', null, '>=', 0)->get();
?>

<?php if(count($categories)): ?>
    <nav class="btn-group btn-block scroll open category-menu">
        <button class="btn-menu btn-block dropdown-toggle text-uppercase btn-primary" aria-expanded="true">
            <i class="fa fa-bars"></i>
            Каталог товаров
        </button>
        <ul class="dropdown-menu vertical-list category-dropdown-menu">
            <?php foreach($categories as $category): ?>
                <?=view('includes/category-menu-item', ['categoryItem' => $category])->render(); ?>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>
