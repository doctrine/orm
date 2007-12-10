<?php
/**
 * OrderByItem = PathExpression
 */
class Doctrine_Query_Production_GroupByItem extends Doctrine_Query_Production
{
    public function execute(array $params = array())
    {
        $this->PathExpression();
    }
}
