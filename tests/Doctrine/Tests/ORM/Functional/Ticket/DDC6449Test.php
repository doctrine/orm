<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-6449
 */
class DDC6449Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC6449AccountNumber::class),
            $this->_em->getClassMetadata(DDC6449PurchaseOrderItemsSuggest::class)
            ]
        );



    }

    public function testObjectCreation()
    {

      $suggest = new DDC6449PurchaseOrderItemsSuggest();
      $this->_em->persist($suggest);
      $suggest->qbpurchaseorderitemsuggestid = 25;
      $suggest->projectid = 2;
      $suggest->qbaccountnumberid = 99;

      $this->assertEquals($suggest->projectid, 2);
      $this->assertEquals($suggest->qbaccountnumberid, 99);
      $this->assertEquals($suggest->qbpurchaseorderitemsuggestid, 25);

      $e = null;

      try {
        $this->_em->flush();
      } catch (\Exception $ex) {
        $e = $ex;
        echo $ex->getTraceAsString();
      }

      $this->assertEquals($e, null);

      $accountnumber = new DDC6449AccountNumber();
      $accountnumber->projectid = 2;
      $accountnumber->qbaccountnumberid = 99;
      $this->_em->persist($accountnumber);
      $accountnumber->accountnumber = 'test';

      $this->assertEquals($accountnumber->projectid, 2);
      $this->assertEquals($accountnumber->qbaccountnumberid, 99);
      $this->assertEquals($accountnumber->accountnumber, 'test');

      $e = null;

      try {
        $this->_em->flush();
      } catch (\Exception $ex) {
        $e = $ex;
        echo $ex->getTraceAsString();
      }


      $this->_em->clear();
    }

}

/**
 * @Table(name="PurchaseOrderItemsSuggest")
 * @Entity
 */
class DDC6449PurchaseOrderItemsSuggest
{
    /**
     * @Id
     * @Column(name="PurchaseOrderItemSuggestID", type="bigint", options={"unsigned":true})
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $purchaseorderitemsuggestid;
    /**
     * @Column(name="ProjectID", type="bigint", options={"unsigned":true})
     */
    protected $projectid;
    /**
     * @Column(name="QBPurchaseOrderItemSuggestID", type="bigint", options={"unsigned":true})
     */
    protected $qbpurchaseorderitemsuggestid;
    /**
     * @Column(name="QBAccountNumberID", type="bigint", options={"unsigned":true})
     */
    protected $qbaccountnumberid;
    /**
     * @ManyToOne(targetEntity="DDC6449AccountNumber", fetch="LAZY", cascade = {})
     * @JoinColumns({
          @JoinColumn(name="ProjectID", referencedColumnName="ProjectID"),
          @JoinColumn(name="QBPurchaseOrderItemSuggestID", referencedColumnName="QBAccountNumberID")
        })
     */
    protected $qbaccount;

    /**
     * Set qbaccount
     *
     * @param string $qbaccount
     *
     * @return PurchaseOrderPayment
     */
    public function setQbaccount($qbaccount)
    {
        $this->qbaccount = $qbaccount;

        return $this;
    }

    /**
     * Get qbaccount
     *
     * @return string
     */
    public function getQbaccount()
    {
        return $this->qbaccount;
    }
    /**
     * Set qbaccountnumberid
     *
     * @param string $qbaccountnumberid
     *
     * @return PurchaseOrderPayment
     */
    public function setQbaccountnumberid($qbaccountnumberid)
    {
        $this->qbaccountnumberid = $qbaccountnumberid;

        return $this;
    }

    /**
     * Get qbaccountnumberid
     *
     * @return string
     */
    public function getQbaccountnumberid()
    {
        return $this->qbaccountnumberid;
    }

    /**
     * Set qbpurchaseorderitemsuggestid
     *
     * @param string $qbpurchaseorderitemsuggestid
     *
     * @return PurchaseOrderPayment
     */
    public function setQbpurchaseorderitemsuggestid($qbpurchaseorderitemsuggestid)
    {
        $this->qbpurchaseorderitemsuggestid = $qbpurchaseorderitemsuggestid;

        return $this;
    }

    /**
     * Get qbpurchaseorderitemsuggestid
     *
     * @return string
     */
    public function getQbpurchaseorderitemsuggestid()
    {
        return $this->qbpurchaseorderitemsuggestid;
    }


    function __get($p) {
        $m = "get$p";
        if(!method_exists($this, $m)) return null;
        return $this->$m();
    }



    function __set($p, $v) {
        $m = "set$p";
        if(method_exists($this, $m)) $this->$m($v);
    }


    /**
     * Get purchaseorderitemsuggestid
     *
     * @return integer
     */
    public function getPurchaseorderitemsuggestid()
    {
        return $this->purchaseorderitemsuggestid;
    }

    /**
     * Set projectid
     *
     * @param integer $projectid
     *
     * @return PurchaseOrderItemsSuggest
     */
    public function setProjectid($projectid)
    {
        $this->projectid = $projectid;

        return $this;
    }

    /**
     * Get projectid
     *
     * @return integer
     */
    public function getProjectid()
    {
        return $this->projectid;
    }


}

/**
 * @Table(name="AccountNumber")
 * @Entity
 */
class DDC6449AccountNumber
{
    /**
     * @Id
     * @Column(name="QBAccountNumberID", type="bigint", options={"unsigned":true})
     * @GeneratedValue(strategy="NONE")
     */
    protected $qbaccountnumberid;
    /**
     * @Id
     * @Column(name="ProjectID", type="bigint", options={"unsigned":true})
     * @GeneratedValue(strategy="NONE")
     */
    protected $projectid;
    /**
     * @Column(name="AccountNumber", type="string", length=255)
     */
    protected $accountnumber;



    /**
     * Set qbaccountnumberid
     *
     * @param integer $qbaccountnumberid
     *
     * @return AccountNumber
     */
    public function setQbaccountnumberid($qbaccountnumberid)
    {
        $this->qbaccountnumberid = $qbaccountnumberid;

        return $this;
    }

    /**
     * Get qbaccountnumberid
     *
     * @return integer
     */
    public function getQbaccountnumberid()
    {
        return $this->qbaccountnumberid;
    }


    function __get($p) {
        $m = "get$p";
        if(!method_exists($this, $m)) return null;
        return $this->$m();
    }



    function __set($p, $v) {
        $m = "set$p";
        if(method_exists($this, $m)) $this->$m($v);
    }


    /**
     * Set projectid
     *
     * @param integer $projectid
     *
     * @return AccountNumber
     */
    public function setProjectid($projectid)
    {
        $this->projectid = $projectid;

        return $this;
    }

    /**
     * Get projectid
     *
     * @return integer
     */
    public function getProjectid()
    {
        return $this->projectid;
    }

    /**
     * Set accountnumber
     *
     * @param string $accountnumber
     *
     * @return AccountNumber
     */
    public function setAccountnumber($accountnumber)
    {
        $this->accountnumber = $accountnumber;

        return $this;
    }

    /**
     * Get accountnumber
     *
     * @return string
     */
    public function getAccountnumber()
    {
        return $this->accountnumber;
    }
}

