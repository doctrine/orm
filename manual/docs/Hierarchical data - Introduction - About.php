<p>Most users at one time or another have dealt with hierarchical data in a SQL database and no doubt learned that the management of hierarchical data is not what a relational database is intended for. The tables of a relational database are not hierarchical (like XML), but are simply a flat list. Hierarchical data has a parent-child relationship that is not naturally represented in a relational database table.</p>

<p>For our purposes, hierarchical data is a collection of data where each item has a single parent and zero or more children (with the exception of the root item, which has no parent). Hierarchical data can be found in a variety of database applications, including forum and mailing list threads, business organization charts, content management categories, and product categories.</p>

<p>In a hierarchical data model, data is organized into a tree-like structure. The tree structure allows repeating information using parent/child relationships. For an explanation of the tree data structure, see here <a href="http://en.wikipedia.org/wiki/Tree_data_structure" >http://en.wikipedia.org/wiki/Tree_data_structure</a></p>

<p>There are three major approaches to managing tree structures in relational databases, these are:</p>

<ul>
	<li>the adjacency list model</li>
	<li>the nested set model (otherwise known as the modified pre-order tree traversal algorithm)</li> 
	<li>materialized path model</li> 
</ul>

<p>These are explained in more detail in the following chapters, or see<br />
<a href="http://www.dbazine.com/oracle/or-articles/tropashko4" >http://www.dbazine.com/oracle/or-articles/tropashko4</a>, <a href="http://dev.mysql.com/tech-resources/articles/hierarchical-data.html" >http://dev.mysql.com/tech-resources/articles/hierarchical-data.html</a></p>