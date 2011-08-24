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

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class Entity extends Annotation {
    public $repositoryClass;
    public $readOnly = false;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class MappedSuperclass extends Annotation {}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class InheritanceType extends Annotation {}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class DiscriminatorColumn extends Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $type;
    public $length;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class DiscriminatorMap extends Annotation {}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class Id extends Annotation {}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class GeneratedValue extends Annotation {
    public $strategy = 'AUTO';
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class Version extends Annotation {}

/** 
 * @Annotation 
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class JoinColumn extends Annotation {
    public $name;
    public $fieldName; // field name used in non-object hydration (array/scalar)
    public $referencedColumnName = 'id';
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $columnDefinition;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class JoinColumns extends Annotation {}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
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

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class OneToOne extends Annotation {
    public $targetEntity;
    public $mappedBy;
    public $inversedBy;
    public $cascade;
    public $fetch = 'LAZY';
    public $orphanRemoval = false;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class OneToMany extends Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
    public $fetch = 'LAZY';
    public $orphanRemoval = false;
    public $indexBy;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class ManyToOne extends Annotation {
    public $targetEntity;
    public $cascade;
    public $fetch = 'LAZY';
    public $inversedBy;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class ManyToMany extends Annotation {
    public $targetEntity;
    public $mappedBy;
    public $inversedBy;
    public $cascade;
    public $fetch = 'LAZY';
    public $indexBy;
}

/** 
 * @Annotation 
 * @Target("ALL")
 * @todo check available targets
 */
final class ElementCollection extends Annotation {
    public $tableName;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class Table extends Annotation {
    public $name;
    public $schema;
    public $indexes;
    public $uniqueConstraints;
}

/** 
 * @Annotation 
 * @Target("ANNOTATION")
 */
final class UniqueConstraint extends Annotation {
    public $name;
    public $columns;
}

/** 
 * @Annotation 
 * @Target("ANNOTATION")
 */
final class Index extends Annotation {
    public $name;
    public $columns;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class JoinTable extends Annotation {
    public $name;
    public $schema;
    public $joinColumns = array();
    public $inverseJoinColumns = array();
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class SequenceGenerator extends Annotation {
    public $sequenceName;
    public $allocationSize = 1;
    public $initialValue = 1;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class ChangeTrackingPolicy extends Annotation {}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class OrderBy extends Annotation {}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class NamedQueries extends Annotation {}

/** 
 * @Annotation 
 * @Target("ANNOTATION")
 */
final class NamedQuery extends Annotation {
    public $name;
    public $query;
}

/* Annotations for lifecycle callbacks */

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class HasLifecycleCallbacks extends Annotation {}

/** 
 * @Annotation 
 * @Target("METHOD")
 */
final class PrePersist extends Annotation {}

/** 
 * @Annotation 
 * @Target("METHOD")
 */
final class PostPersist extends Annotation {}

/** 
 * @Annotation 
 * @Target("METHOD")
 */
final class PreUpdate extends Annotation {}

/** 
 * @Annotation 
 * @Target("METHOD")
 */
final class PostUpdate extends Annotation {}

/** 
 * @Annotation 
 * @Target("METHOD")
 */
final class PreRemove extends Annotation {}

/** 
 * @Annotation 
 * @Target("METHOD")
 */
final class PostRemove extends Annotation {}

/** 
 * @Annotation 
 * @Target("METHOD")
 */
final class PostLoad extends Annotation {}
