; $Id: colorer.scm,v 1.8 2006/04/29 04:49:48 olpa Exp $

; construct a tree from the text and path
; "ignore" is a list of path element which shouldn't be added to the tree
; each path item is either symbol, which is the node name,
; either (symbol (@ ...)), which is the node name and the attribute node.
; It is supposed that elements with attributes aren't in the ignore list.
(define (colorer:path-to-tree text path ignore)
  (let loop ((tree text) (path path))
    (if (null? path)
      tree
      (let (
            (cur     (car path))
            (nodeset (cons tree '())))
        (loop
          (if (pair? cur)
            (append cur nodeset)
            (if (memq cur ignore) tree (cons cur nodeset)))
          (cdr path))))))

; A fragment of the text node handler
(define-macro (%colorer:on-text)
  (quote (let loop ((cur cur))
    (let* (
           (len-buf (string-length buf))
           (len-cur (string-length cur))
           (len-min (min len-buf len-cur)))
      (if (> len-cur 0) ; the text node in the h-tree isn't finished yet?
        (let (
              (result ; result is either a tree, eiter #f
                (if (zero? len-buf) ; the text node in the main tree finished?
                  #f
                  (colorer:path-to-tree
                    (substring buf 0 len-min)
                    path
                    ignore))))
          ; Update buffer, switch to the main tree traversing,
          ; continue h-tree traversing on switching back
          (set! buf (substring buf len-min len-buf))
          (call-with-current-continuation (lambda (here)
                     (set! walker here)
                     (yield result)))
          (loop (substring cur len-min len-cur))))))))

; A fragment of the node and attribute handler
(define-macro (%colorer:on-pair)
  (quote (let ((elem (car cur)))
    (if (eq? elem '@)
      ; attribute: attach to the path
      (set-car! path (list (car path) cur))
      ; element: update path, continue traversing
      (let ((path (cons (car cur) path)))
        (for-each
          (lambda (kid) (loop kid path))
          (cdr cur)))))))

; generator of highlighted chunks.
; Creation:
; (define highlighter (colorer:join-markup-stepper highlight-tree ignore))
; Usage step:
; (highlighter more-buf)
; where more-buf either text, either #f. Each step returns either a
; subtree, either #f if buffer is over.
(define (colorer:join-markup-stepper highlight-tree ignore)
  (letrec (
    (buf   #f)
    (yield #f)
    ; The main loop
    (walker-loop (lambda (cur path)
          (let loop ((cur cur) (path path))
            (if (pair? cur)
              (%colorer:on-pair)
              (%colorer:on-text)))
          ; The highlighting tree is over. Stop looping.
          ; If the main tree isn't over (impossible),
          ; just return the data from main tree.
          (set! walker (lambda (dummy)
                (if (and buf (> (string-length buf) 0))
                  (let ((old-buf buf))
                    (set! buf #f)
                    (yield old-buf))
                  (yield #f))))
          (walker 'dummy)))
    ; Set buffer, continue looping
    (walker-entry
          (lambda (new-buf)
            (if new-buf
              (set! buf new-buf))
            (call-with-current-continuation (lambda (here)
                       (set! yield here)
                       (walker 'resume)))))
    ; Use once, than re-set
    (walker
          (lambda (dummy)
            (set! walker walker-loop)
            (walker-loop highlight-tree '()))))
    ; create generator
    walker-entry))

; add the colorer namespace to the tree
(define (colorer:wrap-by-ns tree)
  `(syn:syntax (@ (@
      (*NAMESPACES* (syn "http://ns.laxan.com/text-vimcolor/1"))))
    ,tree))

; join main markup with highlighting markup
(define colorer:id (lambda x x))
(define (colorer:join-markup main-tree highlight-tree ignore)
  (let ((stepper (colorer:join-markup-stepper highlight-tree ignore)))
    (colorer:wrap-by-ns
      ; Walk over the main tree
      (pre-post-order main-tree `(
        ; Comments, entities etc are not possible, so only few special cases
        (*PI*      *preorder* . ,colorer:id)
        (@         *preorder* . ,colorer:id)
        (*default*            . ,colorer:id)
        ; Text node: split on highlighted subtrees
        (*text* . ,(lambda (trigger str)
          (let loop (
                     (chunks '())
                     (tree   (stepper str)))
            (if tree
              ; Loop while trees are being generated
              (loop (cons tree chunks) (stepper #f))
              ; The node is processed. If there is only one chunk, return
              ; it, otherwise wrap the nodeset of chunks by a dummy
              ; element. Handle also impossible case of absense of chunks.
              (cond
                ((null? chunks)       "")
                ((null? (cdr chunks)) (car chunks))
                (else      (cons 'syn:syntax (reverse chunks)))))))))))))
