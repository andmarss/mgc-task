
<li class="dropdown dropdown-item category-dropdown">
    <a href="{{route('category', ['id' => $categoryItem->category_id])}}">
        {{$categoryItem->name}}
        @if($categoryItem->children->count())
            <i class="fa fa-angle-down arrow"></i>
        @endif
    </a>
    @if($categoryItem->children->count())
        <ul class="children scrollable-menu">
            @foreach($categoryItem->children as $subcategory)
                @import('includes/category-menu-item', ['categoryItem' => $subcategory])
            @endforeach
        </ul>
    @endif
</li>
