
<li class="dropdown dropdown-item category-dropdown">
    <a href="<?=route('category', ['id' => $categoryItem->category_id]);?>">
        <?=$categoryItem->name;?>
        <?php if($categoryItem->children->count()): ?>
            <i class="fa fa-angle-down arrow"></i>
        <?php endif; ?>
    </a>
    <?php if($categoryItem->children->count()): ?>
        <ul class="children scrollable-menu">
            <?php foreach($categoryItem->children as $subcategory): ?>
                <?=view('includes/category-menu-item', ['categoryItem' => $subcategory])->render(); ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</li>
