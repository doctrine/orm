Syntax:
<div class='sql'>
<pre>
<i>operand</i> [NOT ]EXISTS (<i>subquery</i>)
</pre>
</div>
The EXISTS operator returns TRUE if the subquery returns one or more rows and FALSE otherwise. <br \>
<br \>
The NOT EXISTS operator returns TRUE if the subquery returns 0 rows and FALSE otherwise.<br \>
<br \>
Finding all articles which have readers:
<div class='sql'>
<pre>
FROM Article
  WHERE EXISTS (FROM ReaderLog(id)
                WHERE ReaderLog.article_id = Article.id)
</pre>
</div>
Finding all articles which don't have readers:
<div class='sql'>
<pre>
FROM Article
  WHERE NOT EXISTS (FROM ReaderLog(id)
                WHERE ReaderLog.article_id = Article.id)
</pre>
</div>     
