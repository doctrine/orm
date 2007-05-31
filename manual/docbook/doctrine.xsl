<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:import href="/usr/share/sgml/docbook/xsl-stylesheets-1.70.1/xhtml/docbook.xsl"/>
<xsl:import href="/home/clients/jhassine/doctrine/trunk/manual/docbook/programlisting/colorer.xsl"/>
<xsl:import href="/home/clients/jhassine/doctrine/trunk/manual/docbook/programlisting/colorer-html.xsl"/>
<xsl:param name="html.stylesheet" select="'doctrine.css'"/>
<xsl:param name="section.autolabel" select="1"/>
<xsl:param name="section.label.includes.component.label" select="1"/><!-- adds section numbering, ie '1.3.1. Mailing Lists' -->
</xsl:stylesheet>
