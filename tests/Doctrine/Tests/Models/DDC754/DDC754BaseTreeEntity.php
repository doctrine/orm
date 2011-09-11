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

namespace Doctrine\Tests\Models\DDC754;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="ddc754_tree")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *      "foo" = "DDC754FooEntity",
 *      "bar" = "DDC754BarEntity",
 *      "base"= "DDC754BaseTreeEntity"
 * })
 */
class DDC754BaseTreeEntity
{

    /**
     * @Id
     * @Column(type = "integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Column(type = "string")
     * @var string
     */
    protected $name;

    /**
     * @ManyToOne(targetEntity="self", inversedBy="children", fetch="EAGER")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     * @var BaseTreeEntity|NULL	 
     */
    protected $parent;

    /**
     * @OneToMany(targetEntity="self", mappedBy="parent", cascade={"persist"})
     */
    private $children;

    public function __construct($name = null)
    {
        $this->children = new ArrayCollection();
        $this->name     = $name;
    }

    /**
     * @return int 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name 
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param DDC754BaseTreeEntity $parent 
     */
    public function setParent(DDC754BaseTreeEntity $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return DDC754BaseTreeEntity
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return Doctrine\Common\Collections\ArrayCollection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param ArrayCollection $children 
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;
    }

    /**
     * @param DDC754BaseTreeEntity $child 
     */
    public function addChildren(DDC754BaseTreeEntity $child)
    {
        $child->setParent($this);
        $this->children->add($child);
    }
    
    
    public function __toString()
    {
        return "{id:{$this->id},name:{$this->name}}";
    }
}