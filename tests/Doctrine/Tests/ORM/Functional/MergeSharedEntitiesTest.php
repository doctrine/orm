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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;

class MergeSharedEntitiesTest extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\MSEFile'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\MSEPicture'),
            ));
        } catch (ToolsException $ignored) {
        }
    }

    public function testMergeSharedNewEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $picture = $this->_em->merge($picture);

        $this->assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    public function testMergeSharedManagedEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $this->_em->persist($file);
        $this->_em->persist($picture);
        $this->_em->flush();
        $this->_em->clear();

        $picture = $this->_em->merge($picture);

        $this->assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    public function testMergeSharedDetachedSerializedEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $serializedPicture = serialize($picture);

        $this->_em->persist($file);
        $this->_em->persist($picture);
        $this->_em->flush();
        $this->_em->clear();

        $picture = $this->_em->merge(unserialize($serializedPicture));

        $this->assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    /**
     * @group DDC-2704
     */
    public function testMergeInheritedTransientPrivateProperties()
    {
        $admin1 = new MSEAdmin();
        $admin2 = new MSEAdmin();

        $admin1->id = 123;
        $admin2->id = 123;

        $this->_em->persist($admin1);

        $admin2->setSession('zeh current session data');

        $this->assertSame($admin1, $this->_em->merge($admin2));
        $this->assertSame('zeh current session data', $admin1->getSession());
    }
}

/** @Entity */
class MSEPicture
{
    /** @Column(type="integer") @Id @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="MSEFile", cascade={"merge"}) */
    public $file;

    /** @ManyToOne(targetEntity="MSEFile", cascade={"merge"}) */
    public $otherFile;
}

/** @Entity */
class MSEFile
{
    /** @Column(type="integer") @Id @GeneratedValue(strategy="AUTO") */
    public $id;
}

/** @MappedSuperclass */
abstract class MSEUser
{
    private $session; // intentionally transient property

    public function getSession()
    {
        return $this->session;
    }

    public function setSession($session)
    {
        $this->session = $session;
    }
}

/** @Entity */
class MSEAdmin extends MSEUser
{
    /** @Column(type="integer") @Id @GeneratedValue(strategy="NONE") */
    public $id;
}
