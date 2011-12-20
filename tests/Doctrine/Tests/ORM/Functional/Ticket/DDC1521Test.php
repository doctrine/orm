<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * @group DDC-1521
 */
class DDC1521Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('navigation');

        parent::setUp();

        $config = $this->_em->getConfiguration();
        $config->addCustomNumericFunction('TEST', __NAMESPACE__ . '\TestFunction');
    }

    public function testIssue()
    {
        $dql    = 'SELECT TEST(p.lat, p.long, :lat, :lng) FROM Doctrine\Tests\Models\Navigation\NavPointOfInterest p WHERE p.name = :name';
        $params = array('lat' => 1, 'lng' => 2, 'name' => 'My restaurant');

        $query  = $this->_em->createQuery($dql)->setParameters($params);

        $this->assertEquals('SELECT ((12733.129728 + (n0_.nav_lat - ?) + (n0_.nav_long - ?)) * ((n0_.nav_lat - ?) / (12733.129728 * n0_.nav_long - ?))) AS sclr0 FROM navigation_pois n0_ WHERE n0_.name = ?', $query->getSQL());

        $result = $query->getScalarResult();

        $this->assertEquals(0, count($result));
    }
}

class TestFunction extends FunctionNode
{
	protected $fromLat;
	protected $fromLng;
	protected $toLat;
	protected $toLng;

	public function parse(\Doctrine\ORM\Query\Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);

		$this->fromLat = $parser->ArithmeticPrimary();
		$parser->match(Lexer::T_COMMA);

		$this->fromLng = $parser->ArithmeticPrimary();
		$parser->match(Lexer::T_COMMA);

		$this->toLat = $parser->ArithmeticPrimary();
		$parser->match(Lexer::T_COMMA);

		$this->toLng = $parser->ArithmeticPrimary();

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}

	public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
	{
		$fromLat = $this->fromLat->dispatch($sqlWalker);
		$fromLng = $this->fromLng->dispatch($sqlWalker);
		$toLat   = $this->toLat->dispatch($sqlWalker);
		$toLng   = $this->toLng->dispatch($sqlWalker);

		$earthDiameterInKM = 1.609344 * 3956 * 2;

		return "(($earthDiameterInKM + ($fromLat - $toLat) + ($fromLng - $toLng)) * (($fromLat - $toLat) / ($earthDiameterInKM * $fromLng - $toLng)))";
	}
}