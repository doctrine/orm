function toggleToc()
{
    var toc = document.getElementById('table-of-contents').getElementsByTagName('ul')[0];
    var toggleLink = document.getElementById('toc-collapse-toggle').getElementsByTagName('a')[0];
    var stickySpan = document.getElementById('toc-sticky-toggle');
    
    if (toc && toggleLink && toc.style.display == 'none') {
        toggleLink.innerHTML = tocHideText;
        toc.style.display = 'block';
        if (stickySpan) {
            stickySpan.style.display = 'inline';
        }
        createCookie('hidetoc', 0, 1000);
    } else {
        toggleLink.innerHTML = tocShowText;
        toc.style.display = 'none';
        if (stickySpan) {
            stickySpan.style.display = 'none';
        }
        createCookie('hidetoc', 1, 1000);
    }
}

function toggleStickyToc()
{
    var wrap = document.getElementById('wrap');
    var toggleLink = document.getElementById('toc-sticky-toggle').getElementsByTagName('a')[0];
    var collapseSpan = document.getElementById('toc-collapse-toggle');
    
    if (wrap && toggleLink && !hasClassName(wrap, 'sticky-toc')) {
        toggleLink.innerHTML = tocUnstickyText;
        addClassName(wrap, 'sticky-toc');
        if (collapseSpan) {
            collapseSpan.style.display = 'none';
        }
        createCookie('stickytoc', 1, 1000);
    } else {
        toggleLink.innerHTML = tocStickyText;
        removeClassName(wrap, 'sticky-toc');
        if (collapseSpan) {
            collapseSpan.style.display = 'inline';
        }
        createCookie('stickytoc', 0, 1000);
    }
}

function createTocToggle()
{
	var container = document.getElementById('toc-toggles');
    
    var span = document.createElement('span');
    var link = document.createElement('a');
    var text = document.createTextNode(tocHideText);

    link.appendChild(text);
    link.setAttribute('href', 'javascript:toggleToc()');
    
    span.setAttribute('id', 'toc-collapse-toggle');
    span.appendChild(link);
    
    container.appendChild(span);
    
    if (readCookie('hidetoc') == 1) {
        toggleToc();
    }
    
    if (readCookie('stickytoc') == 1) {
        span.style.display = 'none';
    }
}

function createTocStickyToggle()
{
	var container = document.getElementById('toc-toggles');
    
    var span = document.createElement('span');
    var link = document.createElement('a');
    var text = document.createTextNode(tocStickyText);

    link.appendChild(text);
    link.setAttribute('href', 'javascript:toggleStickyToc()');
    
    span.setAttribute('id', 'toc-sticky-toggle');
    span.appendChild(link);
    
    container.appendChild(span);
    
    if (readCookie('stickytoc') == 1) {
        toggleStickyToc();
    }
    
    if (readCookie('hidetoc') == 1) {
        span.style.display = 'none';
    }
}
