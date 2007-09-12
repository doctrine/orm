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
 * @version    SVN: $Id: sfDoctrineRecordI18n.class.php 4498 2007-06-30 20:18:01Z mahono $
 */

class sfDoctrineRecordI18n extends sfDoctrineRecord
{
  protected
    $cultureName = '',
    $i18nColumns = null,
    $i18nTable = '',
    $culture = null;

  public function setCulture($newCulture)
  {
    $this->culture = $newCulture;
  }

  public function getCulture()
  {
    // lazily set the culture to the current user's one
    if (!$this->culture)
    {
      $this->culture = sfContext::getInstance()->getUser()->getCulture();
    }

    return $this->culture;
  }

  public function getI18nTable()
  {
    return $this->getTable()->getConnection()->getTable($this->i18nTable);
  }

  public function getCurrentI18nObject($create = false)
  {
    return $this->getI18nObjectForCulture($this->getCulture(), $create);
  }

  public function getI18nObjectForCulture($culture, $create)
  {
    if (empty($this->i18nTable))
    {
      $this->setUp();
    }

    $coll = parent::get($this->i18nTable);

    if (isset($coll[$culture]))
    {
      return $coll[$culture];
    }

    // the i18n object does not exist
    if ($create)
    {
      $obj = $this->getI18nTable()->create();
      $obj->set($this->cultureName, $culture);

      $coll->add($obj);
      return $obj;
    }

    return null; // not found and not created
  }

  protected function hasI18nTable($i18nTableName, $cultureName)
  {
    $this->i18nTable = $i18nTableName;
    $i18nTable = $this->getI18nTable();
    $i18nTable->setAttribute(Doctrine::ATTR_COLL_KEY, $cultureName);

    $columns = $i18nTable->getColumns();

    $this->cultureName = $cultureName;
    unset($columns[$cultureName]);
    $pks = $i18nTable->getPrimaryKeys();
    foreach($pks as $pk)
    {
      unset($columns[$pk]);
    }
    $this->i18nColumns = array_keys($columns);
  }

  // we need this function in order to be sure that setUp has been called
  protected function getI18nColumns()
  {
    if (is_null($this->i18nColumns))
    {
      $this->setUp();
    }

    return $this->i18nColumns;
  }

  public function get($name, $load = true)
  {
    // check if $name is i18n
    if (in_array($name, $this->getI18nColumns()))
    {
      if ($obj = $this->getCurrentI18nObject())
      {
        return $obj->get($name, $load);
      }
      else
      {
        return null;
      }
    }

    return parent::get($name, $load);
  }

  public function set($name, $value, $load = true)
  {
    if (in_array($name, $this->getI18nColumns()))
    {
      $obj = $this->getCurrentI18nObject(true);
      $obj->set($name, $value, $load);
    }
    else
    {
      parent::set($name, $value, $load);
    }
  }

  public function contains($name)
  {
    $i18n = $this->getCurrentI18nObject();

    return $i18n ? $i18n->contains($name) : parent::contains($name);
  }

  public function getData()
  {
    $i18n = $this->getCurrentI18nObject();
    $i18nData = $i18n ? $i18n->getData() : array();
    $data = parent::getData();

    return array_merge($data, $i18nData);
  }

  /**
   * @return integer  the number of columns in this record
   */
  public function count()
  {
    $data = $this->getData();

    return count($data);
  }

  public function toArray()
  {
    $array = parent::toArray();
    $i18n = $this->getCurrentI18nObject();
    $i18nArray = $i18n ? $i18n->toArray() : array();

    return array_merge($array, $i18nArray);
  }
}