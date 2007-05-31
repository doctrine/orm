<xsl:stylesheet
  xmlns:xsl = "http://www.w3.org/1999/XSL/Transform"
  version   = "1.0"
  xmlns:s   = "http://xsieve.sourceforge.net"
  xmlns:syn = "http://ns.laxan.com/text-vimcolor/1"
  extension-element-prefixes="s">
<!-- $Id: colorer-one.xsl,v 1.1 2006/05/22 04:23:51 olpa Exp $ -->

<xsl:import href="colorer.xsl" />

<xsl:template match="node()|@*">
  <xsl:copy>
    <xsl:apply-templates select="node()|@*" />
  </xsl:copy>
</xsl:template>

<xsl:template match="programlisting | screen[starts-with(@role,'colorer:')]">
  <xsl:apply-imports />
</xsl:template>

</xsl:stylesheet>

