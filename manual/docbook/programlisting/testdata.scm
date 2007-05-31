; test cases for joining parallel markup
; $Id: testdata.scm,v 1.2 2006/03/02 05:58:55 olpa Exp $
; (test-case in-xml also-xml joined-xml)

; the simplest test, no highlighting at all
(test-case
  '(i "012")
  '(h "012")
  '(i "012"))

; the simplest test, some highlighting
(test-case
  '(i "012")
  '(h "0" (a "1") "2")
  '(i (colorer:dummy "0" (a "1") "2")))

; the size of text is different
(test-case
  '(i "0123456789")
  '(h (a "01") "234" (b "56") "7")
  '(i (colorer:dummy (a "01") "234" (b "56") "7" "89")))

(test-case
  '(i "01234567")
  '(h "0" (a "12") "345" (b "5789"))
  '(i (colorer:dummy "0" (a "12") "345" (b "67"))))

; the text of the main tree is not corrupted
(test-case
  '(i "012345")
  '(h "ab" (c "cd") "ef")
  '(i (colorer:dummy "01" (c "23") "45")))

; attributes are saved
(test-case
  '(i "012345")
  '(h "01"
    (a (@ (a1 "a1") (a2 "a2"))
      (b (@ (b1 "b1") (b2 "b2"))
        "23"))
    "45")
  '(i (colorer:dummy "01"
    (a (@ (a1 "a1") (a2 "a2"))
      (b (@ (b1 "b1") (b2 "b2"))
        "23"))
    "45")))

; ordering and nesting of empty tags
(test-case
  '(i "012" (x (y)) (z) "34")
  '(h "01" (a "23") "4")
  '(i (colorer:dummy "01" (a "2")) (x (y)) (z) (colorer:dummy (a "3") "4")))

; intersecting at left
(test-case
  '(i "01" (a "2345" (b "67")))
  '(h "012" (x (y "3456")) "7")
  '(i "01" (a (colorer:dummy "2" (x (y "345"))) (b (colorer:dummy (x (y "6")) "7")))))

; intersecting at right
(test-case
  '(i "01" (a "23" (b "45") "6") "78")
  '(h "01234" (x (y "56")) "78")
  '(i "01" (a "23" (b (colorer:dummy "4" (x (y "5")))) (x (y "6"))) "78"))

