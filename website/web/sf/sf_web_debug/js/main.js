function sfWebDebugGetElementsByClassName(strClass, strTag, objContElm)
{
  // http://muffinresearch.co.uk/archives/2006/04/29/getelementsbyclassname-deluxe-edition/
  strTag = strTag || "*";
  objContElm = objContElm || document;
  var objColl = (strTag == '*' && document.all) ? document.all : objContElm.getElementsByTagName(strTag);
  var arr = new Array();
  var delim = strClass.indexOf('|') != -1  ? '|' : ' ';
  var arrClass = strClass.split(delim);
  var j = objColl.length;
  for (var i = 0; i < j; i++) {
    if(objColl[i].className == undefined) continue;
    var arrObjClass = objColl[i].className.split(' ');
    if (delim == ' ' && arrClass.length > arrObjClass.length) continue;
    var c = 0;
    comparisonLoop:
    {
      var l = arrObjClass.length;
      for (var k = 0; k < l; k++) {
        var n = arrClass.length;
        for (var m = 0; m < n; m++) {
          if (arrClass[m] == arrObjClass[k]) c++;
          if (( delim == '|' && c == 1) || (delim == ' ' && c == arrClass.length)) {
            arr.push(objColl[i]);
            break comparisonLoop;
          }
        }
      }
    }
  }
  return arr;
}

function sfWebDebugToggleMenu()
{
  var element = document.getElementById('sfWebDebugDetails');

  var cacheElements = sfWebDebugGetElementsByClassName('sfWebDebugCache');
  var mainCacheElements = sfWebDebugGetElementsByClassName('sfWebDebugActionCache');

  if (element.style.display != 'none')
  {
    document.getElementById('sfWebDebugLog').style.display = 'none';
    document.getElementById('sfWebDebugConfig').style.display = 'none';
    document.getElementById('sfWebDebugDatabaseDetails').style.display = 'none';
    document.getElementById('sfWebDebugTimeDetails').style.display = 'none';

    // hide all cache information
    for (var i = 0; i < cacheElements.length; ++i)
    {
      cacheElements[i].style.display = 'none';
    }
    for (var i = 0; i < mainCacheElements.length; ++i)
    {
      mainCacheElements[i].style.border = 'none';
    }
  }
  else
  {
    for (var i = 0; i < cacheElements.length; ++i)
    {
      cacheElements[i].style.display = '';
    }
    for (var i = 0; i < mainCacheElements.length; ++i)
    {
      mainCacheElements[i].style.border = '1px solid #f00';
    }
  }

  sfWebDebugToggle('sfWebDebugDetails');
  sfWebDebugToggle('sfWebDebugShowMenu');
  sfWebDebugToggle('sfWebDebugHideMenu');
}

function sfWebDebugShowDetailsFor(element)
{
  if (element != 'sfWebDebugLog') document.getElementById('sfWebDebugLog').style.display='none';
  if (element != 'sfWebDebugConfig') document.getElementById('sfWebDebugConfig').style.display='none';
  if (element != 'sfWebDebugDatabaseDetails') document.getElementById('sfWebDebugDatabaseDetails').style.display='none';
  if (element != 'sfWebDebugTimeDetails') document.getElementById('sfWebDebugTimeDetails').style.display='none';

  sfWebDebugToggle(element);
}

function sfWebDebugToggle(element)
{
  if (typeof element == 'string')
    element = document.getElementById(element);

  if (element)
    element.style.display = element.style.display == 'none' ? '' : 'none';
}

function sfWebDebugToggleMessages(klass)
{
  var elements = sfWebDebugGetElementsByClassName(klass);

  var x = elements.length;
  for (var i = 0; i < x; ++i)
  {
    sfWebDebugToggle(elements[i]);
  }
}

function sfWebDebugToggleAllLogLines(show, klass)
{
  var elements = sfWebDebugGetElementsByClassName(klass);
  var x = elements.length;
  for (var i = 0; i < x; ++i)
  {
    elements[i].style.display = show ? '' : 'none';
  }
}

function sfWebDebugShowOnlyLogLines(type)
{
  var types = new Array();
  types[0] = 'info';
  types[1] = 'warning';
  types[2] = 'error';
  for (klass in types)
  {
    var elements = sfWebDebugGetElementsByClassName('sfWebDebug' + types[klass].substring(0, 1).toUpperCase() + types[klass].substring(1, types[klass].length));
    var x = elements.length;
    for (var i = 0; i < x; ++i)
    {
      elements[i].style.display = (type == types[klass]) ? '' : 'none';
    }
  }
}
