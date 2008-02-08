<?php
class Doctrine_Record_Filter_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { }

    public function testValueWrapper() {
        $e = new RecordFilterTest;
        $e->name = "something";
        $e->password = "123";


        $this->assertEqual($e->get('name'), 'SOMETHING');
        // test repeated calls
        $this->assertEqual($e->get('name'), 'SOMETHING');
        $this->assertEqual($e->id, null);
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $e->save();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $this->connection->clear();

        $e->refresh();

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

        $this->connection->clear();

        $e = $e->getTable()->find($e->id);

        $this->assertEqual($e->id, 1);
        $this->assertEqual($e->name, 'SOMETHING');
        $this->assertEqual($e->rawGet('name'), 'something');
        $this->assertEqual($e->password, '202cb962ac59075b964b07152d234b70');

    }
}
