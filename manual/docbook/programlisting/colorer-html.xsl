<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:syn="http://ns.laxan.com/text-vimcolor/1" version="1.0">
<!-- $Id: colorer-html.xsl,v 1.2 2006/04/29 05:48:16 olpa Exp $ -->

<xsl:template match="syn:Comment">
  <span style="color:#0000FF;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Constant">
  <span style="color:#FF00FF;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Identifier">
  <span style="color:#008B8B;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Statement">
  <span style="color:#A52A2A; font-weight:bold;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:PreProc">
  <span style="color:#A020F0;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Type">
  <span style="color:#2E8B57; font-weight:bold;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Special">
  <span style="color:#6A5ACD;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Underlined">
  <span style="color:#000000; text-decoration:underline;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Error">
  <span style="color:#FFFFFF; background:#FF0000 none;">
    <xsl:apply-templates />
  </span>
</xsl:template>

<xsl:template match="syn:Todo">
  <span style="color:#0000FF; background: #FFFF00 none;">
    <xsl:apply-templates />
  </span>
</xsl:template>

</xsl:stylesheet>
