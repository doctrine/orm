<?php
class MyView extends Doctrine_View { }

class Doctrine_ViewTestCase extends Doctrine_UnitTestCase {
    public function testCreateView() {
        $query = new Doctrine_Query($this->session);
        $query->from('User');

        $view = new MyView($query);

        $this->assertEqual($view->getName(), 'MyView');
        $this->assertEqual($view->getQuery(), $query);
        $this->assertEqual($view, $query->getView());
        $this->assertTrue($view->getDBH() instanceof PDO);

        $success = true;

        try {
            $view->create();
        } catch(Exception $e) {
            $success = false;
        }
        $this->assertTrue($success);

        $users = $view->execute();
        $count = $this->dbh->count();
        $this->assertTrue($users instanceof Doctrine_Collection);
        $this->assertEqual($users->count(), 8);
        $this->assertEqual($users[0]->name, 'zYne');
        $this->assertEqual($users[0]->getState(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($count, $this->dbh->count());

        $success = true;
        try {
            $view->drop();
        } catch(Exception $e) {
            $success = false;
        }
        $this->assertTrue($success);


    }
}
?>
