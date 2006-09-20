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

/**
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * 
 * Doctrine_Lib has not commonly used static functions, mostly for debugging purposes
 */
class Doctrine_Lib {
    /**
     * @param integer $state                the state of record
     * @see Doctrine_Record::STATE_* constants
     * @return string                       string representation of given state
     */
    public static function getRecordStateAsString($state) {
        switch($state):
            case Doctrine_Record::STATE_PROXY:
                return "proxy";
            break;
            case Doctrine_Record::STATE_CLEAN:
                return "persistent clean";
            break;
            case Doctrine_Record::STATE_DIRTY:
                return "persistent dirty";
            break;
            case Doctrine_Record::STATE_TDIRTY:
                return "transient dirty";
            break;
            case Doctrine_Record::STATE_TCLEAN:
                return "transient clean";
            break;
        endswitch;
    }
    /**
     * returns a string representation of Doctrine_Record object
     * @param Doctrine_Record $record
     * @return string
     */
    public static function getRecordAsString(Doctrine_Record $record) {
        $r[] = "<pre>";
        $r[] = "Component  : ".$record->getTable()->getComponentName();
        $r[] = "ID         : ".$record->obtainIdentifier();
        $r[] = "References : ".count($record->getReferences());
        $r[] = "State      : ".Doctrine_Lib::getRecordStateAsString($record->getState());
        $r[] = "OID        : ".$record->getOID();
        $r[] = "</pre>";
        return implode("\n",$r)."<br />";
    }
    /**
     * getStateAsString
     * returns a given connection state as string
     * @param integer $state        connection state
     */
    public static function getConnectionStateAsString($state) {
        switch($state):
            case Doctrine_Transaction::STATE_OPEN:
                return "open";
            break;
            case Doctrine_Transaction::STATE_CLOSED:
                return "closed";
            break;
            case Doctrine_Transaction::STATE_BUSY:
                return "busy";
            break;
            case Doctrine_Transaction::STATE_ACTIVE:
                return "active";
            break;
        endswitch;
    }
    /**
     * returns a string representation of Doctrine_Connection object
     * @param Doctrine_Connection $connection
     * @return string
     */
    public static function getConnectionAsString(Doctrine_Connection $connection) {
        $r[] = "<pre>";
        $r[] = "Doctrine_Connection object";
        $r[] = "State               : ".Doctrine_Lib::getConnectionStateAsString($connection->getState());
        $r[] = "Open Transactions   : ".$connection->getTransactionLevel();
        $r[] = "Open Factories      : ".$connection->count();
        $sum = 0;
        $rsum = 0;
        $csum = 0;
        foreach($connection->getTables() as $objTable) {
            if($objTable->getCache() instanceof Doctrine_Cache_File) {
                $sum += array_sum($objTable->getCache()->getStats());
                $rsum += $objTable->getRepository()->count();
                $csum += $objTable->getCache()->count();
            }
        }
        $r[] = "Cache Hits          : ".$sum." hits ";
        $r[] = "Cache               : ".$csum." objects ";

        $r[] = "Repositories        : ".$rsum." objects ";
        $queries = false;
        if($connection->getDBH() instanceof Doctrine_DB) {
            $handler = "Doctrine Database Handler";
            $queries = count($connection->getDBH()->getQueries());
            $sum     = array_sum($connection->getDBH()->getExecTimes());
        } elseif($connection->getDBH() instanceof PDO) {
            $handler = "PHP Native PDO Driver";
        } else
            $handler = "Unknown Database Handler";

        $r[] = "DB Handler          : ".$handler;
        if($queries) {
            $r[] = "Executed Queries    : ".$queries;
            $r[] = "Sum of Exec Times   : ".$sum;
        }

        $r[] = "</pre>";
        return implode("\n",$r)."<br>";
    }
    /**
     * returns a string representation of Doctrine_Table object
     * @param Doctrine_Table $table
     * @return string
     */
    public static function getTableAsString(Doctrine_Table $table) {
        $r[] = "<pre>";
        $r[] = "Component   : ".$table->getComponentName();
        $r[] = "Table       : ".$table->getTableName();
        $r[] = "Repository  : ".$table->getRepository()->count()." objects";
        if($table->getCache() instanceof Doctrine_Cache_File) {
            $r[] = "Cache       : ".$table->getCache()->count()." objects";
            $r[] = "Cache hits  : ".array_sum($table->getCache()->getStats())." hits";
        }
        $r[] = "</pre>";
        return implode("\n",$r)."<br>";
    }
    /**
     * @return string
     */
    public static function formatSql($sql) {
        $e = explode("\n",$sql);
        $color = "367FAC";
        $l = $sql;
        $l = str_replace("SELECT","<font color='$color'><b>SELECT</b></font><br \>  ",$l);
        $l = str_replace("FROM","<font color='$color'><b>FROM</b></font><br \>",$l);
        $l = str_replace("LEFT JOIN","<br \><font color='$color'><b>LEFT JOIN</b></font>",$l);
        $l = str_replace("INNER JOIN","<br \><font color='$color'><b>INNER JOIN</b></font>",$l);
        $l = str_replace("WHERE","<br \><font color='$color'><b>WHERE</b></font>",$l);
        $l = str_replace("GROUP BY","<br \><font color='$color'><b>GROUP BY</b></font>",$l);
        $l = str_replace("HAVING","<br \><font color='$color'><b>HAVING</b></font>",$l);
        $l = str_replace("AS","<font color='$color'><b>AS</b></font><br \>  ",$l);
        $l = str_replace("ON","<font color='$color'><b>ON</b></font>",$l);
        $l = str_replace("ORDER BY","<font color='$color'><b>ORDER BY</b></font><br \>",$l);
        $l = str_replace("LIMIT","<font color='$color'><b>LIMIT</b></font><br \>",$l);
        $l = str_replace("OFFSET","<font color='$color'><b>OFFSET</b></font><br \>",$l);
        $l = str_replace("  ","<dd>",$l);
        
        return $l;
    }
    /**
     * returns a string representation of Doctrine_Collection object
     * @param Doctrine_Collection $collection
     * @return string
     */
    public static function getCollectionAsString(Doctrine_Collection $collection) {
        $r[] = "<pre>";
        $r[] = get_class($collection);

        foreach($collection as $key => $record) {
            $r[] = "Key : ".$key." ID : ".$record->obtainIdentifier();
        }
        $r[] = "</pre>";
        return implode("\n",$r);
    }
}

