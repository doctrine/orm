XSLSTYLE=doctrine.xsl
XSLSTYLE_CHUNKED=doctrine-chunked.xsl
%.html:  book/%.xml
	/usr/local/bin/xsieve -xinclude --output $@ $(XSLSTYLE) $<
	/usr/local/bin/xsieve -xinclude --stringparam base.dir html/ $(XSLSTYLE_CHUNKED) $<

html:  doctrine.html
