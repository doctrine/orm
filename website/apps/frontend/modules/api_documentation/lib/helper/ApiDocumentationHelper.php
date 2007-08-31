<?php
function get_documentation($q)
{
  $docPath = dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))).DIRECTORY_SEPARATOR.'api_documentation'.DIRECTORY_SEPARATOR.'trunk';
  
  if( $q )
  {
    $q = str_replace('-sep-', '/', $q);
    return get_documentation_html($docPath.'/'.$q, $q);
  } else {
    return get_documentation_html($docPath.'/index.html', $q);
  }
}

function process_documentation($html, $q)
{
  //preg_match_all('/a href="(.*)"/', $html, $matches);
  preg_match_all('/<a\s[^>]*href=\"([^\"]*)\"[^>]*>/siU', $html, $matches);

  $matchValues = $matches[1];
  $matches = $matches[0];

  foreach($matches AS $key => $match)
  {
    $value = $matchValues[$key];

    if( $value[0] != '#' )
    {
      $urlQ = str_replace('../', '/', $value);
      $urlQ = str_replace('/', '-sep-', $urlQ);

      $html = str_replace($match, '<a href="'.url_for('@api_documentation_page?q='.$urlQ).'">', $html);
    }
  }
  
  return $html;
}

function get_documentation_html($path)
{
  ob_start();
  if( file_exists($path) )
  {
    include($path);
    $html = ob_get_contents();
    ob_end_clean();

    return process_documentation($html, $path);
  }
}