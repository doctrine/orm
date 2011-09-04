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

abstract class Annotation {}


/* Annotations */

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class Entity extends Annotation {
    /** @var string */
    public $repositoryClass;
    /** @var boolean */
    public $readOnly = false;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class MappedSuperclass extends Annotation {
   
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class InheritanceType extends Annotation {
    /** @var string */
    public $value;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class DiscriminatorColumn extends Annotation {
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var integer */
    public $length;
    /** @var mixed */
    public $fieldName; // field name used in non-object hydration (array/scalar)
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class DiscriminatorMap extends Annotation {
    /** @var array<string> */
    public $value;
}

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
     /** @var string */
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
    /** @var string */
    public $name;
    /** @var string */
    public $referencedColumnName = 'id';
    /** @var boolean */
    public $unique = false;
    /** @var boolean */
    public $nullable = true;
    /** @var mixed */
    public $onDelete;
    /** @var string */
    public $columnDefinition;
    /** @var string */
    public $fieldName; // field name used in non-object hydration (array/scalar)
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class JoinColumns extends Annotation {
    /** @var array<Doctrine\ORM\Mapping\JoinColumn> */
    public $value;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class Column extends Annotation {
    /** @var string */
    public $name;
    /** @var mixed */
    public $type = 'string';
    /** @var integer */
    public $length;
    /** @var integer */
    public $precision = 0; // The precision for a decimal (exact numeric) column (Applies only for decimal column)
    /** @var integer */
    public $scale = 0; // The scale for a decimal (exact numeric) column (Applies only for decimal column)
    /** @var boolean */
    public $unique = false;
    /** @var boolean */
    public $nullable = false;
    /** @var array */
    public $options = array();
    /** @var string */
    public $columnDefinition;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class OneToOne extends Annotation {
    /** @var string */
    public $targetEntity;
    /** @var string */
    public $mappedBy;
    /** @var string */
    public $inversedBy;
    /** @var array<string> */
    public $cascade;
    /** @var string */
    public $fetch = 'LAZY';
    /** @var boolean */
    public $orphanRemoval = false;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class OneToMany extends Annotation {
    /** @var string */
    public $mappedBy;
    /** @var string */
    public $targetEntity;
    /** @var array<string> */
    public $cascade;
    /** @var string */
    public $fetch = 'LAZY';
    /** @var boolean */
    public $orphanRemoval = false;
    /** @var string */
    public $indexBy;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class ManyToOne extends Annotation {
    /** @var string */
    public $targetEntity;
    /** @var array<string> */
    public $cascade;
    /** @var string */
    public $fetch = 'LAZY';
    /** @var string */
    public $inversedBy;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class ManyToMany extends Annotation {
    /** @var string */
    public $targetEntity;
    /** @var string */
    public $mappedBy;
    /** @var string */
    public $inversedBy;
    /** @var array<string> */
    public $cascade;
    /** @var string */
    public $fetch = 'LAZY';
    /** @var string */
    public $indexBy;
}

/** 
 * @Annotation 
 * @Target("ALL")
 * @todo check available targets
 */
final class ElementCollection extends Annotation {
    /** @var string */
    public $tableName;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class Table extends Annotation {
    /** @var string */
    public $name;
    /** @var string */
    public $schema;
    /** @var array<Doctrine\ORM\Mapping\Index> */
    public $indexes;
    /** @var array<Doctrine\ORM\Mapping\UniqueConstraint> */
    public $uniqueConstraints;
}

/** 
 * @Annotation 
 * @Target("ANNOTATION")
 */
final class UniqueConstraint extends Annotation {
    /** @var string */
    public $name;
    /** @var array<string> */
    public $columns;
}

/** 
 * @Annotation 
 * @Target("ANNOTATION")
 */
final class Index extends Annotation {
    /** @var string */
    public $name;
    /** @var array<string> */
    public $columns;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class JoinTable extends Annotation {
    /** @var string */
    public $name;
    /** @var string */
    public $schema;
    /** @var array<Doctrine\ORM\Mapping\JoinColumn> */
    public $joinColumns = array();
    /** @var array<Doctrine\ORM\Mapping\JoinColumn> */
    public $inverseJoinColumns = array();
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class SequenceGenerator extends Annotation {
    /** @var string */
    public $sequenceName;
    /** @var integer */
    public $allocationSize = 1;
    /** @var integer */
    public $initialValue = 1;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class ChangeTrackingPolicy extends Annotation {
    /** @var string */
    public $value;
}

/** 
 * @Annotation 
 * @Target("PROPERTY")
 */
final class OrderBy extends Annotation {
    /** @var array<string> */
    public $value;
}

/** 
 * @Annotation 
 * @Target("CLASS")
 */
final class NamedQueries extends Annotation {
    /** @var array<Doctrine\ORM\Mapping\NamedQuery> */
    public $value;
}

/** 
 * @Annotation 
 * @Target("ANNOTATION")
 */
final class NamedQuery extends Annotation {
    /** @var string */
    public $name;
    /** @var string */
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
