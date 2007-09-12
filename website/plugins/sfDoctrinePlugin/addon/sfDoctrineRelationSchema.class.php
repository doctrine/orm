<?php
/*
 * This file is part of the sfDoctrine package.
 * (c) 2006-2007 Olivier Verdier <Olivier.Verdier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @package    symfony.plugins
 * @subpackage sfDoctrine
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: sfDoctrineRelationSchema.class.php 4705 2007-07-24 20:45:46Z Jonathan.Wage $
 */

class sfDoctrineRelationSchema
{
  protected $relationInfo = array();

  public function __construct($relationInfo)
  {
    foreach ($relationInfo as $key => $value)
    {
      $this->set($key, $value);
    }
  }

  public function set($key, $value)
  {
    // we set the default foreign name
    if ($key == 'foreignClass')
    {
      if (!isset($this->relationInfo['foreignName']))
      {
      	$this->relationInfo['foreignName'] = $value;
    	}
    }
    
    $this->relationInfo[$key] = $value;
  }

  public function get($key)
  {
    if (isset($this->relationInfo[$key]))
    {
      return $this->relationInfo[$key];
    }
    else if (isset($this->relationInfo['options']))
    {
      if ($option = $this->relationInfo['options']->get($key))
      {
        return $option;
    	}
    }
    
    return null;
  }

  public function asDoctrineYml()
  {
    $output = array();
    foreach(array('foreignClass', 'foreignReference', 'localName', 'foreignName', 'cascadeDelete', 'unique') as $key)
    {
      if ($value = $this->get($key))
      {
        $output[$key] = $value;
      }
    }
    
    // FIXME: this is clumsy: change the schema syntax?
    if ($verb == 'owns')
    {
      $output['cascadeDelete'] = true;
    }
    
    return $output;
  }
  
  public function asPhpArray($array)
  {
    $phpArray = 'array(';
    
    if( !empty($array) )
    {
			foreach($array AS $key => $value)
			{
				$phpArray .= "'{$key}' => '{$value}', ";
			}
		
			$phpArray = substr($phpArray, 0, strlen($phpArray) - 2);
    }
    
    $phpArray .= ')';
    
    return $phpArray;
  }
  
  public function asOnePhp()
  {
  	// special behaviour for xref tables with cascade delete
  	$verb = ($this->get('cascadeDelete') && ($this->get('counterpart') || $this->get('unique'))) ? 'owns' : 'has';
  	$options['local'] = $this->get('localReference');
  	$options['foreign'] = $this->get('foreignReference');
  	
  	//support old and new cascade declarations
  	if ($verb == 'owns' || $this->get('cascadeDelete') === true)
  	{
  	  $options['onDelete'] = 'CASCADE';
  	}
  	
  	if ($this->get('onDelete'))
  	{
  	  $options['onDelete'] = strtoupper($this->get('onDelete'));
  	}
  	
  	$phpOptions = $this->asPhpArray($options);
  	
      return "\$this->$verb"."One('{$this->get('foreignClass')} as {$this->get('foreignName')}', $phpOptions);";
  }  

  public function asManyPhp()
  {
    $quantity = $this->get('unique') ? 'One':'Many';
    
    // using "owns" for cascade delete except in xref table
    $verb = ($this->get('cascadeDelete') && !$this->get('counterpart')) ? 'has':'has';
    
		$otherClass = $this->get('localClass');
		
		if ($quantity == 'Many' && $this->get('counterpart'))
		{
			$localReference = $this->relationInfo['localReference'];
			$foreignReference = $this->relationInfo['options']->get('counterpart');
			$otherClass = $this->get('otherClass');
		} else {
			$localReference = $this->get('foreignReference');
			$foreignReference = $this->get('localReference');
		}
  	
  	$localClass = $this->get('localClass');
  	
    // Set refClass to localClass if it is a Many-Many relationship
    if ($quantity == 'Many' && $this->get('counterpart'))
    {
    	$refClass = $this->get('localClass');
    }
    
    if (isset($refClass) && $refClass)
    {
    	$options['refClass'] = $refClass;
    }
    
    if ($localReference)
    {
    	$options['local'] = $localReference;
    }
    
    if ($foreignReference)
    {
    	$options['foreign'] = $foreignReference;
    }
    
    $phpOptions = $this->asPhpArray($options);
    
    return "\$this->$verb$quantity('$otherClass as {$this->get('localName')}', $phpOptions);";    
  }

  public function debug()
  {
    return $this->relationInfo;
  }
}