var symbolClosed = '+';
var symbolOpen = '-';
//var symbolClosed = '▹';
//var symbolOpen = '▿';

function Tree_AutoInit()
{
    var candidates = document.getElementsByTagName('ul');
    
    for (i in candidates) {
        if (HasClassName(candidates[i], 'tree')) {
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
                
                if (HasClassName(subTree, 'closed')) {
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
    
    if (HasClassName(subTree, 'closed')) {
        RemoveClassName(subTree, 'closed');
        expander.innerHTML = symbolOpen;
    } else {
        AddClassName(subTree, 'closed');
        expander.innerHTML = symbolClosed;
    }
    
} 

// ----------------------------------------------------------------------------
// HasClassName
//
// Description : returns boolean indicating whether the object has the class name
//    built with the understanding that there may be multiple classes
//
// Arguments:
//    objElement              - element to manipulate
//    strClass                - class name to add
//
function HasClassName(objElement, strClass)
   {

   // if there is a class
   if ( objElement.className )
      {

      // the classes are just a space separated list, so first get the list
      var arrList = objElement.className.split(' ');

      // get uppercase class for comparison purposes
      var strClassUpper = strClass.toUpperCase();

      // find all instances and remove them
      for ( var i = 0; i < arrList.length; i++ )
         {

         // if class found
         if ( arrList[i].toUpperCase() == strClassUpper )
            {

            // we found it
            return true;

            }

         }

      }

   // if we got here then the class name is not there
   return false;

   }

// ----------------------------------------------------------------------------
// AddClassName
//
// Description : adds a class to the class attribute of a DOM element
//    built with the understanding that there may be multiple classes
//
// Arguments:
//    objElement              - element to manipulate
//    strClass                - class name to add
//
function AddClassName(objElement, strClass, blnMayAlreadyExist)
{
    // if there is a class
    if (objElement.className) {
        
        // the classes are just a space separated list, so first get the list
        var arrList = objElement.className.split(' ');
        
        // if the new class name may already exist in list
        if (blnMayAlreadyExist) {
        
            // get uppercase class for comparison purposes
            var strClassUpper = strClass.toUpperCase();
            
            // find all instances and remove them
            for (var i = 0; i < arrList.length; i++) {
            
                // if class found
                if (arrList[i].toUpperCase() == strClassUpper) {
                
                    // remove array item
                    arrList.splice(i, 1);
                    
                    // decrement loop counter as we have adjusted the array's contents
                    i--;
                }
            }
        }
        
        // add the new class to end of list
        arrList[arrList.length] = strClass;
        
        // add the new class to beginning of list
        //arrList.splice(0, 0, strClass);
        
        // assign modified class name attribute
        objElement.className = arrList.join(' ');
    
    // if there was no class    
    } else {
        // assign modified class name attribute      
        objElement.className = strClass;
    }
}

// ----------------------------------------------------------------------------
// RemoveClassName
//
// Description : removes a class from the class attribute of a DOM element
//    built with the understanding that there may be multiple classes
//
// Arguments:
//    objElement              - element to manipulate
//    strClass                - class name to remove
//
function RemoveClassName(objElement, strClass)
   {

   // if there is a class
   if ( objElement.className )
      {

      // the classes are just a space separated list, so first get the list
      var arrList = objElement.className.split(' ');

      // get uppercase class for comparison purposes
      var strClassUpper = strClass.toUpperCase();

      // find all instances and remove them
      for ( var i = 0; i < arrList.length; i++ )
         {

         // if class found
         if ( arrList[i].toUpperCase() == strClassUpper )
            {

            // remove array item
            arrList.splice(i, 1);

            // decrement loop counter as we have adjusted the array's contents
            i--;

            }

         }

      // assign modified class name attribute
      objElement.className = arrList.join(' ');

      }
   // if there was no class
   // there is nothing to remove

   }


/*
 * Handlers for automated loading
 */ 
 _LOADERS = Array();

function callAllLoaders() {
    var i, loaderFunc;
    for(i=0;i<_LOADERS.length;i++) {
        loaderFunc = _LOADERS[i];
        if(loaderFunc != callAllLoaders) loaderFunc();
    }
}

function appendLoader(loaderFunc) {
    if(window.onload && window.onload != callAllLoaders)
        _LOADERS[_LOADERS.length] = window.onload;

    window.onload = callAllLoaders;

    _LOADERS[_LOADERS.length] = loaderFunc;
}

appendLoader(Tree_AutoInit);
