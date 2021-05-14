$(document).ready(function () {
    var $dropdown = $('.category-dropdown');

    var $dropdownMenu = $('.category-dropdown-menu');

    var $children = $('.children');

    $dropdownMenu.css({
        'position': 'relative'
    });

    $children.css({'list-style-type': 'none'});

    $dropdown.each(function () {
        var $self = $(this);

        if ($self.find('> .children').length) {
            var $childrenList = $self.find('> .children');
            var $parent = $self;

            $parent.css({
                'position': 'unset'
            });

            $childrenList.css({
                'position': 'relative',
                'display': 'none',
                'background': '#fff',
                'min-width': '250px',
                'padding': '10px',
                'border-radius': '10px'
            });
        }
    });

    $('.overlay-open').click(function () {
        $('.overlay').show();
    });

    $('.close-overlay').click(function () {
        $('.overlay').hide();
    });

    $('.category-menu').addClass('open');

    $('.overlay').click(function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $self = $(e.target);

        if ($self.is('.overlay') || $self.is('.close-overlay')) {
            $('.overlay').hide();
        }
    });

    $dropdown.click(function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $self = $(e.target).closest('.dropdown');

        if ($self.find('> .children').length && $self.hasClass('active')) {
            window.location = e.target.href;
        } else if (!$self.find('> .children').length) {
            window.location = e.target.href;
        } else {
            $dropdown.removeClass('active');
            $('.dropdown-menu').find('.dropdown').not($self).not($self.parents('.dropdown')).find('.children').hide();
            $self.addClass('active');
            $self.find('> .children').show();
        }

        $('.scroll').animate({
            scrollTop: $self.position().top
        }, 400);
    });

    $('.category-product').matchHeight();

    $('.product-image--wrapper img').each(function () {
        var $img = $(this);
        var $wrapper = $img.parent();

        if ($img.width() < $wrapper.width() || $img.height() < $wrapper.height()) {
            $wrapper.addClass('box-shadow--none').addClass('background--none');
        }
    });
});