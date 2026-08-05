
require(['jquery'], function($) {
    'use strict';
    
    function initSolutiooLogo() {

        var menuItem = $('.item-solutioo-base-solutioo.level-0').first();
        
        if (!menuItem.length) {

            menuItem = $('[class*="item-solutioo-base"]').first();
        }
        
        if (!menuItem.length) {

            return false;
        }
        
        var link = menuItem.find('> a').first();

        if (link.find('.solutioo-menu-logo').length) {
            return true;
        }
        
        var logoUrl = require.toUrl('Solutioo_Base/images/solutioo-logo.svg');

        var logoImg = $('<img>', {
            src: logoUrl,
            alt: 'Solutioo',
            class: 'solutioo-menu-logo'
        }).css({
            'width': '20px',
            'height': '20px',
            'filter': 'brightness(0) invert(1)',
            'display': 'inline-block',
            'vertical-align': 'middle',
            'margin-right': '10px',
            'flex-shrink': '0'
        });

        link.prepend(logoImg);

        var css = '<style id="solutioo-base-menu-css">' +
            '.admin__menu .item-solutioo-base-solutioo.level-0 > a::before,' +
            '.admin__menu [class*="item-solutioo-base"].level-0 > a::before {' +
            '  content: none !important;' +
            '  display: none !important;' +
            '}' +
            '.admin__menu .item-solutioo-base-solutioo.level-0 > a,' +
            '.admin__menu [class*="item-solutioo-base"].level-0 > a {' +
            '  padding-left: 15px !important;' +
            '  display: flex !important;' +
            '  align-items: center !important;' +
            '}' +
            '.solutioo-menu-logo {' +
            '  width: 20px !important;' +
            '  height: 20px !important;' +
            '  filter: brightness(0) invert(1) !important;' +
            '}' +
            '.admin__menu [class*="item-solutioo-base"]:hover .solutioo-menu-logo,' +
            '.admin__menu [class*="item-solutioo-base"]._show .solutioo-menu-logo,' +
            '.admin__menu [class*="item-solutioo-base"]._current .solutioo-menu-logo {' +
            '  filter: none !important;' +
            '}' +
            '</style>';
        
        if (!$('#solutioo-base-menu-css').length) {
            $('head').append(css);
        }

        menuItem.on('mouseenter', function() {
            $(this).find('.solutioo-menu-logo').css('filter', 'none');
        }).on('mouseleave', function() {
            if (!$(this).hasClass('_current') && !$(this).hasClass('_show')) {
                $(this).find('.solutioo-menu-logo').css('filter', 'brightness(0) invert(1)');
            }
        });
        
        if (menuItem.hasClass('_current') || menuItem.hasClass('_show')) {
            logoImg.css('filter', 'none');
        }
        
        return true;
    }

    $(function() {
        if (!initSolutiooLogo()) {

            setTimeout(function() {
                if (!initSolutiooLogo()) {

                    setTimeout(initSolutiooLogo, 500);
                }
            }, 100);
        }
    });
});
