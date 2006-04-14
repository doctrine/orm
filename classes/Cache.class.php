<?php
interface iDoctrine_Cache {
    public function store(Doctrine_Record $record);
    public function clean();
    public function delete($id);
    public function fetch($id);
    public function exists($id);
}
class Doctrine_Cache implements iDoctrine_Cache {
    /**
     * implemented by child classes
     * @param Doctrine_Record $record
     * @return boolean
     */
    public function store(Doctrine_Record $record) {
        return false;
    }
    /**
     * implemented by child classes
     * @return boolean
     */
    public function clean() {
        return false;
    }
    /**
     * implemented by child classes
     * @return boolean
     */
    public function delete($id) {
        return false;
    }
    /**
     * implemented by child classes
     * @throws InvalidKeyException
     * @return Doctrine_Record                      found Data Access Object
     */
    public function fetch($id) {
        throw new InvalidKeyException();
    }
    /**
     * implemented by child classes
     * @param integer $id
     * @return boolean
     */
    public function exists($id) {
        return false;
    }
    /**
     * implemented by child classes
     */
    public function deleteMultiple() {
        return 0;
    }
    /**
     * implemented by child classes
     * @return integer
     */
    public function deleteAll() {
        return 0;
    }

}
?>
