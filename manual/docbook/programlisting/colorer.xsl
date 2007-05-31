<xsl:stylesheet
  xmlns:xsl = "http://www.w3.org/1999/XSL/Transform"
  version   = "1.0"
  xmlns:s   = "http://xsieve.sourceforge.net"
  xmlns:syn = "http://ns.laxan.com/text-vimcolor/1"
  extension-element-prefixes="s">
<!-- $Id: colorer.xsl,v 1.6 2006/04/29 04:30:03 olpa Exp $ -->

<xsl:param name="colorer.bin">/home/clients/jhassine/doctrine/trunk/manual/docbook/programlisting/vimcolor/vimcolor-wrapper</xsl:param>
<xsl:param name="colorer.params">--format xml</xsl:param>
<xsl:param name="colorer.param.type">--filetype </xsl:param>
<xsl:param name="colorer.param.outfile">--output </xsl:param>

<s:init>
(load-from-path "sxml-utils.scm")
(load-from-path "colorer.scm")
(load-from-path "run-colorer.scm")
</s:init>

<!-- ProgramListing is colorized -->
<xsl:template match="programlisting[parent::syn:syntax] | screen[parent::syn:syntax]" priority="2">
  <xsl:apply-imports/>
</xsl:template>

<!-- Colorize ProgramListing -->
<xsl:template match="programlisting | screen[starts-with(@role,'colorer:')]">
  <xsl:variable name="type">
    <xsl:choose>
      <xsl:when test="self::screen"><xsl:value-of select="substring-after(@role,':')"/></xsl:when>
      <xsl:otherwise><xsl:value-of select="@role"/></xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  <s:scheme>
    (let* (
        (highlighted-tree (run-colorer (x:eval "string(.)") (x:eval "string($type)")))
        (current          (x:current))
        (united-tree
          (if (not highlighted-tree)
            #f
            (colorer:join-markup current highlighted-tree '()))))
      (x:apply-templates
        'with-param 'colorized #t
        (if united-tree
          united-tree
          (colorer:wrap-by-ns current))))
  </s:scheme>
</xsl:template>

<xsl:template match="syn:syntax">
  <xsl:apply-templates select="node()"/>
</xsl:template>

</xsl:stylesheet>

