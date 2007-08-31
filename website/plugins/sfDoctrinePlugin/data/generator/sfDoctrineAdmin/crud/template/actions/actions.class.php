[?php

/**
 * <?php echo $this->getGeneratedModuleName() ?> actions.
 *
 * @package    ##PROJECT_NAME##
 * @subpackage <?php echo $this->getGeneratedModuleName() ?>

 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Olivier Verdier <Olivier.Verdier@gmail.com>
 * @version    SVN: $Id: actions.class.php 3923 2007-05-03 19:42:33Z gnat $
 */
class <?php echo $this->getGeneratedModuleName() ?>Actions extends sfActions
{
  public function executeIndex ()
  {
    return $this->forward('<?php echo $this->getModuleName() ?>', 'list');
  }

  public function executeList ()
  {
  	$this-><?php echo $this->getPluralName() ?> = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->findAll();
  }

  public function executeShow ()
  {
    $this-><?php echo $this->getSingularName() ?> = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->find(<?php echo $this->getRetrieveByPkParamsForAction('') ?>);    
    $this->forward404Unless($this-><?php echo $this->getSingularName() ?>);
  }

  public function executeCreate ()
  {
    $this-><?php echo $this->getSingularName() ?> = new <?php echo $this->getClassName() ?>();
    $this->setTemplate('edit');
  }

  public function executeEdit ()
  {
    $this-><?php echo $this->getSingularName() ?> = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->find(<?php echo $this->getRetrieveByPkParamsForAction('') ?>);    
    $this->forward404Unless($this-><?php echo $this->getSingularName() ?>);
  }

  public function executeDelete ()
  {
    $this-><?php echo $this->getSingularName() ?> = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->find(<?php echo $this->getRetrieveByPkParamsForAction('') ?>);    
    
    $this->forward404Unless($this-><?php echo $this->getSingularName() ?>);

    try
    {
      $this-><?php echo $this->getSingularName() ?>->delete();
      $this->redirect('<?php echo $this->getModuleName() ?>/list');
    }
    catch (Doctrine_Exception $e)
    {
      $this->getRequest()->setError('delete', 'Could not delete the selected <?php echo sfInflector::humanize($this->getSingularName()) ?>. Make sure it does not have any associated items.');
      return $this->forward('<?php echo $this->getModuleName() ?>', 'list');
    }
  }

  public function executeUpdate ()
  {
    if (<?php echo $this->getTestPksForGetOrCreate(false) ?>)
    {
      $<?php echo $this->getSingularName() ?> = new <?php echo $this->getClassName() ?>();
    }
    else
    {
      $<?php echo $this->getSingularName() ?> = sfDoctrine::getTable('<?php echo $this->getClassName() ?>')->find(<?php echo $this->getRetrieveByPkParamsForAction('') ?>);
      $this->forward404Unless($<?php echo $this->getSingularName() ?>);
    }

    $formData = $this->getRequestParameter('<?php echo $this->getSingularName() ?>');
<?php foreach ($this->getColumns('') as $index => $column): 
$type = $column->getDoctrineType(); 
$name = $column->getName(); ?>
<?php if($column->isPrimaryKey()) continue ?>
<?php if ($name == 'created_at' || $name == 'updated_at') continue ?>
<?php if ($type == 'boolean'): ?>
    <?php $boolVar = "\$formData['$name']";
      echo $this->getColumnSetter($column, "isset($boolVar) ? $boolVar : 0", false, '')?>;
<?php continue; ?>
<?php endif; // boolean case ?>
    if ($newValue = $formData['<?php echo $name ?>'])
    {
<?php if ($type == 'date' || $type == 'timestamp'): ?>
<?php $inputPattern = ($type == 'date' ? 'd' : 'g');
$outputPattern = ($type == 'date' ? 'i' : 'I'); ?>
       $dateFormat = new sfDateFormat($this->getUser()->getCulture());
       <?php echo $this->getColumnSetter($column, sprintf('$dateFormat->format($newValue, \'%s\', $dateFormat->getInputPattern(\'%s\'))', $outputPattern, $inputPattern), false, '');?>;
<?php elseif ($column->isForeignKey()): ?>
       $<?php echo $this->getSingularName()?>->set('<?php echo $column->getColumnName()?>', (empty($newValue) ? null : $newValue));
<?php else: ?>
	     <?php echo $this->getColumnSetter($column, '$newValue', false, '');?>;
<?php endif; ?>
    }
<?php endforeach; ?>

    $<?php echo $this->getSingularName() ?>->save();

    return $this->redirect('<?php echo $this->getModuleName() ?>/show?<?php echo $this->getPrimaryKeyUrlParams() ?>);
<?php //' ?>
  }
}
