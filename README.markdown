# Doctrine 2 ORM

Master: [![Build Status](https://secure.travis-ci.org/doctrine/doctrine2.png?branch=master)](http://travis-ci.org/doctrine/doctrine2)
2.2: [![Build Status](https://secure.travis-ci.org/doctrine/doctrine2.png?branch=2.2)](http://travis-ci.org/doctrine/doctrine2)
2.1: [![Build Status](https://secure.travis-ci.org/doctrine/doctrine2.png?branch=2.1.x)](http://travis-ci.org/doctrine/doctrine2)

Doctrine 2 is an object-relational mapper (ORM) for PHP 5.3.2+ that provides transparent persistence
for PHP objects. It sits on top of a powerful database abstraction layer (DBAL). One of its key features
is the option to write database queries in a proprietary object oriented SQL dialect called Doctrine Query Language (DQL),
inspired by Hibernates HQL. This provides developers with a powerful alternative to SQL that maintains flexibility
without requiring unnecessary code duplication.

## More resources:

* [Website](http://www.doctrine-project.org)
* [Documentation](http://www.doctrine-project.org/projects/orm/2.0/docs/reference/introduction/en)
* [Issue Tracker](http://www.doctrine-project.org/jira/browse/DDC)
* [Downloads](http://github.com/doctrine/doctrine2/downloads)

## Some Possible Code Problems

lib\Doctrine\ORM\AbstractQuery.php (11 comments)
==================================================================
Line 207: Should the type for parameter "$type" not be "string|null" instead of "string"?
Line 297: Should the type for parameter "$rsm" not be "Query\ResultSetMapping" instead of "ResultSetMapping"?
Line 325: Should the type for parameter "$profile" not be "null|QueryCacheProfile" instead of "QueryCacheProfile"?
Line 354: Should the type for parameter "$profile" not be "null|QueryCacheProfile" instead of "QueryCacheProfile"?
Line 408: Should the type for parameter "$lifetime" not be "integer|null" instead of "integer"?
Line 409: Should the type for parameter "$resultCacheId" not be "string|null" instead of "string"?
Line 478: Should the return type not be "null" instead of "QueryCacheProfile"?
Line 570: Should the type for parameter "$hydrationMode" not be "integer|null" instead of "integer"?
Line 600: Should the type for parameter "$hydrationMode" not be "integer|null" instead of "integer"?
Line 677: Should the type for parameter "$hydrationMode" not be "integer|null" instead of "integer"?
Line 701: Should the type for parameter "$hydrationMode" not be "integer|null" instead of "integer"?


lib\Doctrine\ORM\Configuration.php (1 comments)
==================================================================
Line 324: Should the type for parameter "$rsm" not be "Query\ResultSetMapping" instead of "ResultSetMapping"?


lib\Doctrine\ORM\EntityManager.php (6 comments)
==================================================================
Line 349: Should the type for parameter "$entity" not be "object|null" instead of "object"?
Line 368: Should the type for parameter "$lockVersion" not be "integer|null" instead of "integer"?
Line 468: Should the type for parameter "$entityName" not be "string|null" instead of "string"?
Line 586: Should the return type not be "NoType" instead of "object"?
Line 600: Should the type for parameter "$lockVersion" not be "integer|null" instead of "integer"?
Line 781: Should the type for parameter "$eventManager" not be "null|EventManager" instead of "EventManager"?


lib\Doctrine\ORM\EntityRepository.php (1 comments)
==================================================================
Line 120: Should the type for parameter "$lockVersion" not be "integer|null" instead of "integer"?


lib\Doctrine\ORM\Event\OnClearEventArgs.php (2 comments)
==================================================================
Line 47: Should the type for parameter "$entityClass" not be "string|null" instead of "string"?
Line 68: Should the return type not be "string|null" instead of "string"?


lib\Doctrine\ORM\Id\AssignedGenerator.php (1 comments)
==================================================================
Line 41: Should the return type not be "array" instead of "?"?


lib\Doctrine\ORM\Id\IdentityGenerator.php (1 comments)
==================================================================
Line 35: Should the type for parameter "$seqName" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Id\SequenceGenerator.php (3 comments)
==================================================================
Line 54: Should the return type not be "integer" instead of "integer|double"?
Line 74: Should the return type not be "integer" instead of "integer|double"?
Line 84: Should the return type not be "integer" instead of "integer|double"?


lib\Doctrine\ORM\Internal\Hydration\AbstractHydrator.php (2 comments)
==================================================================
Line 118: Should the return type not be "Boolean|array" instead of "?"?
Line 163: Should the type for parameter "$result" not be "array" instead of "?"?


lib\Doctrine\ORM\Internal\Hydration\SimpleObjectHydrator.php (1 comments)
==================================================================
Line 146: Should the return type not be "null|array" instead of "array"?


lib\Doctrine\ORM\Mapping\Builder\AssociationBuilder.php (2 comments)
==================================================================
Line 132: Should the type for parameter "$onDelete" not be "string|null" instead of "string"?
Line 133: Should the type for parameter "$columnDef" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder.php (2 comments)
==================================================================
Line 50: Should the return type not be "ClassMetadataInfo" instead of "ClassMetadata"?
Line 368: Should the type for parameter "$inversedBy" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Mapping\Builder\FieldBuilder.php (1 comments)
==================================================================
Line 151: Should the type for parameter "$strategy" not be "string" instead of "integer"?


lib\Doctrine\ORM\Mapping\Builder\ManyToManyAssociationBuilder.php (2 comments)
==================================================================
Line 50: Should the type for parameter "$onDelete" not be "string|null" instead of "string"?
Line 51: Should the type for parameter "$columnDef" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Mapping\ClassMetadataFactory.php (5 comments)
==================================================================
Line 305: Are you sure that the method "getTableGeneratorDefinition()" exists?
Line 533: Should the type for parameter "$class" not be "ClassMetadataInfo" instead of "ClassMetadata"?
Line 583: This code seems not to be reachable, are you sure this is correct?
Line 621: Should the return type not be "RuntimeReflectionService|ReflectionService" instead of "ReflectionService"?
Line 634: Should the type for parameter "$reflectionService" not be "ReflectionService" instead of "the"?


lib\Doctrine\ORM\Mapping\ClassMetadataInfo.php (10 comments)
==================================================================
Line 597: Should the type for parameter "$namingStrategy" not be "null|NamingStrategy" instead of "NamingStrategy"?
Line 620: Should the return type not be "\ReflectionProperty" instead of "ReflectionProperty"?
Line 630: Should the return type not be "\ReflectionProperty" instead of "ReflectionProperty"?
Line 679: Should the type for parameter "$id" not be "array" instead of "?"?
Line 1168: Should the return type not be "null" instead of "array"?
Line 1499: Should the return type not be "array" instead of "?"?
Line 1549: Should the return type not be "array" instead of "string"?
Line 1692: Should the return type not be "null" instead of "Boolean"?
Line 2405: Should the return type not be "Boolean|integer" instead of "Boolean"?
Line 2658: Are you sure the variable "$platform" exists, or did you maybe forget to declare it?


lib\Doctrine\ORM\Mapping\Driver\AbstractFileDriver.php (1 comments)
==================================================================
Line 93: Should the return type not be "string" instead of "null"?


lib\Doctrine\ORM\Mapping\Driver\AnnotationDriver.php (8 comments)
==================================================================
Line 72: Should the type for parameter "$paths" not be "string|array|null" instead of "string|array"?
Line 115: Should the return type not be "string" instead of "null"?
Line 384: The assignment to $idAnnot seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 413: The assignment to $idAnnot seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 439: The assignment to $idAnnot seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 525: The assignment to $attributeOverrides seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 740: Should the type for parameter "$paths" not be "array" instead of "array|string"?
Line 741: Should the type for parameter "$reader" not be "null|AnnotationReader" instead of "AnnotationReader"?


lib\Doctrine\ORM\Mapping\Driver\DatabaseDriver.php (1 comments)
==================================================================
Line 173: The assignment to $indexes seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?


lib\Doctrine\ORM\Mapping\Driver\Driver.php (2 comments)
==================================================================
Line 46: Should the return type not be "null" instead of "array"?
Line 56: Should the return type not be "null" instead of "Boolean"?


lib\Doctrine\ORM\Mapping\Driver\PHPDriver.php (1 comments)
==================================================================
Line 66: The assignment to $metadata seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?


lib\Doctrine\ORM\Mapping\Driver\XmlDriver.php (1 comments)
==================================================================
Line 650: Are you sure the variable "$metadata" exists, or did you maybe forget to declare it?


lib\Doctrine\ORM\Mapping\Driver\YamlDriver.php (1 comments)
==================================================================
Line 656: Are you sure the variable "$metadata" exists, or did you maybe forget to declare it?


lib\Doctrine\ORM\Mapping\MappingException.php (3 comments)
==================================================================
Line 291: Should the return type not be "MappingException" instead of "self"?
Line 334: Should the return type not be "MappingException" instead of "self"?
Line 350: Should the return type not be "MappingException" instead of "self"?


lib\Doctrine\ORM\Mapping\NamingStrategy.php (8 comments)
==================================================================
Line 37: Should the return type not be "null" instead of "string"?
Line 45: Should the return type not be "null" instead of "string"?
Line 52: Should the return type not be "null" instead of "string"?
Line 60: Should the return type not be "null" instead of "string"?
Line 69: Should the type for parameter "$propertyName" not be "string|null" instead of "string"?
Line 70: Should the return type not be "null" instead of "string"?
Line 78: Should the type for parameter "$referencedColumnName" not be "string|null" instead of "string"?
Line 79: Should the return type not be "null" instead of "string"?


lib\Doctrine\ORM\NativeQuery.php (1 comments)
==================================================================
Line 48: Should the return type not be "string" instead of "?"?


lib\Doctrine\ORM\PersistentCollection.php (4 comments)
==================================================================
Line 129: Should the type for parameter "$assoc" not be "array" instead of "AssociationMapping"?
Line 142: Should the return type not be "object|null" instead of "object"?
Line 287: Should the return type not be "array" instead of "Mapping\AssociationMapping"?
Line 751: Should the type for parameter "$length" not be "integer|null" instead of "integer"?


lib\Doctrine\ORM\Persisters\BasicEntityPersister.php (15 comments)
==================================================================
Line 212: Should the return type not be "null|array" instead of "array"?
Line 587: Should the type for parameter "$entity" not be "object|null" instead of "object"?
Line 592: Should the type for parameter "$limit" not be "integer|null" instead of "integer"?
Line 706: Should the type for parameter "$orderBy" not be "null|array" instead of "array"?
Line 707: Should the type for parameter "$limit" not be "integer|null" instead of "integer"?
Line 708: Should the type for parameter "$offset" not be "integer|null" instead of "integer"?
Line 790: Should the type for parameter "$assoc" not be "array" instead of "ManyToManyMapping"?
Line 867: Should the type for parameter "$assoc" not be "AssociationMapping|null" instead of "AssociationMapping"?
Line 868: Should the type for parameter "$orderBy" not be "null|array" instead of "string"?
Line 870: Should the type for parameter "$limit" not be "integer|null" instead of "integer"?
Line 871: Should the type for parameter "$offset" not be "integer|null" instead of "integer"?
Line 1088: Should the type for parameter "$manyToMany" not be "array" instead of "ManyToManyMapping"?
Line 1290: Should the type for parameter "$assoc" not be "AssociationMapping|null" instead of "AssociationMapping"?
Line 1343: Should the type for parameter "$offset" not be "integer|null" instead of "integer"?
Line 1344: Should the type for parameter "$limit" not be "integer|null" instead of "integer"?


lib\Doctrine\ORM\Persisters\JoinedSubclassPersister.php (2 comments)
==================================================================
Line 130: Are you sure that you can call "_getInsertSQL()" as it is declared protected in class "Doctrine\ORM\Persisters\BasicEntityPersister"?
Line 145: Are you sure that you can call "_getInsertSQL()" as it is declared protected in class "Doctrine\ORM\Persisters\BasicEntityPersister"?


lib\Doctrine\ORM\Persisters\ManyToManyPersister.php (2 comments)
==================================================================
Line 237: Should the type for parameter "$length" not be "integer|null" instead of "integer"?
Line 371: Should the return type not be "array" instead of "string"?


lib\Doctrine\ORM\Persisters\OneToManyPersister.php (1 comments)
==================================================================
Line 139: Should the type for parameter "$length" not be "integer|null" instead of "integer"?


lib\Doctrine\ORM\Proxy\Autoloader.php (1 comments)
==================================================================
Line 57: Should the type for parameter "$notFoundCallback" not be "null|\Closure" instead of "Closure"?


lib\Doctrine\ORM\Proxy\ProxyFactory.php (2 comments)
==================================================================
Line 109: Should the type for parameter "$baseDir" not be "string|null" instead of "string"?
Line 125: Should the type for parameter "$toDir" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Query\AST\Functions\ConcatFunction.php (2 comments)
==================================================================
Line 48: Are you sure that the property "secondStringPrimary" exists, or did you maybe mean "secondStringPriamry"?
Line 62: Are you sure that the property "secondStringPrimary" exists, or did you maybe mean "secondStringPriamry"?


lib\Doctrine\ORM\Query\Exec\MultiTableDeleteExecutor.php (3 comments)
==================================================================
Line 43: Should the type for parameter "$AST" not be "AST\Node" instead of "Node"?
Line 54: Are you sure that the property "deleteClause" exists?
Line 73: Are you sure that the property "whereClause" exists?


lib\Doctrine\ORM\Query\Exec\MultiTableUpdateExecutor.php (2 comments)
==================================================================
Line 44: Should the type for parameter "$AST" not be "AST\Node" instead of "Node"?
Line 55: Are you sure that the property "updateClause" exists?


lib\Doctrine\ORM\Query\Expr\From.php (2 comments)
==================================================================
Line 52: Should the type for parameter "$indexBy" not be "string|null" instead of "string"?
Line 78: Should the return type not be "string|null" instead of "string"?


lib\Doctrine\ORM\Query\Expr\Join.php (7 comments)
==================================================================
Line 73: Should the type for parameter "$alias" not be "string|null" instead of "string"?
Line 74: Should the type for parameter "$conditionType" not be "string|null" instead of "string"?
Line 74: Should the type for parameter "$condition" not be "string|null" instead of "string"?
Line 76: Should the type for parameter "$indexBy" not be "string|null" instead of "string"?
Line 105: Should the return type not be "string|null" instead of "string"?
Line 113: Should the return type not be "string|null" instead of "string"?
Line 121: Should the return type not be "string|null" instead of "string"?
Line 129: Should the return type not be "string|null" instead of "string"?


lib\Doctrine\ORM\Query\Expr\OrderBy.php (3 comments)
==================================================================
Line 60: Should the type for parameter "$sort" not be "string|null" instead of "string"?
Line 61: Should the type for parameter "$order" not be "string|null" instead of "string"?
Line 72: Should the type for parameter "$order" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Query\Expr.php (1 comments)
==================================================================
Line 499: Should the type for parameter "$len" not be "integer|null" instead of "integer"?


lib\Doctrine\ORM\Query\Filter\SQLFilter.php (1 comments)
==================================================================
Line 64: Should the type for parameter "$type" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Query\Lexer.php (1 comments)
==================================================================
Line 163: The assignment to $value seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?


lib\Doctrine\ORM\Query\Parser.php (8 comments)
==================================================================
Line 364: Should the type for parameter "$token" not be "array|null" instead of "array"?
Line 387: Should the type for parameter "$token" not be "array|null" instead of "array"?
Line 1498: Should the return type not be "Query\AST\PartialObjectExpression" instead of "array"?
Line 1789: Should the return type not be "Query\AST\GeneralCaseExpression" instead of "Query\AST\GeneralExpression"?
Line 1835: Should the return type not be "Query\AST\WhenClause" instead of "Query\AST\WhenExpression"?
Line 1849: Should the return type not be "Query\AST\SimpleWhenClause" instead of "Query\AST\SimpleWhenExpression"?
Line 2317: Should the return type not be "Query\AST\Literal|null" instead of "string"?
Line 2925: Should the return type not be "string|null" instead of "string"?


lib\Doctrine\ORM\Query\ResultSetMapping.php (4 comments)
==================================================================
Line 127: Should the type for parameter "$resultAlias" not be "string|null" instead of "string"?
Line 172: The assignment to $found seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 178: The assignment to $found seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 257: Should the type for parameter "$declaringClass" not be "string|null" instead of "string"?


lib\Doctrine\ORM\Query\SqlWalker.php (6 comments)
==================================================================
Line 172: Should the return type not be "Query\Exec\MultiTableDeleteExecutor|Query\Exec\SingleTableDeleteUpdateExecutor|Query\Exec\MultiTableUpdateExecutor|Query\Exec\SingleSelectExecutor" instead of "AbstractExecutor"?
Line 398: This code seems not to be reachable, are you sure this is correct?
Line 513: Should the type for parameter "$fieldName" not be "string|null" instead of "string"?
Line 1039: Should the type for parameter "$generalCaseExpression" not be "Query\AST\GeneralCaseExpression" instead of "GeneralCaseExpression"?
Line 1690: The assignment to $entity seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 1856: The assignment to $fieldName seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?


lib\Doctrine\ORM\Query\TreeWalker.php (47 comments)
==================================================================
Line 44: Should the return type not be "null" instead of "string"?
Line 51: Should the return type not be "null" instead of "string"?
Line 58: Should the return type not be "null" instead of "string"?
Line 65: Should the return type not be "null" instead of "string"?
Line 73: Should the return type not be "null" instead of "string"?
Line 81: Should the return type not be "null" instead of "string"?
Line 89: Should the return type not be "null" instead of "string"?
Line 97: Should the return type not be "null" instead of "string"?
Line 105: Should the return type not be "null" instead of "string"?
Line 113: Should the return type not be "null" instead of "string"?
Line 121: Should the return type not be "null" instead of "string"?
Line 129: Should the return type not be "null" instead of "string"?
Line 137: Should the return type not be "null" instead of "string"?
Line 145: Should the return type not be "null" instead of "string"?
Line 153: Should the return type not be "null" instead of "string"?
Line 161: Should the return type not be "null" instead of "string"?
Line 169: Should the return type not be "null" instead of "string"?
Line 177: Should the return type not be "null" instead of "string"?
Line 185: Should the return type not be "null" instead of "string"?
Line 193: Should the return type not be "null" instead of "string"?
Line 201: Should the return type not be "null" instead of "string"?
Line 209: Should the return type not be "null" instead of "string"?
Line 217: Should the return type not be "null" instead of "string"?
Line 225: Should the return type not be "null" instead of "string"?
Line 233: Should the return type not be "null" instead of "string"?
Line 241: Should the return type not be "null" instead of "string"?
Line 249: Should the return type not be "null" instead of "string"?
Line 257: Should the return type not be "null" instead of "string"?
Line 265: Should the return type not be "null" instead of "string"?
Line 273: Should the return type not be "null" instead of "string"?
Line 281: Should the return type not be "null" instead of "string"?
Line 289: Should the return type not be "null" instead of "string"?
Line 297: Should the return type not be "null" instead of "string"?
Line 305: Should the return type not be "null" instead of "string"?
Line 313: Should the return type not be "null" instead of "string"?
Line 321: Should the return type not be "null" instead of "string"?
Line 329: Should the return type not be "null" instead of "string"?
Line 337: Should the return type not be "null" instead of "string"?
Line 345: Should the return type not be "null" instead of "string"?
Line 353: Should the return type not be "null" instead of "string"?
Line 361: Should the return type not be "null" instead of "string"?
Line 369: Should the return type not be "null" instead of "string"?
Line 377: Should the return type not be "null" instead of "string"?
Line 385: Should the return type not be "null" instead of "string"?
Line 393: Should the return type not be "null" instead of "string"?
Line 401: Should the return type not be "null" instead of "string"?
Line 408: Should the return type not be "null" instead of "AbstractExecutor"?


lib\Doctrine\ORM\Query\TreeWalkerAdapter.php (47 comments)
==================================================================
Line 78: Should the return type not be "null" instead of "string"?
Line 85: Should the return type not be "null" instead of "string"?
Line 92: Should the return type not be "null" instead of "string"?
Line 99: Should the return type not be "null" instead of "string"?
Line 107: Should the return type not be "null" instead of "string"?
Line 115: Should the return type not be "null" instead of "string"?
Line 123: Should the return type not be "null" instead of "string"?
Line 131: Should the return type not be "null" instead of "string"?
Line 139: Should the return type not be "null" instead of "string"?
Line 147: Should the return type not be "null" instead of "string"?
Line 155: Should the return type not be "null" instead of "string"?
Line 163: Should the return type not be "null" instead of "string"?
Line 171: Should the return type not be "null" instead of "string"?
Line 179: Should the return type not be "null" instead of "string"?
Line 187: Should the return type not be "null" instead of "string"?
Line 195: Should the return type not be "null" instead of "string"?
Line 203: Should the return type not be "null" instead of "string"?
Line 211: Should the return type not be "null" instead of "string"?
Line 219: Should the return type not be "null" instead of "string"?
Line 227: Should the return type not be "null" instead of "string"?
Line 235: Should the return type not be "null" instead of "string"?
Line 243: Should the return type not be "null" instead of "string"?
Line 251: Should the return type not be "null" instead of "string"?
Line 259: Should the return type not be "null" instead of "string"?
Line 267: Should the return type not be "null" instead of "string"?
Line 275: Should the return type not be "null" instead of "string"?
Line 283: Should the return type not be "null" instead of "string"?
Line 291: Should the return type not be "null" instead of "string"?
Line 299: Should the return type not be "null" instead of "string"?
Line 307: Should the return type not be "null" instead of "string"?
Line 315: Should the return type not be "null" instead of "string"?
Line 323: Should the return type not be "null" instead of "string"?
Line 331: Should the return type not be "null" instead of "string"?
Line 339: Should the return type not be "null" instead of "string"?
Line 347: Should the return type not be "null" instead of "string"?
Line 355: Should the return type not be "null" instead of "string"?
Line 363: Should the return type not be "null" instead of "string"?
Line 371: Should the return type not be "null" instead of "string"?
Line 379: Should the return type not be "null" instead of "string"?
Line 387: Should the return type not be "null" instead of "string"?
Line 395: Should the return type not be "null" instead of "string"?
Line 403: Should the return type not be "null" instead of "string"?
Line 411: Should the return type not be "null" instead of "string"?
Line 419: Should the return type not be "null" instead of "string"?
Line 427: Should the return type not be "null" instead of "string"?
Line 435: Should the return type not be "null" instead of "string"?
Line 442: Should the return type not be "null" instead of "AbstractExecutor"?


lib\Doctrine\ORM\Query\TreeWalkerChain.php (47 comments)
==================================================================
Line 66: Should the return type not be "null" instead of "string"?
Line 78: Should the return type not be "null" instead of "string"?
Line 90: Should the return type not be "null" instead of "string"?
Line 102: Should the return type not be "null" instead of "string"?
Line 115: Should the return type not be "null" instead of "string"?
Line 128: Should the return type not be "null" instead of "string"?
Line 141: Should the return type not be "null" instead of "string"?
Line 154: Should the return type not be "null" instead of "string"?
Line 167: Should the return type not be "null" instead of "string"?
Line 180: Should the return type not be "null" instead of "string"?
Line 193: Should the return type not be "null" instead of "string"?
Line 206: Should the return type not be "null" instead of "string"?
Line 219: Should the return type not be "null" instead of "string"?
Line 232: Should the return type not be "null" instead of "string"?
Line 245: Should the return type not be "null" instead of "string"?
Line 258: Should the return type not be "null" instead of "string"?
Line 271: Should the return type not be "null" instead of "string"?
Line 284: Should the return type not be "null" instead of "string"?
Line 297: Should the return type not be "null" instead of "string"?
Line 310: Should the return type not be "null" instead of "string"?
Line 323: Should the return type not be "null" instead of "string"?
Line 336: Should the return type not be "null" instead of "string"?
Line 349: Should the return type not be "null" instead of "string"?
Line 362: Should the return type not be "null" instead of "string"?
Line 375: Should the return type not be "null" instead of "string"?
Line 388: Should the return type not be "null" instead of "string"?
Line 401: Should the return type not be "null" instead of "string"?
Line 414: Should the return type not be "null" instead of "string"?
Line 427: Should the return type not be "null" instead of "string"?
Line 440: Should the return type not be "null" instead of "string"?
Line 453: Should the return type not be "null" instead of "string"?
Line 466: Should the return type not be "null" instead of "string"?
Line 479: Should the return type not be "null" instead of "string"?
Line 492: Should the return type not be "null" instead of "string"?
Line 505: Should the return type not be "null" instead of "string"?
Line 518: Should the return type not be "null" instead of "string"?
Line 531: Should the return type not be "null" instead of "string"?
Line 544: Should the return type not be "null" instead of "string"?
Line 557: Should the return type not be "null" instead of "string"?
Line 570: Should the return type not be "null" instead of "string"?
Line 583: Should the return type not be "null" instead of "string"?
Line 596: Should the return type not be "null" instead of "string"?
Line 609: Should the return type not be "null" instead of "string"?
Line 622: Should the return type not be "null" instead of "string"?
Line 635: Should the return type not be "null" instead of "string"?
Line 648: Should the return type not be "null" instead of "string"?
Line 660: Should the return type not be "null" instead of "AbstractExecutor"?


lib\Doctrine\ORM\Query.php (2 comments)
==================================================================
Line 417: Should the return type not be "Query" instead of "AbstractQuery"?
Line 432: Should the return type not be "null|string" instead of "string"?


lib\Doctrine\ORM\QueryBuilder.php (16 comments)
==================================================================
Line 216: Are you sure that the method "setFirstResult()" exists?
Line 438: Should the type for parameter "$append" not be "Boolean" instead of "string"?
Line 561: Should the type for parameter "$delete" not be "string|null" instead of "string"?
Line 562: Should the type for parameter "$alias" not be "string|null" instead of "string"?
Line 587: Should the type for parameter "$update" not be "string|null" instead of "string"?
Line 588: Should the type for parameter "$alias" not be "string|null" instead of "string"?
Line 614: Should the type for parameter "$indexBy" not be "string|null" instead of "string"?
Line 638: Should the type for parameter "$conditionType" not be "string|null" instead of "string"?
Line 638: Should the type for parameter "$condition" not be "string|null" instead of "string"?
Line 640: Should the type for parameter "$indexBy" not be "string|null" instead of "string"?
Line 663: Should the type for parameter "$conditionType" not be "string|null" instead of "string"?
Line 663: Should the type for parameter "$condition" not be "string|null" instead of "string"?
Line 665: Should the type for parameter "$indexBy" not be "string|null" instead of "string"?
Line 699: Should the type for parameter "$conditionType" not be "string|null" instead of "string"?
Line 699: Should the type for parameter "$condition" not be "string|null" instead of "string"?
Line 701: Should the type for parameter "$indexBy" not be "string|null" instead of "string"?
Line 938: Should the type for parameter "$order" not be "string|null" instead of "string"?
Line 952: Should the type for parameter "$order" not be "string|null" instead of "string"?
Line 1052: Should the type for parameter "$parts" not be "array|null" instead of "array"?


lib\Doctrine\ORM\Tools\Console\ConsoleRunner.php (1 comments)
==================================================================
Line 31: Should the type for parameter "$commands" not be "array" instead of "\Symfony\Component\Console\Command\Command[]"?


lib\Doctrine\ORM\Tools\ConvertDoctrine1Schema.php (2 comments)
==================================================================
Line 57: Are you sure that the property "_from" exists? Did you maybe forget to declare it?
Line 152: It seems like ``$matches`` was never initialized. Although not strictly required by PHP, it is generally a good practice to add ``$matches = array();`` before regardless.


lib\Doctrine\ORM\Tools\Export\Driver\AbstractExporter.php (11 comments)
==================================================================
Line 162: This code seems not to be reachable, are you sure this is correct?
Line 166: This code seems not to be reachable, are you sure this is correct?
Line 170: This code seems not to be reachable, are you sure this is correct?
Line 174: This code seems not to be reachable, are you sure this is correct?
Line 184: This code seems not to be reachable, are you sure this is correct?
Line 188: This code seems not to be reachable, are you sure this is correct?
Line 192: This code seems not to be reachable, are you sure this is correct?
Line 202: This code seems not to be reachable, are you sure this is correct?
Line 206: This code seems not to be reachable, are you sure this is correct?
Line 210: This code seems not to be reachable, are you sure this is correct?
Line 214: This code seems not to be reachable, are you sure this is correct?


lib\Doctrine\ORM\Tools\Export\Driver\PhpExporter.php (2 comments)
==================================================================
Line 131: It seems like ``$oneToManyMappingArray`` was never initialized. Although not strictly required by PHP, it is generally a good practice to add ``$oneToManyMappingArray = array();`` before regardless.
Line 144: It seems like ``$manyToManyMappingArray`` was never initialized. Although not strictly required by PHP, it is generally a good practice to add ``$manyToManyMappingArray = array();`` before regardless.


lib\Doctrine\ORM\Tools\Pagination\CountOutputWalker.php (1 comments)
==================================================================
Line 90: The assignment to $rootClass seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?


lib\Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker.php (1 comments)
==================================================================
Line 101: The assignment to $rootClass seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?


lib\Doctrine\ORM\Tools\Pagination\Paginator.php (1 comments)
==================================================================
Line 61: Should the type for parameter "$query" not be "Query" instead of "Query|QueryBuilder"?


lib\Doctrine\ORM\Tools\SchemaTool.php (6 comments)
==================================================================
Line 143: The assignment to $columns seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 146: The assignment to $columns seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 279: Should the return type not be "null" instead of "array"?
Line 321: The assignment to $column seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 343: Should the return type not be "null" instead of "array"?
Line 580: Maybe add a comment since this catch block is empty?


lib\Doctrine\ORM\Tools\Setup.php (8 comments)
==================================================================
Line 112: Should the type for parameter "$proxyDir" not be "string|null" instead of "string"?
Line 113: Should the type for parameter "$cache" not be "null|Cache" instead of "Cache"?
Line 128: Should the type for parameter "$proxyDir" not be "string|null" instead of "string"?
Line 129: Should the type for parameter "$cache" not be "null|Cache" instead of "Cache"?
Line 144: Should the type for parameter "$proxyDir" not be "string|null" instead of "string"?
Line 145: Should the type for parameter "$cache" not be "null|Cache" instead of "Cache"?
Line 159: Should the type for parameter "$proxyDir" not be "string|null" instead of "string"?
Line 160: Should the type for parameter "$cache" not be "null|Cache" instead of "Cache"?


lib\Doctrine\ORM\UnitOfWork.php (12 comments)
==================================================================
Line 773: This code seems not to be reachable, are you sure this is correct?
Line 1179: The assignment to $ignored seems to be dead. Sure it is necessary, or maybe you have a typo somewhere?
Line 1316: Should the type for parameter "$assume" not be "integer|null" instead of "integer"?
Line 1959: Is this fall-through intended? If so, this would be worth a comment, no?
Line 2000: Is this fall-through intended? If so, this would be worth a comment, no?
Line 2080: Is this fall-through intended? If so, this would be worth a comment, no?
Line 2146: Should the type for parameter "$lockVersion" not be "integer|null" instead of "integer"?
Line 2211: Should the type for parameter "$entityName" not be "string|null" instead of "string"?
Line 2285: Are you sure that the property "collectionsDeletions" exists, or did you maybe mean "collectionDeletions"?
Line 2581: Should the type for parameter "$collection" not be "PersistentCollection" instead of "PeristentCollection"?
Line 2760: Should the type for parameter "$association" not be "array" instead of "AssociationMapping"?
Line 2950: Should the return type not be "Boolean" instead of "null"?


tests\Doctrine\Tests\Mocks\ClassMetadataMock.php (1 comments)
==================================================================
Line 11: Are you sure that the property "_generatorType" exists, or did you maybe mean "generatorType"?


tests\Doctrine\Tests\Mocks\EntityManagerMock.php (2 comments)
==================================================================
Line 70: Should the type for parameter "$config" not be "null|\Doctrine\ORM\Configuration" instead of "Doctrine_Configuration"?
Line 71: Should the type for parameter "$eventManager" not be "null|\Doctrine\Common\EventManager" instead of "Doctrine_EventManager"?


tests\Doctrine\Tests\Mocks\MockTreeWalker.php (1 comments)
==================================================================
Line 10: Should the return type not be "null" instead of "AbstractExecutor"?


tests\Doctrine\Tests\Mocks\UnitOfWorkMock.php (2 comments)
==================================================================
Line 51: Are you sure that the property "_entityStates" exists, or did you maybe mean "entityStates"?
Line 56: Are you sure that the property "_originalEntityData" exists, or did you maybe mean "originalEntityData"?
