[?php

/**
 * <?php echo $this->getGeneratedModuleName() ?> actions.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage <?php echo $this->getGeneratedModuleName() ?>

 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: actions.class.php 4836 2007-08-07 19:10:21Z Jonathan.Wage $
 */
class <?php echo $this->getGeneratedModuleName() ?>Actions extends sfActions
{
  public function preExecute ()
  {
    $this->getResponse()->addStylesheet('<?php echo $this->getParameterValue('css', sfConfig::get('sf_admin_web_dir').'/css/main') ?>');
  }

  public function executeIndex ()
  {
    return $this->forward('<?php echo $this->getModuleName() ?>', 'list');
  }

  public function executeList ()
  {
    $this->processSort();

    $this->processFilters();

<?php if ($this->getParameterValue('list.filters')): ?>
    $this->filters = $this->getUser()->getAttributeHolder()->getAll('sf_admin/<?php echo $this->getSingularName() ?>/filters');
<?php endif; ?>

    // pager
    $this->pager = new sfDoctrinePager('<?php echo $this->getClassName() ?>', <?php echo $this->getParameterValue('list.max_per_page', 20) ?>);

<?php if ($peerMethod = $this->getParameterValue('list.peer_method')): ?>    
    $q = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')-><?php echo $peerMethod ?>();
    $this->pager->setQuery($q);
<?php endif; ?>

    $this->addSortCriteria($this->pager->getQuery());
    $this->addFiltersCriteria($this->pager->getQuery()); 
    
    $this->pager->setPage($this->getRequestParameter('page', $this->getUser()->getAttribute('page', 1, 'sf_admin/<?php echo $this->getSingularName() ?>')));
    
    $this->pager->init();
    // Save page
    if ($this->getRequestParameter('page')) {
        $this->getUser()->setAttribute('page', $this->getRequestParameter('page'), 'sf_admin/<?php echo $this->getSingularName() ?>');
    }
  }

  public function executeCreate ()
  {
    return $this->forward('<?php echo $this->getModuleName() ?>', 'edit');
  }

  public function executeSave ()
  {
    return $this->forward('<?php echo $this->getModuleName() ?>', 'edit');
  }

  public function executeEdit ()
  {
    $this-><?php echo $this->getSingularName() ?> = $this->get<?php echo $this->getClassName() ?>OrCreate();

    if ($this->getRequest()->getMethod() == sfRequest::POST)
    {
      $this->update<?php echo $this->getClassName() ?>FromRequest();

      $this->save<?php echo $this->getClassName() ?>($this-><?php echo $this->getSingularName() ?>);

      $this->setFlash('notice', 'Your modifications have been saved');

      if ($this->getRequestParameter('save_and_add'))
      {
        return $this->redirect('<?php echo $this->getModuleName() ?>/create');
      }
      else if ($this->getRequestParameter('save_and_list'))
      {
        return $this->redirect('<?php echo $this->getModuleName() ?>/list');
      }
      else
      {
        return $this->redirect('<?php echo $this->getModuleName() ?>/edit?<?php echo $this->getPrimaryKeyUrlParams('this->') ?>);
      }
    }
    else
    {
      $this->addJavascriptsForEdit();

      $this->labels = $this->getLabels();
    }
    
    // temporary fix to avoid using a distinct editSuccess.php template
    sfLoader::loadHelpers(array('Helper', 'ObjectDoctrineAdmin'));
  }

  public function executeDelete ()
  {
    $this-><?php echo $this->getSingularName() ?> = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->find(<?php echo $this->getRetrieveByPkParamsForAction(40) ?>);
    
    $this->forward404Unless($this-><?php echo $this->getSingularName() ?>);

    try
    {
      $this->delete<?php echo $this->getClassName() ?>($this-><?php echo $this->getSingularName() ?>);
    }
    catch (Doctrine_Exception $e)
    {
      $this->getRequest()->setError('delete', 'Could not delete the selected <?php echo sfInflector::humanize($this->getSingularName()) ?>. Make sure it does not have any associated items.');
      return $this->forward('<?php echo $this->getModuleName() ?>', 'list');
    }

<?php foreach ($this->getColumnCategories('edit.display') as $category): ?>
<?php foreach ($this->getColumns('edit.display', $category) as $name => $column): ?>
<?php $input_type = $this->getParameterValue('edit.fields.'.$column->getName().'.type') ?>
<?php if ($input_type == 'admin_input_file_tag'): ?>
<?php $upload_dir = $this->replaceConstants($this->getParameterValue('edit.fields.'.$column->getName().'.upload_dir')) ?>
      $currentFile = sfConfig::get('sf_upload_dir')."/<?php echo $upload_dir ?>/".<?php echo $this->getColumnGetter($column, true, 'this->')?>;
      if (is_file($currentFile))
      {
        unlink($currentFile);
      }

<?php endif; ?>
<?php endforeach; ?>
<?php endforeach; ?>
    return $this->redirect('<?php echo $this->getModuleName() ?>/list');
  }

  public function handleErrorEdit()
  {
    $this->preExecute();
    $this-><?php echo $this->getSingularName() ?> = $this->get<?php echo $this->getClassName() ?>OrCreate();
    $this->update<?php echo $this->getClassName() ?>FromRequest();

    $this->addJavascriptsForEdit();

    $this->labels = $this->getLabels();

    // temporary fix to avoid using a distinct editSuccess.php template
    sfLoader::loadHelpers(array('Helper', 'ObjectDoctrineAdmin'));

    return sfView::SUCCESS;
  }

  protected function save<?php echo $this->getClassName() ?>($<?php echo $this->getSingularName() ?>)
  {
    $<?php echo $this->getSingularName() ?>->save();
  }

  protected function delete<?php echo $this->getClassName() ?>($<?php echo $this->getSingularName() ?>)
  {
    $<?php echo $this->getSingularName() ?>->delete();
  }

  protected function update<?php echo $this->getClassName() ?>FromRequest()
  {
    $<?php echo $this->getSingularName() ?> = $this->getRequestParameter('<?php echo $this->getSingularName() ?>');

<?php foreach ($this->getColumnCategories('edit.display') as $category): ?>
<?php foreach ($this->getColumns('edit.display', $category) as  $column): $type = $column->getDoctrineType(); ?>
<?php $name = $column->getName();  ?>
<?php if ($column->isPrimaryKey()) continue ?>
<?php $credentials = $this->getParameterValue('edit.fields.'.$name.'.credentials') ?>
<?php $input_type = $this->getParameterValue('edit.fields.'.$name.'.type') ?>
<?php if ($credentials): $credentials = str_replace("\n", ' ', var_export($credentials, true)) ?>
    if ($this->getUser()->hasCredential(<?php echo $credentials ?>))
    {
<?php endif; ?>
<?php if ($input_type == 'admin_input_file_tag'): ?>
<?php $upload_dir = $this->replaceConstants($this->getParameterValue('edit.fields.'.$column->getName().'.upload_dir')) ?>
    $currentFile = sfConfig::get('sf_upload_dir')."/<?php echo $upload_dir ?>/".<?php echo $this->getColumnGetter($column, true, 'this->')?>;
    if (!$this->getRequest()->hasErrors() && isset($<?php echo $this->getSingularName() ?>['<?php echo $name ?>_remove']))
    {
      <?php echo $this->getColumnSetter($column, '', true) ?>;
      if (is_file($currentFile))
      {
        unlink($currentFile);
      }
    }

    if (!$this->getRequest()->hasErrors() && $this->getRequest()->getFileSize('<?php echo $this->getSingularName() ?>[<?php echo $name ?>]'))
    {
<?php elseif ($type != 'boolean'): ?>
    if (isset($<?php echo $this->getSingularName() ?>['<?php echo $name ?>']))
    {
<?php endif; ?>
<?php if ($input_type == 'admin_input_file_tag'): ?>
<?php if ($this->getParameterValue('edit.fields.'.$name.'.filename')): ?>
      $fileName = "<?php echo str_replace('"', '\\"', $this->replaceConstants($this->getParameterValue('edit.fields.'.$column->getName().'.filename'))) ?>";
<?php else: ?>
      $fileName = md5($this->getRequest()->getFileName('<?php echo $this->getSingularName() ?>[<?php echo $name ?>]').time());
<?php endif; ?>
      $ext = $this->getRequest()->getFileExtension('<?php echo $this->getSingularName() ?>[<?php echo $name ?>]');
      if (is_file($currentFile))
      {
        unlink($currentFile);
      }
      $this->getRequest()->moveFile('<?php echo $this->getSingularName() ?>[<?php echo $name ?>]', sfConfig::get('sf_upload_dir')."/<?php echo $upload_dir ?>/".$fileName.$ext);
      <?php echo $this->getColumnSetter($column, '$fileName.$ext')?>;
<?php elseif ($type == 'date' || $type == 'timestamp'): ?>
      if ($<?php echo $this->getSingularName() ?>['<?php echo $name ?>'])
      {
        $dateFormat = new sfDateFormat($this->getUser()->getCulture());
<?php 
$inputPattern = ($type == 'date' ? 'd' : 'g');
$outputPattern = ($type == 'date' ? 'i' : 'I'); 
?>
        // if this is a direct date input (rich == true)
        if (!is_array($<?php echo $this->getSingularName() ?>['<?php echo $name ?>']))
        {
          try
          {
            $value = $dateFormat->format($<?php echo $this->getSingularName() ?>['<?php echo $name ?>'], '<?php echo $outputPattern ?>', $dateFormat->getInputPattern('<?php echo $inputPattern ?>'));
          }
          catch (sfException $e)
          {
            // not a valid date
          }
        }
        else // rich == false
        {
          $value_array = $<?php echo $this->getSingularName() ?>['<?php echo $name ?>'];
          $value = $value_array['year'].'-'.$value_array['month'].'-'.$value_array['day'].(isset($value_array['hour']) ? ' '.$value_array['hour'].':'.$value_array['minute'].(isset($value_array['second']) ? ':'.$value_array['second'] : '') : ''); 
        }
        <?php echo $this->getColumnSetter($column, '$value') ?>;
      }
      else
      {
        <?php echo $this->getColumnSetter($column, 'null') ?>;
      }
<?php elseif ($type == 'boolean'): ?>
  <?php $boolVar = "\${$this->getSingularName()}['$name']";
     echo $this->getColumnSetter($column, "isset($boolVar) ? $boolVar : 0") ?>;
<?php elseif ($column->isForeignKey()): ?>
      $foreignKey = $<?php echo $this->getSingularName() ?>['<?php echo $name ?>'];
      $foreignKey = empty($foreignKey) ? null : $foreignKey;
      $this-><?php echo $this->getSingularName()?>->set('<?php echo $column->getColumnName()?>', $foreignKey);
<?php else: ?>
      $this-><?php echo $this->getSingularName() ?>->set('<?php echo $column->getName() ?>', $<?php echo $this->getSingularName() ?>['<?php echo $name ?>']);
<?php endif; ?>
<?php if ($type != 'boolean'): ?>
    }
<?php endif; ?>

<?php // double lists
if (in_array($input_type, array('doctrine_admin_double_list', 'doctrine_admin_check_list', 'doctrine_admin_select_list'))): ?>
      // Update many-to-many for "<?php echo $name ?>"
      $<?php echo $name?>Table = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->getRelation('<?php echo $name ?>')->getTable();
 
      $associationName = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->getRelation('<?php echo $name ?>')->getAssociationTable()->getOption('name');
      $this-><?php echo $this->getSingularName()?>->$associationName->delete();
 
      $ids = $this->getRequestParameter('associated_<?php echo $name ?>');
      if (is_array($ids))
      {
        foreach ($ids as $id)
        {
          $id = explode('/', $id);
          $this-><?php echo $this->getSingularName()?>->get('<?php echo $name ?>')->add($<?php echo $name?>Table->find($id));
        }
      }
<?php endif; // double lists ?>
<?php if ($credentials): ?>
      }
<?php endif; ?>
<?php endforeach; ?>
<?php endforeach; ?>
  }

  protected function get<?php echo $this->getClassName() ?>OrCreate (<?php echo $this->getMethodParamsForGetOrCreate() ?>)
  {
    if (<?php echo $this->getTestPksForGetOrCreate() ?>)
    {
      $<?php echo $this->getSingularName() ?> = new <?php echo $this->getClassName() ?>();
    }
    else
    {
      $<?php echo $this->getSingularName() ?> = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->find(array(<?php echo $this->getRetrieveByPkParamsForGetOrCreate() ?>));

      $this->forward404Unless($<?php echo $this->getSingularName() ?>);
    }

    return $<?php echo $this->getSingularName() ?>;
  }

  protected function processFilters ()
  {
<?php if ($this->getParameterValue('list.filters')): ?>
    if ($this->getRequest()->hasParameter('filter'))
    {
      $filters = $this->getRequestParameter('filters');
<?php foreach ($this->getColumns('list.filters') as $column): $type = $column->getDoctrineType() ?>
<?php if ($type == 'date' || $type == 'timestamp'): 
$inputPattern = ($type == 'date' ? 'd' : 'g');
$outputPattern = ($type == 'date' ? 'i' : 'I'); ?>
        $dateFormat = new sfDateFormat($this->getUser()->getCulture());

      if (isset($filters['<?php echo $column->getName() ?>']['from']) && $filters['<?php echo $column->getName() ?>']['from'] !== '')
      {
        $filters['<?php echo $column->getName() ?>']['from'] = $dateFormat->format($filters['<?php echo $column->getName() ?>']['from'], '<?php echo $outputPattern?>', $dateFormat->getInputPattern('<?php echo $inputPattern ?>'));
      }
      if (isset($filters['<?php echo $column->getName() ?>']['to']) && $filters['<?php echo $column->getName() ?>']['to'] !== '')
      {
        $filters['<?php echo $column->getName() ?>']['to'] = $dateFormat->format($filters['<?php echo $column->getName() ?>']['to'], '<?php echo $outputPattern?>', $dateFormat->getInputPattern('<?php echo $inputPattern ?>'));
      }
<?php endif; ?>
<?php endforeach; ?>
      $this->getUser()->getAttributeHolder()->removeNamespace('sf_admin/<?php echo $this->getSingularName() ?>');
      $this->getUser()->getAttributeHolder()->removeNamespace('sf_admin/<?php echo $this->getSingularName() ?>/filters');
      $this->getUser()->getAttributeHolder()->add($filters, 'sf_admin/<?php echo $this->getSingularName() ?>/filters');
    }
<?php endif; ?>
  }

  protected function processSort ()
  {
    if ($this->getRequestParameter('sort'))
    {
      $this->getUser()->setAttribute('sort', $this->getRequestParameter('sort'), 'sf_admin/<?php echo $this->getSingularName() ?>/sort');
      $this->getUser()->setAttribute('type', $this->getRequestParameter('type', 'asc'), 'sf_admin/<?php echo $this->getSingularName() ?>/sort');
    }

    if (!$this->getUser()->getAttribute('sort', null, 'sf_admin/<?php echo $this->getSingularName() ?>/sort'))
    {
<?php if ($sort = $this->getParameterValue('list.sort')): ?>
<?php if (is_array($sort)): ?>
      $this->getUser()->setAttribute('sort', '<?php echo $sort[0] ?>', 'sf_admin/<?php echo $this->getSingularName() ?>/sort');
      $this->getUser()->setAttribute('type', '<?php echo $sort[1] ?>', 'sf_admin/<?php echo $this->getSingularName() ?>/sort');
<?php else: ?>
      $this->getUser()->setAttribute('sort', '<?php echo $sort ?>', 'sf_admin/<?php echo $this->getSingularName() ?>/sort');
      $this->getUser()->setAttribute('type', 'asc', 'sf_admin/<?php echo $this->getSingularName() ?>/sort');
<?php endif; ?>
<?php endif; ?>
    }
  }

  protected function addFiltersCriteria ($q)
  {
<?php if ($this->getParameterValue('list.filters')): ?>
<?php foreach ($this->getColumns('list.filters') as $column): $type = $column->getDoctrineType() ?>
<?php if (($column->isPartial() || $column->isComponent()) && $this->getParameterValue('list.fields.'.$column->getName().'.filter_criteria_disabled')) continue ?>
<?php 
$filterColumnName = $column->getName();
if ($column->isForeignKey())
  $filterColumnName = $column->getColumnName();
$queryColumn = $this->getClassName().'.'.$filterColumnName;?>
    if (isset($this->filters['<?php echo $column->getName() ?>_is_empty']))
    {
      $q->addWhere("<?php echo $queryColumn?> = '' OR <?php echo $queryColumn?> IS NULL");
    }
<?php if ($type == 'date' || $type == 'timestamp'): ?>
    else if (isset($this->filters['<?php echo $column->getName() ?>']))
    {
      if (isset($this->filters['<?php echo $column->getName() ?>']['from']) && $this->filters['<?php echo $column->getName() ?>']['from'] !== '')
      {
<?php 
$dateArg = "\$this->filters['{$column->getName()}']['%s']";
?>
        $q->addWhere('<?php echo $queryColumn?> >= ?', <?php echo sprintf($dateArg, 'from') ?>);
      }
      if (isset($this->filters['<?php echo $column->getName() ?>']['to']) && $this->filters['<?php echo $column->getName() ?>']['to'] !== '')
      {
        $q->addWhere('<?php echo $queryColumn?> <= ?', <?php echo sprintf($dateArg, 'to') ?>);
      }

    }
<?php else: ?>
    else if (isset($this->filters['<?php echo $column->getName() ?>']) && $this->filters['<?php echo $column->getName() ?>'] !== '')
    {
<?php if ($type == 'char' || $type == 'string'): ?>
      $q->addWhere("<?php echo $queryColumn?> LIKE ?", '%'.$this->filters['<?php echo $column->getName() ?>'].'%');
<?php else: ?>
      $q->addWhere("<?php echo $queryColumn?> = ?", $this->filters['<?php echo $column->getName() ?>']);
<?php endif; ?>
    }
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
  }

  protected function addSortCriteria ($q)
  {
    if ($sort_column = $this->getUser()->getAttribute('sort', null, 'sf_admin/<?php echo $this->getSingularName() ?>/sort'))
    {
      $table = sfDoctrine::getTable('<?php echo $this->getClassName()?>');
      $colNames = array_keys($table->getColumns());
      if (!in_array($sort_column, $colNames)) // illegal column name
        return;
      if ($this->getUser()->getAttribute('type', null, 'sf_admin/<?php echo $this->getSingularName() ?>/sort') == 'asc')
      {
        $q->orderBy('<?php echo $this->getClassName()?>.'.$sort_column);
      }
      else
      {
        $q->orderBy('<?php echo $this->getClassName()?>.'.$sort_column.' desc');
      }
    }
  }

  protected function addJavascriptsForEdit()
  {
    $this->getResponse()->addJavascript(sfConfig::get('sf_prototype_web_dir').'/js/prototype');
    $this->getResponse()->addJavascript(sfConfig::get('sf_admin_web_dir').'/js/collapse');
    $this->getResponse()->addJavascript(sfConfig::get('sf_admin_web_dir').'/js/double_list');
  }

  protected function getLabels()
  {
    return array(
<?php foreach ($this->getColumnCategories('edit.display') as $category): ?>
<?php foreach ($this->getColumns('edit.display', $category) as $name => $column): ?>
      '<?php echo $this->getSingularName() ?>{<?php echo $column->getName() ?>}' => '<?php $label_name = str_replace("'", "\\'", $this->getParameterValue('edit.fields.'.$column->getName().'.name')); echo $label_name ?><?php if ($label_name): ?>:<?php endif; ?>',
<?php endforeach; ?>
<?php endforeach; ?>
    );
  }
}
