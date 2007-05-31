XSLSTYLE=/usr/share/sgml/docbook/xsl-stylesheets-1.70.1/xhtml/docbook.xsl
%.html:  %.xml
	xsltproc  --output  $@  $(XSLSTYLE)  $<

html:  doctrine.html
