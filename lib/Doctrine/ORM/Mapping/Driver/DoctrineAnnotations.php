<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/* Annotations */

final class Entity extends \Annotation {
    public $repositoryClass;
}
final class InheritanceType extends \Annotation {}
final class DiscriminatorColumn extends \Annotation {
    public $name;
    public $type;
    public $length;
}
//final class DiscriminatorMap extends \Annotation {}
final class DiscriminatorValue extends \Annotation {}
final class SubClasses extends \Annotation {}
final class Id extends \Annotation {}
final class GeneratedValue extends \Annotation {
    public $strategy;
    //public $generator;
}
final class Version extends \Annotation {}
final class JoinColumn extends \Annotation {
    public $name;
    public $referencedColumnName;
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $onUpdate;
}
final class JoinColumns extends \Annotation {}
final class Column extends \Annotation {
    public $type;
    public $length;
    public $unique = false;
    public $nullable = false;
    public $name;
}
final class OneToOne extends \Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch;
    public $optional;
}
final class OneToMany extends \Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
    public $fetch;
}
final class ManyToOne extends \Annotation {
    public $targetEntity;
    public $cascade;
    public $fetch;
    public $optional;
}
final class ManyToMany extends \Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch;
}
final class ElementCollection extends \Annotation {
    public $tableName;
}
final class Table extends \Annotation {
    public $name;
    public $schema;
}
final class JoinTable extends \Annotation {
    public $name;
    public $schema;
    public $joinColumns;
    public $inverseJoinColumns;
}
final class SequenceGenerator extends \Annotation {
    //public $name;
    public $sequenceName;
    public $allocationSize = 10;
    public $initialValue = 1;
}
final class ChangeTrackingPolicy extends \Annotation {}
final class DoctrineX extends \Annotation {}
