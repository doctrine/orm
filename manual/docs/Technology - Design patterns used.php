GoF [Gang Of Four] design patterns used:
<br \>
<li \><a href="http://www.dofactory.com/Patterns/PatternSingleton.aspx">Singleton</a><br \>
<dd>For forcing only one instance of Doctrine_Manager

<li \><a href="http://www.dofactory.com/Patterns/PatternComposite.aspx">Composite</a><br \>
<dd>For leveled configuration

<li \><a href="http://www.dofactory.com/Patterns/PatternFactory.aspx">Factory</a><br \>
<dd>For connection driver loading and many other things

<li \><a href="http://www.dofactory.com/Patterns/PatternObserver.aspx">Observer</a><br \>
<dd>For event listening

<li \><a href="http://www.dofactory.com/Patterns/PatternFlyweight.aspx">Flyweight</a><br \>
<dd>For efficient usage of validators

<li \><a href="http://www.dofactory.com/Patterns/PatternFlyweight.aspx">Iterator</a><br \>
<dd>For iterating through components [Tables, Connections, Records etc.]

<li \><a href="http://www.dofactory.com/Patterns/PatternState.aspx">State</a><br \>
<dd>For state-wise connections

<li \><a href="http://www.dofactory.com/Patterns/PatternStrategy.aspx">Strategy</a><br \>
<dd>For algorithm strategies
<br \><br \>
Enterprise application design patterns used:
<br \>
<li \><a href="http://www.martinfowler.com/eaaCatalog/activeRecord.html">Active Record</a><br \>
<dd>Doctrine is an implementation of this pattern
<li \><a href="http://www.martinfowler.com/eaaCatalog/unitOfWork.html">UnitOfWork</a><br \>
<dd>For maintaining a list of objects affected in a transaction
<li \><a href="http://www.martinfowler.com/eaaCatalog/identityField.html">Identity Field</a><br \>
<dd>For maintaining the identity between record and database row
<li \><a href="http://www.martinfowler.com/eaaCatalog/metadataMapping.html">Metadata Mapping</a><br \>
<dd>For Doctrine DataDict
<li \><a href="http://www.martinfowler.com/eaaCatalog/dependentMapping.html">Dependent Mapping</a><br \>
<dd>For mapping in general, since all records extend Doctrine_Record which performs all mappings
<li \><a href="http://www.martinfowler.com/eaaCatalog/foreignKeyMapping.html">Foreign Key Mapping</a><br \>
<dd>For one-to-one, one-to-many and many-to-one relationships
<li \><a href="http://www.martinfowler.com/eaaCatalog/associationTableMapping.html">Association Table Mapping</a><br \>
<dd>For association table mapping (most commonly many-to-many relationships)
<li \><a href="http://www.martinfowler.com/eaaCatalog/lazyLoad.html">Lazy Load</a><br \>
<dd>For lazy loading of objects and object properties
<li \><a href="http://www.martinfowler.com/eaaCatalog/queryObject.html">Query Object</a><br \>
<dd>DQL API is actually an extension to the basic idea of Query Object pattern

