function initializeTocToggles()
{
    var container = new Element('div', {
        'id': 'toc-toggles'
    });

    container.injectTop($('table-of-contents'));
    $('table-of-contents').setStyle('padding-top', '0');
    
    var hideToggle = new Element('a', {
        'href': 'javascript:void(0);',
        'events': {
            'click': function() {
                var toc = $E('ul', 'table-of-contents');

                if (toc.getStyle('display') == 'none') {
                    this.setHTML(tocHideText);
                    toc.setStyle('display', 'block');
                    stickyToggle.setStyle('display', 'inline');
                    Cookie.set('hidetoc', 0, {duration: 1000});
                } else {
                    this.setHTML(tocShowText);
                    toc.setStyle('display', 'none');
                    stickyToggle.setStyle('display', 'none');
                    Cookie.set('hidetoc', 1, {duration: 1000});
                }
            }
        }
    });
    
    var stickyToggle = new Element('a', {
        'href': 'javascript:void(0);',
        'events': {
            'click': function() {
                var wrap = $('wrap');
                
                if ( ! wrap.hasClass('sticky-toc')) {
                    this.setHTML(tocUnstickyText);
                    wrap.addClass('sticky-toc');
                    hideToggle.setStyle('display', 'none');
                    Cookie.set('stickytoc', 1, {duration: 1000});
                } else {
                    this.setHTML(tocStickyText);
                    wrap.removeClass('sticky-toc');
                    hideToggle.setStyle('display', 'inline');
                    Cookie.set('stickytoc', 0, {duration: 1000});
                }
            }
        }
    });
    
    hideToggle.setHTML(tocHideText);
    hideToggle.injectInside(container);
    
    stickyToggle.setHTML(tocStickyText);
    stickyToggle.injectInside(container);
    
    if (Cookie.get('hidetoc') == 1) {
        hideToggle.fireEvent('click');
        stickyToggle.setStyle('display', 'none');
    }
    
    if (Cookie.get('stickytoc') == 1) {
        stickyToggle.fireEvent('click');
        hideToggle.setStyle('display', 'none');
    }
}

window.addEvent('domready', function() {
    initializeTocToggles();
});
