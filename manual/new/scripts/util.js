/**
 * Checks if the object has the class name. 
 *  
 * @param objElement  element to manipulate
 * @param strClass    class name to add
 * 
 * @return boolean indicating whether the object has the class name built with
 *         the understanding that there may be multiple classes.
 */
function hasClassName(objElement, strClass)
{
   // if there is a class
   if (objElement.className) {

      // the classes are just a space separated list, so first get the list
      var arrList = objElement.className.split(' ');

      // get uppercase class for comparison purposes
      var strClassUpper = strClass.toUpperCase();

      // find all instances and remove them
      for (var i = 0; i < arrList.length; i++) {
         // if class found
         if (arrList[i].toUpperCase() == strClassUpper) {
            // we found it
            return true;
         }
      }

   }

   // if we got here then the class name is not there
   return false;
}

/**
 * Adds a class to the class attribute of a DOM element built with the
 * understanding that there may be multiple classes.
 *
 * @param objElement  element to manipulate
 * @param strClass    class name to add
 */
function addClassName(objElement, strClass, blnMayAlreadyExist)
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

/**
 * RemoveClassName
 * 
 * Removes a class from the class attribute of a DOM element built with the
 * understanding that there may be multiple classes.
 * 
 * @param objElement  element to manipulate
 * @param strClass    class name to remove
 */
function removeClassName(objElement, strClass)
{
   // if there is a class
   if (objElement.className) {

      // the classes are just a space separated list, so first get the list
      var arrList = objElement.className.split(' ');

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


function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+";";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

