function initializeTrees(symbolClosed, symbolOpen)
{
    $$('ul.tree li').each(function(listItem) {
    
        var subTree = listItem.getChildren().filterByTag('ul')[0];
        
        if (subTree) {
        
            var expander = new Element('a', {
                'class': 'expander',
                'href': 'javascript:void(0);',
                'events': {
                    'click': function() {
                        if (subTree.hasClass('closed')) {
                            subTree.removeClass('closed');
                            this.setHTML(symbolOpen);
                        } else {
                            subTree.addClass('closed');
                            this.setHTML(symbolClosed);
                        }
                    }
                }
            });
            
            expander.setHTML(subTree.hasClass('closed') ? symbolClosed : symbolOpen);
            expander.injectTop(listItem);
        }
    });
}

window.addEvent('domready', function() {
    initializeTrees('+', '-');
});
