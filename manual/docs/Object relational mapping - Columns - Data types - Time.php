The time data type may represent the time of a given moment of the day. DBMS independent representation of the time of the day is also accomplished by using text strings formatted according to the ISO-8601 standard. 
<br \><br \>
The format defined by the ISO-8601 standard for the time of the day is HH:MI:SS where HH is the number of hour the day from 00 to 23 and MI and SS are respectively the number of the minute and of the second from 00 to 59. Hours, minutes and seconds numbered below 10 should be padded on the left with 0. 
<br \><br \>
Some DBMS have native support for time of the day formats, but for others the DBMS driver may have to represent them as integers or text values. In any case, it is always possible to make comparisons between time values as well sort query results by fields of this type. 


<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('timetest', 'time');
    }
}
</code>
