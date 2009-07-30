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

namespace Doctrine\ORM\Mapping;

use \Doctrine\Common\Annotations\Annotation;

/* Annotations */

final class Entity extends \Doctrine\Common\Annotations\Annotation {
    public $repositoryClass;
}
final class MappedSuperclass extends Annotation {}
final class InheritanceType extends \Doctrine\Common\Annotations\Annotation {}
final class DiscriminatorColumn extends \Doctrine\Common\Annotations\Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $type;
    public $length;
}
final class DiscriminatorValue extends \Doctrine\Common\Annotations\Annotation {}
final class SubClasses extends \Doctrine\Common\Annotations\Annotation {}
final class Id extends \Doctrine\Common\Annotations\Annotation {}
final class GeneratedValue extends \Doctrine\Common\Annotations\Annotation {
    public $strategy;
    //public $generator;
}
final class Version extends \Doctrine\Common\Annotations\Annotation {}
final class JoinColumn extends \Doctrine\Common\Annotations\Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $referencedColumnName;
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $onUpdate;
}
final class JoinColumns extends \Doctrine\Common\Annotations\Annotation {}
final class Column extends \Doctrine\Common\Annotations\Annotation {
    public $type;
    public $length;
    public $unique = false;
    public $nullable = false;
    public $default;
    public $name;
}
final class OneToOne extends \Doctrine\Common\Annotations\Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch;
    public $optional;
    public $orphanRemoval = false;
}
final class OneToMany extends \Doctrine\Common\Annotations\Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
    public $fetch;
    public $orphanRemoval = false;
}
final class ManyToOne extends \Doctrine\Common\Annotations\Annotation {
    public $targetEntity;
    public $cascade;
    public $fetch;
    public $optional;
}
final class ManyToMany extends \Doctrine\Common\Annotations\Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch;
}
final class ElementCollection extends \Doctrine\Common\Annotations\Annotation {
    public $tableName;
}
final class Table extends \Doctrine\Common\Annotations\Annotation {
    public $name;
    public $schema;
}
final class JoinTable extends \Doctrine\Common\Annotations\Annotation {
    public $name;
    public $schema;
    public $joinColumns;
    public $inverseJoinColumns;
}
final class SequenceGenerator extends \Doctrine\Common\Annotations\Annotation {
    public $sequenceName;
    public $allocationSize = 10;
    public $initialValue = 1;
}
final class ChangeTrackingPolicy extends \Doctrine\Common\Annotations\Annotation {}

/* Annotations for lifecycle callbacks */
final class LifecycleListener extends \Doctrine\Common\Annotations\Annotation {}
final class PrePersist extends \Doctrine\Common\Annotations\Annotation {}
final class PostPersist extends \Doctrine\Common\Annotations\Annotation {}
final class PreUpdate extends \Doctrine\Common\Annotations\Annotation {}
final class PostUpdate extends \Doctrine\Common\Annotations\Annotation {}
final class PreRemove extends \Doctrine\Common\Annotations\Annotation {}
final class PostRemove extends \Doctrine\Common\Annotations\Annotation {}
final class PostLoad extends \Doctrine\Common\Annotations\Annotation {}

/* Generic annotation for Doctrine extensions */
final class DoctrineX extends \Doctrine\Common\Annotations\Annotation {}
