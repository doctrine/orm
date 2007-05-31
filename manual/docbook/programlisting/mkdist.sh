rm -rf xsieve-programlisting.zip xsieve-programlisting
mkdir xsieve-programlisting
rsync -av --exclude CVS vimcolor xsieve-programlisting/
cp colorer.xsl colorer-html.xsl testdoc.xsl index.html sxml-utils.scm colorer.scm run-colorer.scm test.xml testdoc.html xsieve-programlisting/
zip xsieve-programlisting.zip -r xsieve-programlisting

