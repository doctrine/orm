XSLSTYLE=doctrine.xsl
XSLSTYLE_CHUNKED=doctrine-chunked.xsl
%.html:  %.xml
	xsieve -xinclude --output $@ $(XSLSTYLE)  $<
	xsieve -xinclude --stringparam base.dir html/ $(XSLSTYLE_CHUNKED)  $<

html:  doctrine.html
