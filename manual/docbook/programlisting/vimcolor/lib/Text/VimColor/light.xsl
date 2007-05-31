<?xml version="1.0"?>

<!--
     This is an XSLT/XSL-FO stylesheet designed to be used with the XML
     output of the Perl module Text::VimColor.

     This is designed to make the highlighting look like the default gvim
     colour scheme, with 'background=light'.

     Geoff Richards <qef@laxan.com>

     This XSL file (light.xsl) is public domain.  Do what you want with it.

     Bugs: background colouring doesn't work in FOP.
  -->

<xsl:stylesheet version="1.0"
                xmlns:fo="http://www.w3.org/1999/XSL/Format"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:syn="http://ns.laxan.com/text-vimcolor/1">

 <xsl:template match="syn:syntax">
  <fo:root>

   <fo:layout-master-set>

    <!-- Master for odd (right hand) pages -->
    <fo:simple-page-master master-name="recto"
       page-height="297mm" page-width="210mm"
       margin-top="10mm" margin-left="25mm"
       margin-bottom="10mm" margin-right="15mm">
     <fo:region-body margin-top="10mm" margin-bottom="10mm"/>
     <fo:region-before extent="10mm"/>
     <fo:region-after extent="10mm"/>
    </fo:simple-page-master>

    <!-- Master for even (left hand) pages -->
    <fo:simple-page-master master-name="verso"
       page-height="297mm" page-width="210mm"
       margin-top="10mm" margin-left="15mm"
       margin-bottom="10mm" margin-right="25mm">
     <fo:region-body margin-top="10mm" margin-bottom="10mm"/>
     <fo:region-before extent="10mm"/>
     <fo:region-after extent="10mm"/>
    </fo:simple-page-master>

    <fo:page-sequence-master master-name="recto-verso">
     <fo:repeatable-page-master-alternatives>
      <fo:conditional-page-master-reference
         master-name="recto" odd-or-even="odd"/>
      <fo:conditional-page-master-reference
         master-name="verso" odd-or-even="even"/>
     </fo:repeatable-page-master-alternatives>
    </fo:page-sequence-master>

   </fo:layout-master-set>

   <fo:page-sequence master-reference="recto">

    <!-- Header -->
    <fo:static-content flow-name="xsl-region-before">
     <fo:block text-align="end" font-size="10pt"
               font-family="sans-serif" font-style="italic">
      <xsl:value-of select="@filename"/>
     </fo:block>
    </fo:static-content>

    <!-- Footer -->
    <fo:static-content flow-name="xsl-region-after">
     <fo:block text-align="end" font-size="10pt" font-family="sans-serif">
      <fo:page-number/>
     </fo:block>
    </fo:static-content>

    <!-- Body text -->
    <fo:flow flow-name="xsl-region-body">
     <fo:block font-family="monospace" font-size="10pt" line-height="12pt"
               white-space-collapse="false">
      <xsl:apply-templates/>
     </fo:block>
    </fo:flow>

   </fo:page-sequence>

  </fo:root>
 </xsl:template>

 <xsl:template match="syn:Comment">
  <fo:inline color="#0000FF"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Constant">
  <fo:inline color="#FF00FF"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Identifier">
  <fo:inline color="#008B8B"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Statement">
  <fo:inline color="#A52A2A" font-weight="bold"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:PreProc">
  <fo:inline color="#A020F0"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Type">
  <fo:inline color="#2E8B57" font-weight="bold"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Special">
  <fo:inline color="#6A5ACD"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Underlined">
  <fo:inline text-decoration="underline"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Error">
  <fo:inline color="#FFFFFF" background-color="#FF0000"><xsl:apply-templates/></fo:inline>
 </xsl:template>

 <xsl:template match="syn:Todo">
  <fo:inline color="#0000FF" background-color="#FFFF00"><xsl:apply-templates/></fo:inline>
 </xsl:template>

</xsl:stylesheet>
