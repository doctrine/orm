<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\Tests\OrmFunctionalTestCase;


/**
 * @group DDC-3775
 */
class DDC3775Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema([
            __NAMESPACE__ . '\DDC3775Page',
            __NAMESPACE__ . '\DDC3775PageMember',
            __NAMESPACE__ . '\DDC3775PageRelation',
            __NAMESPACE__ . '\DDC3775User'
        ]);

        $this->setupFixtureData($this->_em);
    }

    protected function setupFixtureData($manager) {
        // Users
        $user = new DDC3775User();
        $manager->persist($user);

        $userTwo = new DDC3775User();
        $manager->persist($userTwo);

        // Pages
        $page = new DDC3775Page();
        $page->setTitle('First test page');
        $page->setOwner($user);
        $manager->persist($page);

        $pageTwo = new DDC3775Page();
        $pageTwo->setTitle('Second test page');
        $pageTwo->setOwner($userTwo);
        $manager->persist($pageTwo);

        $pageThree = new DDC3775Page();
        $pageThree->setTitle('Third test page');
        $pageThree->setOwner($user);
        $manager->persist($pageThree);

        // PageMembers
        $pageMember = new DDC3775PageMember();
        $pageMember->setUser($user);
        $pageMember->setPage($page);
        $manager->persist($pageMember);

        $pageMember = new DDC3775PageMember();
        $pageMember->setUser($user);
        $pageMember->setPage($pageTwo);
        $manager->persist($pageMember);

        $pageMember = new DDC3775PageMember();
        $pageMember->setUser($userTwo);
        $pageMember->setPage($pageThree);
        $manager->persist($pageMember);

        // PageRelations
        $pageRelation = new DDC3775PageRelation();
        $pageRelation->setPageCreator($page);
        $pageRelation->setPageReferenced($pageTwo);
        $manager->persist($pageRelation);

        $pageRelation = new DDC3775PageRelation();
        $pageRelation->setPageCreator($pageTwo);
        $pageRelation->setPageReferenced($pageThree);
        $manager->persist($pageRelation);

        $pageRelation = new DDC3775PageRelation();
        $pageRelation->setPageCreator($page);
        $pageRelation->setPageReferenced($pageThree);
        $manager->persist($pageRelation);

        $manager->flush();
        $manager->clear();
    }

    public function testRelationsPagesShouldBeProxyIfNotPreviouslyFetched()
    {
        // After setupDoctrine, pages in $pageIdsToFetch have been added to the identitymap.
        $pageIdsToFetch = [1];
        $this->setupDoctrineCaches($pageIdsToFetch);

        $qb = $this->_em->getRepository(__NAMESPACE__ . '\DDC3775PageRelation')->createQueryBuilder('r')
            ->select('r');

        $relations = $qb->getQuery()->getResult();

        foreach ($relations as $relation) {
            // This page was not fetched before so it should be a proxy
            if (false === in_array($relation->getPageCreator()->getId(), $pageIdsToFetch)) {
                $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $relation->getPageCreator());
            } elseif (false === in_array($relation->getPageReferenced()->getId(), $pageIdsToFetch)) {
                $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $relation->getPageReferenced());
            }
        }
    }


    public function testRelationsPagesShouldHaveNonNullTitleBecauseItIsNonNullable() {
        // After setupDoctrine, pages in $pageIdsToFetch have been added to the identitymap.
        $pageIdsToFetch = [1];
        $this->setupDoctrineCaches($pageIdsToFetch);

        $qb = $this->_em->getRepository(__NAMESPACE__ . '\DDC3775PageRelation')->createQueryBuilder('r')
            ->select('r, p1, p2')
            ->leftJoin('r.pageCreator', 'p1')
            ->leftJoin('r.pageReferenced', 'p2');

        $relations = $qb->getQuery()->getResult();

        foreach ($relations as $relation) {
            $this->assertNotNull($relation->getPageCreator()->getTitle());
            $this->assertNotNull($relation->getPageReferenced()->getTitle());
        }
    }

    protected function setupDoctrineCaches($preFetchPages = [1]) {
        $qb = $this->_em->getRepository(__NAMESPACE__ . '\DDC3775Page')->createQueryBuilder('p')
            ->select('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $preFetchPages);

        $allPages = $qb->getQuery()->getResult();

        $entityIds = [];
        foreach($allPages as $user) {
            $entityIds = $user->getId();
        }

        $qb = $this->_em->getRepository(__NAMESPACE__ . '\DDC3775Page')->createQueryBuilder('p')
            ->select('partial p.{id}, pm')
            ->leftJoin('p.pageMembers', 'pm', 'WITH', 'p.id IN (:ids)')
            ->setParameter('ids', $entityIds);

        $qb->getQuery()->getResult();
    }
}

/**
 * Page
 *
 * @Table(name="ddc3775_page")
 * @Entity
 */
class DDC3775Page
{
    /**
     * @var string
     *
     * @Column(name="title", type="string", nullable=false)
     */
    private $title;

    public function getTitle() 
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @OneToMany(targetEntity="DDC3775PageMember", mappedBy="page")
     */
    private $pageMembers;

    /**
     * @ManyToOne(targetEntity="DDC3775User", inversedBy="ownedPages")
     * @JoinColumns({
     *   @JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $owner;

    /**
     * Set owner
     *
     * @param DDC3775User $owner
     * @return DDC3775Page
     */
    public function setOwner(DDC3775User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }
}


/**
 * PageMember
 *
 * @Table(name="ddc3775_page_member")
 * @Entity
 */
class DDC3775PageMember
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC3775Page", inversedBy="pageMembers")
     * @JoinColumns({
     *   @JoinColumn(name="page_id", referencedColumnName="id")
     * })
     */
    private $page;

    /**
     * Set page
     *
     * @param DDC3775Page $page
     * @return DDC3775PageMember
     */
    public function setPage(DDC3775Page $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @ManyToOne(targetEntity="DDC3775User")
     * @JoinColumns({
     *   @JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * Set user
     *
     * @param DDC3775User $user
     * @return DDC3775PageMember
     */
    public function setUser(DDC3775User $user = null)
    {
        $this->user = $user;

        return $this;
    }
}

/**
 * PageRelation
 *
 * @Table(name="ddc3775_page_relation", uniqueConstraints={@UniqueConstraint(name="0", columns={"page_referenced_id", "page_creator_id"})})
 * @Entity
 */
class DDC3775PageRelation
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="DDC3775Page")
     * @JoinColumns({
     *   @JoinColumn(name="page_referenced_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $pageReferenced;

    /**
     * Set pageReferenced
     *
     * @param DDC3775Page $page
     * @return DDC3775PageMember
     */
    public function setPageReferenced(DDC3775Page $pageReferenced = null)
    {
        $this->pageReferenced = $pageReferenced;

        return $this;
    }

    /**
     * Get pageReferenced
     *
     * @return DDC3775Page
     */
    public function getPageReferenced()
    {
        return $this->pageCreator;
    }

    /**
     * @ManyToOne(targetEntity="DDC3775Page")
     * @JoinColumns({
     *   @JoinColumn(name="page_creator_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $pageCreator;

    /**
     * Set pageCreator
     *
     * @param DDC3775Page $page
     * @return DDC3775PageMember
     */
    public function setPageCreator(DDC3775Page $pageCreator = null)
    {
        $this->pageCreator = $pageCreator;

        return $this;
    }

    /**
     * Get pageCreator
     *
     * @return DDC3775Page
     */
    public function getPageCreator()
    {
        return $this->pageCreator;
    }
}

/**
 * User
 *
 * @Table(name="ddc3775_user")
 * @Entity
 */
class DDC3775User
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;
}