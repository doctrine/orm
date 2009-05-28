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

final class DoctrineEntity extends \Annotation {
    public $repositoryClass;
}
final class DoctrineInheritanceType extends \Annotation {}
final class DoctrineDiscriminatorColumn extends \Annotation {
    public $name;
    public $type;
    public $length;
}
final class DoctrineDiscriminatorMap extends \Annotation {}
final class DoctrineDiscriminatorValue extends \Annotation {}
final class DoctrineSubClasses extends \Annotation {}
final class DoctrineId extends \Annotation {}
final class DoctrineGeneratedValue extends \Annotation {
    public $strategy;
    //public $generator;
}
final class DoctrineVersion extends \Annotation {}
final class DoctrineJoinColumn extends \Annotation {
    public $name;
    public $referencedColumnName;
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $onUpdate;
}
final class DoctrineJoinColumns extends \Annotation {}
final class DoctrineColumn extends \Annotation {
    public $type;
    public $length;
    public $unique = false;
    public $nullable = false;
    public $quote = false;
}
final class DoctrineOneToOne extends \Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch;
    public $optional;
}
final class DoctrineOneToMany extends \Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
    public $fetch;
}
final class DoctrineManyToOne extends \Annotation {
    public $targetEntity;
    public $cascade;
    public $fetch;
    public $optional;
}
final class DoctrineManyToMany extends \Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
    public $fetch;
}
final class DoctrineElementCollection extends \Annotation {
    public $tableName;
}
final class DoctrineTable extends \Annotation {
    public $name;
    public $catalog;
    public $schema;
}
final class DoctrineJoinTable extends \Annotation {
    public $name;
    public $catalog;
    public $schema;
    public $joinColumns;
    public $inverseJoinColumns;
}
final class DoctrineSequenceGenerator extends \Annotation {
    //public $name;
    public $sequenceName;
    public $allocationSize = 20;
    public $initialValue = 1;
    /** The name of the class that defines the generator. */
    //public $definingClass;
}
final class DoctrineChangeTrackingPolicy extends \Annotation {}
