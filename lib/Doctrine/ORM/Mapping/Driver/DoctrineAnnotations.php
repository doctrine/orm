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

final class DoctrineEntity extends \Addendum\Annotation {
    public $repositoryClass;
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
    public $referencedColumnName;
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $onUpdate;
}
final class DoctrineJoinColumns extends \Addendum\Annotation {}
final class DoctrineColumn extends \Addendum\Annotation {
    public $type;
    public $length;
    public $unique = false;
    public $nullable = false;
}
final class DoctrineOneToOne extends \Addendum\Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
}
final class DoctrineOneToMany extends \Addendum\Annotation {
    public $mappedBy;
    public $targetEntity;
    public $cascade;
}
final class DoctrineManyToOne extends \Addendum\Annotation {
    public $targetEntity;
    public $cascade;
}
final class DoctrineManyToMany extends \Addendum\Annotation {
    public $targetEntity;
    public $mappedBy;
    public $cascade;
}
final class DoctrineElementCollection extends \Addendum\Annotation {
    public $tableName;
}
final class DoctrineTable extends \Addendum\Annotation {
    public $name;
    public $catalog;
    public $schema;
}
final class DoctrineJoinTable extends \Addendum\Annotation {
    public $name;
    public $catalog;
    public $schema;
    public $joinColumns;
    public $inverseJoinColumns;
}