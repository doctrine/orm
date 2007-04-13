GoF [Gang Of Four] design patterns used:


 * : [http://www.dofactory.com/Patterns/PatternSingleton.aspx Singleton] : For forcing only one instance of Doctrine_Manager

 * : [http://www.dofactory.com/Patterns/PatternComposite.aspx Composite] : For leveled configuration

 * : [http://www.dofactory.com/Patterns/PatternFactory.aspx Factory] : For connection driver loading and many other things

 * : [http://www.dofactory.com/Patterns/PatternObserver.aspx Observer] : For event listening

 * : [http://www.dofactory.com/Patterns/PatternFlyweight.aspx Flyweight] : For efficient usage of validators

 * : [http://www.dofactory.com/Patterns/PatternFlyweight.aspx Iterator] : For iterating through components [Tables, Connections, Records etc.]

 * : [http://www.dofactory.com/Patterns/PatternState.aspx State] : For state-wise connections

 * : [http://www.dofactory.com/Patterns/PatternStrategy.aspx Strategy] : For algorithm strategies



Enterprise application design patterns used:


 * : [http://www.martinfowler.com/eaaCatalog/activeRecord.html Active Record] : Doctrine is an implementation of this pattern
 * : [http://www.martinfowler.com/eaaCatalog/unitOfWork.html UnitOfWork] : For maintaining a list of objects affected in a transaction
 * : [http://www.martinfowler.com/eaaCatalog/identityField.html Identity Field] : For maintaining the identity between record and database row
 * : [http://www.martinfowler.com/eaaCatalog/metadataMapping.html Metadata Mapping] : For Doctrine DataDict
 * : [http://www.martinfowler.com/eaaCatalog/dependentMapping.html Dependent Mapping] : For mapping in general, since all records extend Doctrine_Record which performs all mappings
 * : [http://www.martinfowler.com/eaaCatalog/foreignKeyMapping.html Foreign Key Mapping] : For one-to-one, one-to-many and many-to-one relationships
 * : [http://www.martinfowler.com/eaaCatalog/associationTableMapping.html Association Table Mapping] : For association table mapping (most commonly many-to-many relationships)
 * : [http://www.martinfowler.com/eaaCatalog/lazyLoad.html Lazy Load] : For lazy loading of objects and object properties
 * : [http://www.martinfowler.com/eaaCatalog/queryObject.html Query Object] : DQL API is actually an extension to the basic idea of Query Object pattern

