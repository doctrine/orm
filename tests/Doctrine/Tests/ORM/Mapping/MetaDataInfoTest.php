namespace Doctrine\Tests\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @group DDC-3944
 */
class ClassMetadataBuilderTest extends \Doctrine\Tests\OrmTestCase
{

    /**
     * @group DDC-3944
     */
    public function testRemoveLifecycleCallbackNotFound()
    {
        $productMd = new ClassMetadata('Product');
        $productMd->initializeReflection(new RuntimeReflectionService());
        
        $callBacks = $productMd->getLifecycleCallbacks('PrePersist');
        
        $this->assertTrue( in_array('setUpdatedAtValue', $callBacks) );
        $product->removeLifecycleCallback('setUpdatedAtValue', 'PrePersist');
        $this->assertFalse( in_array('setUpdatedAtValue', $callBacks) );
    }
    
    
    /**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class Product
{
    protected $id;
    protected $name;
    protacted $updatedAt;
    
  /**
   * @ORM\PrePersist
   */
  public function setUpdatedAtValue()
  {
      $this->updatedAt = new \DateTime();
  }
}


    
    
