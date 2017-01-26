<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-6256
 */
class DDC6256Test extends OrmFunctionalTestCase
{
    /** @var DDC6256StoredFileListener  */
    private $storedFileListener;

    protected function setUp()
    {
        parent::setUp();

        $this->storedFileListener = new DDC6256StoredFileListener();
        $this->_em->getConfiguration()->getEntityListenerResolver()->register($this->storedFileListener);

        try {
            $this->_schemaTool->createSchema(
                array(
                    $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC6256User'),
                    $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC6256StoredFile'),
                )
            );
        } catch(\Exception $ignored) {

        }
    }

    public function testAvatarListenerIsWellAttached()
    {
        $avatar = new DDC6256StoredFile();

        $this->_em->persist($avatar);
        $this->_em->flush();

        $this->_em->remove($avatar);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals(['prePersist', 'postRemove'], $this->storedFileListener->calls);
    }

    public function testAvatarListenerIsCalled()
    {
        $user = new DDC6256User();
        $avatar = new DDC6256StoredFile();

        $user->setAvatar($avatar);

        $this->_em->persist($user);
        $this->_em->flush();

        $user->setAvatar(null);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals(['prePersist', 'postRemove'], $this->storedFileListener->calls);
    }

}

/**
 * @Entity
 */
class DDC6256User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * OneToOne(targetEntity="StoredFile", orphanRemoval=true, cascade={"all"})
     */
    private $avatar;

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }
}

/**
 * @Entity
 * @EntityListeners({"DDC6256StoredFileListener"})
 */
class DDC6256StoredFile
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
}


class DDC6256StoredFileListener
{
    public $calls = [];

    public function prePersist()
    {
        $this->calls[] = __FUNCTION__;
    }

    public function postRemove()
    {
        $this->calls[] = __FUNCTION__;
    }
}
