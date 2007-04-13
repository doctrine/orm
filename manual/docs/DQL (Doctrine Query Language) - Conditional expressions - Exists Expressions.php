Syntax:
<code>
<operand> [NOT ]EXISTS (<subquery>)
</code>
The EXISTS operator returns TRUE if the subquery returns one or more rows and FALSE otherwise. 



The NOT EXISTS operator returns TRUE if the subquery returns 0 rows and FALSE otherwise.



Finding all articles which have readers:
<code>
FROM Article
  WHERE EXISTS (FROM ReaderLog(id)
                WHERE ReaderLog.article_id = Article.id)
</code>
Finding all articles which don't have readers:
<code>
FROM Article
  WHERE NOT EXISTS (FROM ReaderLog(id)
                WHERE ReaderLog.article_id = Article.id)
</code>     

