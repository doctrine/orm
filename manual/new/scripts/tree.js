var symbolClosed = '+';
var symbolOpen = '-';

function Tree_AutoInit()
{
    var candidates = document.getElementsByTagName('ul');
    
    for (i in candidates) {
        if (hasClassName(candidates[i], 'tree')) {
            Tree_Init(candidates[i]);
        }
    }
}

function Tree_Init(element)
{
    for (var i in element.childNodes) {
    
        var li = element.childNodes[i];
        
        if (li.tagName && li.tagName.toLowerCase() == 'li') {
        
            var subTree = Tree_FindChild(li, 'ul');
            
            if (subTree) {
                var expander = document.createElement('a');
                
                expander.className = 'expander';
                expander.href = 'javascript:void(0);';
                expander.onclick = Tree_Toggle;
                
                if (hasClassName(subTree, 'closed')) {
                    expander.innerHTML = symbolClosed;
                } else {
                    expander.innerHTML = symbolOpen;
                }
                
                li.insertBefore(expander, li.firstChild);
                
                Tree_Init(subTree);                
            }
        }
    }
}

function Tree_FindChild(element, childTag)
{   
    for (i in element.childNodes) {
        child = element.childNodes[i];
        if (child.tagName && child.tagName.toLowerCase() == childTag) {
            return child;
        }
    }
    return null;
}
 
function Tree_Toggle()
{
    expander = this;
    li = expander.parentNode;
    subTree = Tree_FindChild(li, 'ul');
    
    if (hasClassName(subTree, 'closed')) {
        removeClassName(subTree, 'closed');
        expander.innerHTML = symbolOpen;
    } else {
        addClassName(subTree, 'closed');
        expander.innerHTML = symbolClosed;
    }
    
} 

appendLoader(Tree_AutoInit);
