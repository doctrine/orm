XSLSTYLE=doctrine.xsl
XSLSTYLE_CHUNKED=doctrine-chunked.xsl
%.html:  %.xml
	xsltproc  --output $@ $(XSLSTYLE)  $<
	xsltproc  --stringparam base.dir html/ $(XSLSTYLE_CHUNKED)  $<

html:  doctrine.html
