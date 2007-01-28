<?php
    // $Id: real_sites_test.php,v 1.16 2004/12/05 21:12:33 lastcraft Exp $
    
    require_once(dirname(__FILE__) . '/../web_tester.php');

    class LiveSitesTestCase extends WebTestCase {
        
        function testLastCraft() {
            $this->assertTrue($this->get('http://www.lastcraft.com'));
            $this->assertResponse(array(200));
            $this->assertMime(array('text/html'));
            $this->clickLink('About');
            $this->assertTitle('About Last Craft');
        }
        
        function testSourceforge() {
            $this->assertTrue($this->get('http://sourceforge.net/'));
            $this->setField('words', 'simpletest');
            $this->assertTrue($this->clickImageByName('imageField'));
            $this->assertTitle('SourceForge.net: Search');
            $this->assertTrue($this->clickLink('SimpleTest'));
            $this->clickLink('statistics');
            $this->assertWantedPattern('/Statistics for the past 7 days/');
            $this->assertTrue($this->setField('report', 'Monthly'));
            $this->clickSubmit('Change Stats View');
            $this->assertWantedPattern('/Statistics for the past \d+ months/');
        }
    }
?>