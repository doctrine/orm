<table>
<tbody>
<?php foreach ($this->getAllColumns() as $column): ?>
<tr>
<th><?php echo sfInflector::humanize(sfInflector::underscore($column->getPhpName())) ?>: </th>
<td>[?= $<?php echo $this->getSingularName() ?>->get<?php echo $column->getPhpName() ?>() ?]</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<hr />
[?php echo link_to('edit', '<?php echo $this->getModuleName() ?>/edit?<?php echo $this->getPrimaryKeyUrlParams() ?>) ?]
&nbsp;[?php echo link_to('list', '<?php echo $this->getModuleName() ?>/list') ?]
