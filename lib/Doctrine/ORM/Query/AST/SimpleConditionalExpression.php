<?php

/**
 * SimpleConditionalExpression ::= ExistsExpression |
 *          (SimpleStateFieldPathExpression (ComparisonExpression | BetweenExpression | LikeExpression |
 *           InExpression | NullComparisonExpression)) |
 *          (CollectionValuedPathExpression EmptyCollectionComparisonExpression) |
 *          (EntityExpression CollectionMemberExpression)
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_SimpleConditionalExpression extends Doctrine_ORM_Query_AST
{
    
}

