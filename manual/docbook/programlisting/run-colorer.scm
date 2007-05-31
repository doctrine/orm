; Run colorer and return the result as SXML
; $Id: run-colorer.scm,v 1.6 2006/04/29 05:47:24 olpa Exp $

(define colorer-bin           #f)
(define colorer-params        #f)
(define colorer-param-type    #f)
(define colorer-param-outfile #f)

; Initialize colorer variables (only once)
(define (init-colorer-variables)
  (if (not colorer-bin) (begin
    (set! colorer-bin           (x:eval "string($colorer.bin)"))
    (set! colorer-params        (x:eval "string($colorer.params)"))
    (set! colorer-param-type    (x:eval "string($colorer.param.type)"))
    (set! colorer-param-outfile (x:eval "string($colorer.param.outfile)")))))

(define-macro (no-errors . body)
  `(catch #t (lambda () ,@body) (lambda (dummy . args) #f)))

(define (run-colorer program-text program-type)
  ; Some sanity check
  (init-colorer-variables)
  (if (not (and program-text (> (string-length program-text) 0)))
    #f
    (let* (
          ; Construct command line to run the colorer
          (infile   (tmpnam)) ; for the program text
          (outfile  (tmpnam)) ; for the colored tokens
          (cmdline (string-append
                     colorer-bin " " colorer-params " "
                     (if (and program-type (> (string-length program-type) 0))
                       (string-append colorer-param-type program-type " ")
                       "")
                     colorer-param-outfile outfile " " infile)))
      ;(display "Command line: ")(display cmdline)(newline)
      ; Ignore errors
      (no-errors
        ; Write the program text to the file and execute the colorer
        (with-output-to-file infile
            (lambda () (display program-text)))
        ;(system (string-append "cp " infile " lastin")) ; DEBUG
        (system cmdline)
        ;(system (string-append "cp " outfile " last")) ; DEBUG
        ; Load the XML result, cleanup and return the result
        (let* (
               (eval-str (string-append "document('file://" outfile "')"))
               (tree (x:eval eval-str)))
          (no-errors (delete-file outfile))
          (no-errors (delete-file infile))
          ; drop "*TOP*" and drop namespace declaration from "syn:syntax"
          (cons 'syn:syntax (cdr (cdadar tree))))))))
