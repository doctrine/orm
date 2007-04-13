The date data type may represent dates with year, month and day. DBMS independent representation of dates is accomplished by using text strings formatted according to the IS0-8601 standard. 



The format defined by the ISO-8601 standard for dates is YYYY-MM-DD where YYYY is the number of the year (Gregorian calendar), MM is the number of the month from 01 to 12 and DD is the number of the day from 01 to 31. Months or days numbered below 10 should be padded on the left with 0. 



Some DBMS have native support for date formats, but for others the DBMS driver may have to represent them as integers or text values. In any case, it is always possible to make comparisons between date values as well sort query results by fields of this type. 




<code type="php">
class Test extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('datetest', 'date');
    }
}
</code>
