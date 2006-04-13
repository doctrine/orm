<?php
abstract class Doctrine_Component {
    /**
     * setTableName
     * @param string $name              table name
     * @return void
     */
    final public function setTableName($name) {
        $this->getComponent()->setTableName($name);
    }
    /**
     * setInheritanceMap
     * @param array $inheritanceMap
     * @return void
     */
    final public function setInheritanceMap(array $inheritanceMap) {
        $this->getComponent()->setInheritanceMap($inheritanceMap);
    }
    /**
     * setAttribute
     * @param integer $attribute
     * @param mixed $value
     * @see Doctrine::ATTR_* constants
     * @return void
     */
    final public function setAttribute($attribute,$value) {
        $this->getComponent()->setAttribute($attribute,$value);
    }
    /**
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function ownsOne($componentName,$foreignKey) {
        $this->getComponent()->bind($componentName,$foreignKey,Doctrine_Table::ONE_COMPOSITE);
    }
    /**
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function ownsMany($componentName,$foreignKey) {
        $this->getComponent()->bind($componentName,$foreignKey,Doctrine_Table::MANY_COMPOSITE);
    }
    /**
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function hasOne($componentName,$foreignKey) {
        $this->getComponent()->bind($componentName,$foreignKey,Doctrine_Table::ONE_AGGREGATE);
    }
    /**
     * @param string $objTableName
     * @param string $fkField
     * @return void
     */
    final public function hasMany($componentName,$foreignKey) {
        $this->getComponent()->bind($componentName,$foreignKey,Doctrine_Table::MANY_AGGREGATE);
    }

    abstract public function getComponent();
}
?>
