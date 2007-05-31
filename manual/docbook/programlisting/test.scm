; $Id: test.scm,v 1.2 2006/03/02 06:01:06 olpa Exp $

(define (test-case main-tree h-tree expected-result)
  (display "------- Running a test case...")
  (let ((result (caddr (colorer:join-markup main-tree h-tree '(h)))))
    (if (equal? result expected-result)
      (begin
        (display "Ok")(newline))
      (begin
        (display "Error")(newline)
        (display "Expected: ")(write expected-result)(newline)
        (display "Result:   ")(write result)(newline)))))

(load "sxml-utils.scm")
(load "colorer.scm")
(load "testdata.scm")
