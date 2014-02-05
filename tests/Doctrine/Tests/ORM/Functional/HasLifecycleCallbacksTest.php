<?php

namespace Doctrine\Tests\ORM\Functional;

class HasLifecycleCallbacksTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testHasLifecycleCallbacksSubExtendsSuper()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubExtendsSuper'));

        $entity = new HasLifecycleCallbacksSubExtendsSuper();
        $this->_em->persist($entity);
        $this->_em->flush();

        // Neither class is annotated. No callback is invoked.
        $this->assertCount(0, $entity->invoked);
    }

    public function testHasLifecycleCallbacksSubExtendsSuperAnnotated()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubExtendsSuperAnnotated'));

        $entity = new HasLifecycleCallbacksSubExtendsSuperAnnotated();
        $this->_em->persist($entity);
        $this->_em->flush();

        /* The sub-class is not annotated, so the callback in the annotated
         * super-class is invokved.
         */
        $this->assertCount(1, $entity->invoked);
        $this->assertEquals('super', $entity->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuper()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubAnnotatedExtendsSuper'));

        $entity = new HasLifecycleCallbacksSubAnnotatedExtendsSuper();
        $this->_em->persist($entity);
        $this->_em->flush();

        /* The sub-class is annotated, but the method is declared in the super-
         * class, which is not annotated. No callback is invoked.
         */
        $this->assertCount(0, $entity->invoked);
    }

    public function testHasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated'));

        $entity = new HasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated();
        $this->_em->persist($entity);
        $this->_em->flush();

        /* The sub-class is annotated, but it doesn't override the method, so
         * the callback in the annotated super-class is invoked.
         */
        $this->assertCount(1, $entity->invoked);
        $this->assertEquals('super', $entity->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuper()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubOverrideExtendsSuper'));

        $entity = new HasLifecycleCallbacksSubOverrideExtendsSuper();
        $this->_em->persist($entity);
        $this->_em->flush();

        // Neither class is annotated. No callback is invoked.
        $this->assertCount(0, $entity->invoked);
    }

    public function testHasLifecycleCallbacksSubOverrideExtendsSuperAnnotated()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated'));

        $entity = new HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated();
        $this->_em->persist($entity);
        $this->_em->flush();

        /* The sub-class is invoked because it overrides the method in the
         * annotated super-class.
         */
        $this->assertCount(1, $entity->invoked);
        $this->assertEquals('sub', $entity->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper'));

        $entity = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper();
        $this->_em->persist($entity);
        $this->_em->flush();

        /* The sub-class is invoked because it overrides the method and is
         * annotated.
         */
        $this->assertCount(1, $entity->invoked);
        $this->assertEquals('sub', $entity->invoked[0]);
    }

    public function testHasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated()
    {
        $this->setUpEntitySchema(array(__NAMESPACE__ . '\HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated'));

        $entity = new HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated();
        $this->_em->persist($entity);
        $this->_em->flush();

        /* Since both classes are annotated and declare the method, the callback
         * is registered twice but the sub-class should be invoked only once.
         */
        $this->assertCount(1, $entity->invoked);
        $this->assertEquals('sub', $entity->invoked[0]);
    }
}

/** @MappedSuperclass */
abstract class HasLifecycleCallbacksSuper
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    public $invoked = array();

    /** @PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'super';
    }
}

/**
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class HasLifecycleCallbacksSuperAnnotated
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    public $invoked = array();

    /** @PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'super';
    }
}

/**
 * @Entity
 * @Table(name="hlc_cb_ses")
 */
class HasLifecycleCallbacksSubExtendsSuper extends HasLifecycleCallbacksSuper
{
}

/**
 * @Entity
 * @Table(name="hlc_cb_sesa")
 */
class HasLifecycleCallbacksSubExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
}

/**
 * @Entity
 * @Table(name="hlc_cb_saes")
 * @HasLifecycleCallbacks
 */
class HasLifecycleCallbacksSubAnnotatedExtendsSuper extends HasLifecycleCallbacksSuper
{
}

/**
 * @Entity
 * @Table(name="hlc_cb_saesa")
 * @HasLifecycleCallbacks
 */
class HasLifecycleCallbacksSubAnnotatedExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
}

/**
 * @Entity
 * @Table(name="hlc_cb_soes")
 */
class HasLifecycleCallbacksSubOverrideExtendsSuper extends HasLifecycleCallbacksSuper
{
    /** @PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'sub';
    }
}

/**
 * @Entity
 * @Table(name="hlc_cb_soesa")
 */
class HasLifecycleCallbacksSubOverrideExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'sub';
    }
}

/**
 * @Entity
 * @Table(name="hlc_cb_soaes")
 * @HasLifecycleCallbacks
 */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuper extends HasLifecycleCallbacksSuper
{
    /** @PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'sub';
    }
}

/**
 * @Entity
 * @Table(name="hlc_cb_soaesa")
 * @HasLifecycleCallbacks
 */
class HasLifecycleCallbacksSubOverrideAnnotatedExtendsSuperAnnotated extends HasLifecycleCallbacksSuperAnnotated
{
    /** @PrePersist */
    public function prePersist()
    {
        $this->invoked[] = 'sub';
    }
}
