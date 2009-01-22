<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/* Annotations */

final class DoctrineEntity extends \Addendum\Annotation {
    public $tableName;
    public $repositoryClass;
    public $inheritanceType;
}
final class DoctrineInheritanceType extends \Addendum\Annotation {}
final class DoctrineDiscriminatorColumn extends \Addendum\Annotation {
    public $name;
    public $type;
    public $length;
}
final class DoctrineDiscriminatorMap extends \Addendum\Annotation {}
final class DoctrineSubClasses extends \Addendum\Annotation {}
final class DoctrineId extends \Addendum\Annotation {}
final class DoctrineIdGenerator extends \Addendum\Annotation {}
final class DoctrineVersion extends \Addendum\Annotation {}
final class DoctrineJoinColumn extends \Addendum\Annotation {
    public $name;
    public $type;
    public $length;
    public $onDelete;
    public $onUpdate;
}
final class DoctrineColumn extends \Addendum\Annotation {
    public $type;
    public $length;
    public $unique;
    public $nullable;
}
final class DoctrineOneToOne extends \Addendum\Annotation {
    public $targetEntity;
    public $mappedBy;
    public $joinColumns;
    public $cascade;
}
final class DoctrineOneToMany extends \Addendum\Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
}
final class DoctrineManyToOne extends \Addendum\Annotation {
    public $targetEntity;
    public $joinColumns;
    public $cascade;
}
final class DoctrineManyToMany extends \Addendum\Annotation {
    public $targetEntity;
    public $joinColumns;
    public $inverseJoinColumns;
    public $joinTable;
    public $mappedBy;
    public $cascade;
}

