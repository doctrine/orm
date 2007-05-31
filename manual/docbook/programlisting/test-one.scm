(load "sxml-utils.scm")
(load "colorer.scm")

(define main-tree '(programlisting (*PI* a "b") (@ (format "linespecific")) "<article id=\"hw\"> 
  <title>Hello</title> 
  <para>Hello <object>World</object>!</para> 
</article>"))

(define h-tree "")

(define result (colorer:join-markup main-tree h-tree '(h)))

(write result)
