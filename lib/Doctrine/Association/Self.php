<?php
/*
 *  $Id$
 *
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
 * <http://www.phpdoctrine.com>.
 */
 
class Doctrine_Association_Self extends Doctrine_Association {
    /**
     * getRelationDql
     *
     * @param integer $count
     * @return string
     */
    public function getRelationDql($count, $context = 'record') {    
        switch($context):
            case "record":
                $sub    = "SELECT ".$this->foreign.
                          " FROM ".$this->associationTable->getTableName().
                          " WHERE ".$this->local.
                          " = ?";
                $sub2   = "SELECT ".$this->local.
                          " FROM ".$this->associationTable->getTableName().
                          " WHERE ".$this->foreign.
                          " = ?";


                $dql  = "FROM ".$this->table->getComponentName();
                $dql .= ".".$this->associationTable->getComponentName();
                $dql .= " WHERE ".$this->table->getComponentName().".".$this->table->getIdentifier()." IN ($sub)";
                $dql .= " || ".$this->table->getComponentName().".".$this->table->getIdentifier()." IN ($sub2)";
            break;
            case "collection":
                $sub  = substr(str_repeat("?, ", $count),0,-2);
                $dql  = "FROM ".$this->associationTable->getComponentName().".".$this->table->getComponentName();
                $dql .= " WHERE ".$this->associationTable->getComponentName().".".$this->local." IN ($sub)";
        endswitch;

        return $dql;
    }
}
?>
