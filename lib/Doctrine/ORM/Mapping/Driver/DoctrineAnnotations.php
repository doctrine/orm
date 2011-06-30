<?php
/*
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

use Doctrine\Common\Annotations\Annotation;

/* Annotations */

/** @Annotation */
final class Entity extends Annotation {
    public $repositoryClass;
    public $readOnly = false;
}

/** @Annotation */
final class MappedSuperclass extends Annotation {}

/** @Annotation */
final class InheritanceType extends Annotation {}

/** @Annotation */
final class DiscriminatorColumn extends Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $type;
    public $length;
}

/** @Annotation */
final class DiscriminatorMap extends Annotation {}

/** @Annotation */
final class Id extends Annotation {}

/** @Annotation */
final class GeneratedValue extends Annotation {
    public $strategy = 'AUTO';
}

/** @Annotation */
final class Version extends Annotation {}

/** @Annotation */
final class JoinColumn extends Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $referencedColumnName = 'id';
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $onUpdate;
    public $columnDefinition;
}

/** @Annotation */
final class JoinColumns extends Annotation {}

/** @Annotation */
final class Column extends Annotation {
    public $type = 'string';
    public $length;
    // The precision for a decimal (exact numeric) column (Applies only for decimal column)
    public $precision = 0;
    // The scale for a decimal (exact numeric) column (Applies only for decimal column)
    public $scale = 0;
    public $unique = false;
    public $nullable = false;
    public $name;
    public $options = array();
    public $columnDefinition;
}

/** @Annotation */
final class OneToOne extends Annotation {
    public $targetEntity;
    public $mappedBy;
    public $inversedBy;
    public $cascade;
    public $fetch = 'LAZY';
    public $orphanRemoval = false;
}

/** @Annotation */
final class OneToMany extends Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
    public $fetch = 'LAZY';
    public $orphanRemoval = false;
    public $indexBy;
}

/** @Annotation */
final class ManyToOne extends Annotation {
    public $targetEntity;
    public $cascade;
    public $fetch = 'LAZY';
    public $inversedBy;
}

/** @Annotation */
final class ManyToMany extends Annotation {
    public $targetEntity;
    public $mappedBy;
    public $inversedBy;
    public $cascade;
    public $fetch = 'LAZY';
    public $indexBy;
}

/** @Annotation */
final class ElementCollection extends Annotation {
    public $tableName;
}

/** @Annotation */
final class Table extends Annotation {
    public $name;
    public $schema;
    public $indexes;
    public $uniqueConstraints;
}

/** @Annotation */
final class UniqueConstraint extends Annotation {
    public $name;
    public $columns;
}

/** @Annotation */
final class Index extends Annotation {
    public $name;
    public $columns;
}

/** @Annotation */
final class JoinTable extends Annotation {
    public $name;
    public $schema;
    public $joinColumns = array();
    public $inverseJoinColumns = array();
}

/** @Annotation */
final class SequenceGenerator extends Annotation {
    public $sequenceName;
    public $allocationSize = 1;
    public $initialValue = 1;
}

/** @Annotation */
final class ChangeTrackingPolicy extends Annotation {}

/** @Annotation */
final class OrderBy extends Annotation {}

/** @Annotation */
final class NamedQueries extends Annotation {}

/** @Annotation */
final class NamedQuery extends Annotation {
    public $name;
    public $query;
}

/* Annotations for lifecycle callbacks */
/** @Annotation */
final class HasLifecycleCallbacks extends Annotation {}

/** @Annotation */
final class PrePersist extends Annotation {}

/** @Annotation */
final class PostPersist extends Annotation {}

/** @Annotation */
final class PreUpdate extends Annotation {}

/** @Annotation */
final class PostUpdate extends Annotation {}

/** @Annotation */
final class PreRemove extends Annotation {}

/** @Annotation */
final class PostRemove extends Annotation {}

/** @Annotation */
final class PostLoad extends Annotation {}
