<?php
/*
 *  $Id: Lib.php 1916 2007-07-01 10:47:44Z meus $
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
 * Doctrine_Lib has not commonly used static functions, mostly for debugging purposes
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1916 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Lib
{
    /**
     * @param integer $state                the state of record
     * @see Doctrine_Record::STATE_* constants
     * @return string                       string representation of given state
     */
    public static function getRecordStateAsString($state)
    {
        switch ($state) {
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
        }
    }
    /**
     * returns a string representation of Doctrine_Record object
     * @param Doctrine_Record $record
     * @return string
     */
    public static function getRecordAsString(Doctrine_Record $record)
    {
        $r[] = '<pre>';
        $r[] = 'Component  : ' . $record->getTable()->getComponentName();
        $r[] = 'ID         : ' . $record->obtainIdentifier();
        $r[] = 'References : ' . count($record->getReferences());
        $r[] = 'State      : ' . Doctrine_Lib::getRecordStateAsString($record->getState());
        $r[] = 'OID        : ' . $record->getOID();
        $r[] = 'data       : ' . Doctrine::dump($record->getData(), false);
        $r[] = '</pre>';
        return implode("\n",$r)."<br />";
    }
    /**
     * Return an collection of records as XML. 
     * 
     * @see getRecordAsXml for options to set in the record class to control this.
     *
     * @param Doctrine_Collection $collection
     * @param SimpleXMLElement $xml
     * @return string Xml as string 
     */

    public static function getCollectionAsXml(Doctrine_Collection $collection, SimpleXMLElement $incomming_xml = null){

        $collectionName = Doctrine_Lib::plurelize($collection->getTable()->tableName);
				if ( $collection->count != 0) {
					$record = $collection[0];
        	$xml_options = $record->option("xml");
					if ( isset($xml_options["collection_name"])) {
						$collectionName = $xml_options["collection_name"];
					}
				}

        if ( ! isset($incomming_xml)) {
						$new_xml_string = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?><" . $collectionName . "></" . $collectionName . ">";
            $xml = new SimpleXMLElement($new_xml_string);
        } else {
            $xml = $incomming_xml->addChild($collectionName);
        }
        foreach ($collection as $key => $record) {
            Doctrine_Lib::getRecordAsXml($record, $xml);
        }
        return $xml->asXML();
    }

    public static function plurelize($string){
        return $string . "s";
    }

    /**
     * Return a recrd as XML. 
     *
     * In order to control how this is done set the "xml" option in a record. 
     * This option is an array that has the keys "ignore_fields" and "include_relations". Both of these are arrays that list the name of fields/relations to include/process. 
     *
     * If you want to insert this xml as a part inside another xml send a 
     * SimpleXMLElement to the function. Because of the nature of SimpleXML the 
     * content you add to this element will be avilable after the function is 
     * complete.
     *
     * @param Doctrine_Record $record
     * @param SimpleXMLElement $xml
     * @return string Xml as string
     */
    public static function getRecordAsXml(Doctrine_Record $record, SimpleXMlElement $incomming_xml = NULL)
    {
        $recordname = $record->getTable()->tableName;
        if ( !isset($incomming_xml)) {
						$new_xml_string = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?><" . $recordname . "></" . $recordname . ">";
            $xml = new SimpleXMLElement($new_xml_string);
        }else{
            $xml = $incomming_xml->addChild($recordname);
        }
				foreach($record->obtainIdentifier() as $pk_field => $pk_value){
					$xml->addChild($pk_field,$pk_value); 
				}
        $xml_options = $record->option("xml");
				if ( isset($xml_options["record_name"])) {
					$recordname = $xml_options["record_name"];
				}
        foreach ($record->getData() as $field => $value) {
            if ((isset($xml_options["ignore_fields"]) && !in_array($field, $xml_options["ignore_fields"])) || !isset($xml_options["ignore_fields"])) {
                if ($value instanceOf Doctrine_Null) {
                    $xml->addChild($field);
                } else {	
                    $xml->addChild($field, $value);
                }
            }
        }
        if (!isset($xml_options["include_relations"])) {
            return $xml->asXML();
        }
        $relations = $record->getTable()->getRelations();
        foreach ($relations as $name => $relation) {
            if (in_array($name, $xml_options["include_relations"])) {
                $relation_type = $relation->getType();
                $related_records = $record->get($name);
                if ($relation_type == Doctrine_Relation::ONE && $related_records instanceOf Doctrine_Record) {
                    Doctrine_Lib::getRecordAsXml($related_records, $xml);
                } else {
                    Doctrine_Lib::getCollectionAsXml($related_records, $xml);
                }
            }
        }
        return $xml->asXML();
    }


    /**
     * getStateAsString
     * returns a given connection state as string
     * @param integer $state        connection state
     */
    public static function getConnectionStateAsString($state)
    {
        switch ($state) {
        case Doctrine_Transaction::STATE_SLEEP:
            return "open";
            break;
        case Doctrine_Transaction::STATE_BUSY:
            return "busy";
            break;
        case Doctrine_Transaction::STATE_ACTIVE:
            return "active";
            break;
        }
    }
    /**
     * returns a string representation of Doctrine_Connection object
     * @param Doctrine_Connection $connection
     * @return string
     */
    public static function getConnectionAsString(Doctrine_Connection $connection)
    {
        $r[] = '<pre>';
        $r[] = 'Doctrine_Connection object';
        $r[] = 'State               : ' . Doctrine_Lib::getConnectionStateAsString($connection->transaction->getState());
        $r[] = 'Open Transactions   : ' . $connection->transaction->getTransactionLevel();
        $r[] = 'Table in memory     : ' . $connection->count();
        $r[] = 'Driver name         : ' . $connection->getAttribute(Doctrine::ATTR_DRIVER_NAME);

        $r[] = "</pre>";
        return implode("\n",$r)."<br>";
    }
    /**
     * returns a string representation of Doctrine_Table object
     * @param Doctrine_Table $table
     * @return string
     */
    public static function getTableAsString(Doctrine_Table $table)
    {
        $r[] = "<pre>";
        $r[] = "Component   : ".$table->getComponentName();
        $r[] = "Table       : ".$table->getTableName();
        $r[] = "</pre>";
        return implode("\n",$r)."<br>";
    }
    /**
     * @return string
     */
    public static function formatSql($sql)
    {
        $e = explode("\n",$sql);
        $color = "367FAC";
        $l = $sql;
        $l = str_replace("SELECT ", "<font color='$color'><b>SELECT </b></font><br \>  ",$l);
        $l = str_replace("FROM ", "<font color='$color'><b>FROM </b></font><br \>",$l);
        $l = str_replace(" LEFT JOIN ", "<br \><font color='$color'><b> LEFT JOIN </b></font>",$l);
        $l = str_replace(" INNER JOIN ", "<br \><font color='$color'><b> INNER JOIN </b></font>",$l);
        $l = str_replace(" WHERE ", "<br \><font color='$color'><b> WHERE </b></font>",$l);
        $l = str_replace(" GROUP BY ", "<br \><font color='$color'><b> GROUP BY </b></font>",$l);
        $l = str_replace(" HAVING ", "<br \><font color='$color'><b> HAVING </b></font>",$l);
        $l = str_replace(" AS ", "<font color='$color'><b> AS </b></font><br \>  ",$l);
        $l = str_replace(" ON ", "<font color='$color'><b> ON </b></font>",$l);
        $l = str_replace(" ORDER BY ", "<font color='$color'><b> ORDER BY </b></font><br \>",$l);
        $l = str_replace(" LIMIT ", "<font color='$color'><b> LIMIT </b></font><br \>",$l);
        $l = str_replace(" OFFSET ", "<font color='$color'><b> OFFSET </b></font><br \>",$l);
        $l = str_replace("  ", "<dd>",$l);

        return $l;
    }
    /**
     * returns a string representation of Doctrine_Collection object
     * @param Doctrine_Collection $collection
     * @return string
     */
    public static function getCollectionAsString(Doctrine_Collection $collection)
    {
        $r[] = "<pre>";
        $r[] = get_class($collection);
        $r[] = 'data : ' . Doctrine::dump($collection->getData(), false);
        //$r[] = 'snapshot : ' . Doctrine::dump($collection->getSnapshot());

        $r[] = "</pre>";
        return implode("\n",$r);
    }
}
