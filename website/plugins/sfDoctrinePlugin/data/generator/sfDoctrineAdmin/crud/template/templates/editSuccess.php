[?php use_helper('ObjectDoctrineAdmin', 'Object', 'Date') ?]

[?php echo form_tag('<?php echo $this->getModuleName() ?>/update', 'multipart=true') ?]

<?php foreach ($this->getPrimaryKey() as $pk): ?>
[?php echo object_input_hidden_tag($<?php echo $this->getSingularName() ?>, 'get<?php echo $pk->getPhpName() ?>') ?]
<?php endforeach; ?>


<table>
<tbody>
<?php foreach ($this->getColumns('') as $index => $column): ?>
<?php if ($column->isPrimaryKey()) continue ?>
<?php if ($column->getName() == 'created_at' || $column->getName() == 'updated_at') continue ?>
<tr>
  <th><?php echo sfInflector::humanize(sfInflector::underscore($column->getPhpName())) ?>: </th>
  <td>[?php echo <?php echo $this->getColumnEditTag($column) ?> ?]</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
<hr />
[?php echo submit_tag('save') ?]
[?php if (<?php echo $this->getPrimaryKeyIsSet() ?>): ?]
  &nbsp;[?php echo link_to('delete', '<?php echo $this->getModuleName() ?>/delete?<?php echo $this->getPrimaryKeyUrlParams() ?>, 'post=true&confirm=Are you sure?') ?]
  &nbsp;[?php echo link_to('cancel', '<?php echo $this->getModuleName() ?>/show?<?php echo $this->getPrimaryKeyUrlParams() ?>) ?]
[?php else: ?]
  &nbsp;[?php echo link_to('cancel', '<?php echo $this->getModuleName() ?>/list') ?]
[?php endif; ?]
</form>
