<?xml version="1.0" encoding="utf-8"?>

<!--

   svn2cl.xsl - xslt stylesheet for converting svn log to a normal
                changelog

   version 0.9

   Usage (replace ++ with two minus signs which aren't allowed
   inside xml comments):
     svn ++verbose ++xml log | \
       xsltproc ++stringparam strip-prefix `basename $(pwd)` \
                ++stringparam linelen 75 \
                ++stringparam groupbyday yes \
                ++stringparam separate-daylogs yes \
                ++stringparam include-rev yes \
                ++stringparam breakbeforemsg yes/2 \
                ++stringparam reparagraph yes \
                ++stringparam authorsfile FILE \
                ++stringparam ignore-message-starting \
                svn2cl.xsl - > ChangeLog

   This file is based on several implementations of this conversion
   that I was not completely happy with and some other common
   xslt constructs found on the web.

   Copyright (C) 2004, 2005, 2006, 2007 Arthur de Jong.

   Redistribution and use in source and binary forms, with or without
   modification, are permitted provided that the following conditions
   are met:
   1. Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
   2. Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in
      the documentation and/or other materials provided with the
      distribution.
   3. The name of the author may not be used to endorse or promote
      products derived from this software without specific prior
      written permission.

   THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
   IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
   WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
   ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY
   DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
   DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
   GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
   INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
   IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
   OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
   IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

-->

<!DOCTYPE xsl:stylesheet [
 <!ENTITY tab "&#9;">
 <!ENTITY newl "&#10;">
 <!ENTITY space "&#32;">
]>

<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

 <xsl:output
   method="text"
   encoding="utf-8"
   media-type="text/plain"
   omit-xml-declaration="yes"
   standalone="yes"
   indent="no" />

 <xsl:strip-space elements="*" />

 <!-- the prefix of pathnames to strip -->
 <xsl:param name="strip-prefix" select="'/'" />

 <!-- the length of a line to wrap messages at -->
 <xsl:param name="linelen" select="75" />
 
 <!-- whether entries should be grouped by day -->
 <xsl:param name="groupbyday" select="'no'" />

 <!-- whether to seperate log messages by empty lines -->
 <xsl:param name="separate-daylogs" select="'no'" />

 <!-- whether a revision number should be included -->
 <xsl:param name="include-rev" select="'no'" />

 <!-- whether the log message should start on a new line -->
 <xsl:param name="breakbeforemsg" select="'no'" />

 <!-- whether the message should be rewrapped within one paragraph -->
 <xsl:param name="reparagraph" select="'no'" />

 <!-- whether certain messages should be ignored -->
 <xsl:param name="ignore-message-starting" select="''" />

 <!-- location of authors file if any -->
 <xsl:param name="authorsfile" select="''" />
 <xsl:key name="author-lookup" match="author" use="@uid" />
 <xsl:variable name="authors-top" select="document($authorsfile)/authors" />

 <!-- match the topmost log entry -->
 <xsl:template match="log">
  <xsl:choose>
   <xsl:when test="$ignore-message-starting != ''">
    <!-- only handle logentries with don't contain the string -->
    <xsl:apply-templates select="logentry[not(starts-with(msg,$ignore-message-starting))]" />
   </xsl:when>
   <xsl:otherwise>
    <xsl:apply-templates select="logentry" />
   </xsl:otherwise>
  </xsl:choose>
  <!-- add newlines at the end of the changelog -->
  <xsl:text>&newl;</xsl:text>
 </xsl:template>

 <!-- format one entry from the log -->
 <xsl:template match="logentry">
  <xsl:choose>
   <!-- if we're grouping we should omit some headers -->
   <xsl:when test="$groupbyday='yes'">
    <!-- save log entry number -->
    <xsl:variable name="pos" select="position()" />
    <!-- fetch previous entry's date -->
    <xsl:variable name="prevdate">
     <xsl:apply-templates select="../logentry[position()=(($pos)-1)]/date" />
    </xsl:variable>
    <!-- fetch previous entry's author -->
    <xsl:variable name="prevauthor">
     <xsl:value-of select="normalize-space(../logentry[position()=(($pos)-1)]/author)" />
    </xsl:variable>
    <!-- fetch this entry's date -->
    <xsl:variable name="date">
     <xsl:apply-templates select="date" />
    </xsl:variable>
    <!-- fetch this entry's author -->
    <xsl:variable name="author">
     <xsl:value-of select="normalize-space(author)" />
    </xsl:variable>
    <!-- check if header is changed -->
    <xsl:if test="($prevdate!=$date) or ($prevauthor!=$author)">
     <!-- add newline -->
     <xsl:if test="not(position()=1)">
      <xsl:text>&newl;</xsl:text>
     </xsl:if>
     <!-- date -->
     <xsl:value-of select="$date" />
     <!-- two spaces -->
     <xsl:text>&space;&space;</xsl:text>
     <!-- author's name -->
     <xsl:apply-templates select="author" />
     <!-- two newlines -->
     <xsl:text>&newl;</xsl:text>
     <xsl:if test="$separate-daylogs!='yes'"><xsl:text>&newl;</xsl:text></xsl:if>
    </xsl:if>
   </xsl:when>
   <!-- write the log header -->
   <xsl:otherwise>
    <!-- add newline -->
    <xsl:if test="not(position()=1)">
     <xsl:text>&newl;</xsl:text>
    </xsl:if>
    <!-- date -->
    <xsl:apply-templates select="date" />
    <!-- two spaces -->
    <xsl:text>&space;&space;</xsl:text>
    <!-- author's name -->
    <xsl:apply-templates select="author" />
    <!-- two newlines -->
    <xsl:text>&newl;&newl;</xsl:text>
   </xsl:otherwise>
  </xsl:choose>
  <!-- get paths string -->
  <xsl:variable name="paths">
   <xsl:apply-templates select="paths" />
   <xsl:text>:&space;</xsl:text>
  </xsl:variable>
  <!-- get revision number -->
  <xsl:variable name="rev">
   <xsl:if test="$include-rev='yes'">
    <xsl:text>[r</xsl:text>
    <xsl:value-of select="@revision" />
    <xsl:text>]&space;</xsl:text>
   </xsl:if>
  </xsl:variable>
  <!-- trim trailing newlines -->
  <xsl:variable name="msg">
   <!-- add a line break before the log message -->
   <xsl:choose>
    <xsl:when test="$breakbeforemsg='yes'">
     <xsl:text>&newl;</xsl:text>
    </xsl:when>
    <xsl:when test="number($breakbeforemsg)&gt;0">
     <xsl:call-template name="newlines">
      <xsl:with-param name="count" select="number($breakbeforemsg)" />
     </xsl:call-template>
    </xsl:when>
   </xsl:choose>
   <xsl:call-template name="trim-newln">
    <xsl:with-param name="txt" select="msg" />
   </xsl:call-template>
  </xsl:variable>
  <!-- add newline here if separate-daylogs is in effect -->
  <xsl:if test="$groupbyday='yes' and $separate-daylogs='yes'"><xsl:text>&newl;</xsl:text></xsl:if>
  <!-- first line is indented (other indents are done in wrap template) -->
  <xsl:text>&tab;*&space;</xsl:text>
  <!-- print the paths and message nicely wrapped -->
  <xsl:call-template name="wrap">
   <xsl:with-param name="txt" select="concat($rev,$msg)" />
  </xsl:call-template>
 </xsl:template>

 <!-- format date -->
 <xsl:template match="date">
  <xsl:variable name="date" select="normalize-space(.)" />
  <!-- output date part -->
  <xsl:value-of select="substring($date,1,10)" />
  <!-- output time part -->
  <xsl:if test="$groupbyday!='yes'">
   <xsl:text>&space;</xsl:text>
   <xsl:value-of select="substring($date,12,5)" />
  </xsl:if>
 </xsl:template>

 <!-- format author -->
 <xsl:template match="author">
  <xsl:variable name="uid" select="normalize-space(.)" />
  <!-- try to lookup author in authorsfile -->
  <xsl:choose>
   <xsl:when test="$authorsfile!=''">
    <xsl:for-each select="$authors-top">
     <xsl:variable name="author" select="key('author-lookup',$uid)" />
     <!-- present result -->
     <xsl:choose>
      <xsl:when test="string($author/.)">
       <xsl:apply-templates select="$author/node()" mode="copy" />
      </xsl:when>
      <xsl:otherwise>
       <xsl:value-of select="$uid" />
      </xsl:otherwise>
     </xsl:choose>
    </xsl:for-each>
   </xsl:when>
   <xsl:otherwise>
    <xsl:value-of select="$uid" />
   </xsl:otherwise>
  </xsl:choose>
 </xsl:template>

 <!-- copy but normalize text -->
 <xsl:template match="text()" mode="copy">
  <xsl:value-of select="normalize-space(.)" />
 </xsl:template>

 <!-- simple copy template -->
 <xsl:template match="@*|node()" mode="copy">
  <xsl:copy>
   <xsl:apply-templates select="@*|node()" mode="copy" />
  </xsl:copy>
 </xsl:template>

 <!-- present a list of paths names -->
 <xsl:template match="paths">
  <xsl:choose>
   <!-- only handle paths that begin with the path and strip the path -->
   <xsl:when test="$strip-prefix != ''">
    <!-- if strip-prefix does not start with a slash, prepend it -->
    <xsl:variable name="tmpstrip1">
     <xsl:choose>
      <xsl:when test="starts-with($strip-prefix,'/')">
       <xsl:value-of select="$strip-prefix" />
      </xsl:when>
      <xsl:otherwise>
       <xsl:value-of select="concat('/',$strip-prefix)" />
      </xsl:otherwise>
     </xsl:choose>
    </xsl:variable>
    <!-- strip trailing slash from strip-prefix -->
    <xsl:variable name="tmpstrip2">
     <xsl:choose>
      <xsl:when test="substring($tmpstrip1,string-length($tmpstrip1),1)='/'">
       <xsl:value-of select="substring($tmpstrip1,1,string-length($tmpstrip1)-1)" />
      </xsl:when>
      <xsl:otherwise>
       <xsl:value-of select="$tmpstrip1" />
      </xsl:otherwise>
     </xsl:choose>
    </xsl:variable>
    <!-- filter on all entries within directory -->
    <xsl:for-each select="path[starts-with(concat(normalize-space(.),'/'),concat($tmpstrip2,'/'))]">
     <xsl:sort select="normalize-space(.)" data-type="text" />
     <!-- unless we are the first entry, add a comma -->
     <xsl:if test="not(position()=1)">
      <xsl:text>,&space;</xsl:text>
     </xsl:if>
     <!-- print the path name -->
     <xsl:call-template name="printpath">
      <xsl:with-param name="path" select="substring(normalize-space(.),string-length($strip-prefix)+3)" />
     </xsl:call-template>
    </xsl:for-each>
   </xsl:when>
   <!-- print a simple list of all paths -->
   <xsl:otherwise>
    <xsl:for-each select="path">
     <xsl:sort select="normalize-space(.)" data-type="text" />
     <!-- unless we are the first entry, add a comma -->
     <xsl:if test="not(position()=1)">
      <xsl:text>,&space;</xsl:text>
     </xsl:if>
     <!-- print the path name -->
     <xsl:value-of select="normalize-space(.)" />
    </xsl:for-each>
   </xsl:otherwise>
  </xsl:choose>
 </xsl:template>

 <!-- transform path to something printable -->
 <xsl:template name="printpath">
  <!-- fetch the pathname -->
  <xsl:param name="path" />
  <!-- strip leading slash -->
  <xsl:variable name="tmp1">
   <xsl:choose>
    <xsl:when test="starts-with($path,'/')">
     <xsl:value-of select="substring($path,2)" />
    </xsl:when>
    <xsl:otherwise>
     <xsl:value-of select="$path" />
    </xsl:otherwise>
   </xsl:choose>
  </xsl:variable>
  <!-- translate empty string to dot -->
  <xsl:choose>
   <xsl:when test="$tmp1 = ''">
    <xsl:text>.</xsl:text>
   </xsl:when>
   <xsl:otherwise>
    <xsl:value-of select="$tmp1" />
   </xsl:otherwise>
  </xsl:choose>
 </xsl:template>

 <!-- string-wrapping template -->
 <xsl:template name="wrap">
  <xsl:param name="txt" />
  <xsl:variable name="normtxt" select="normalize-space($txt)" />
  <xsl:choose>
   <xsl:when test="contains($txt,'&newl;')">
     <!-- text contains newlines, do the first line -->
     <xsl:call-template name="wrap">
      <xsl:with-param name="txt" select="substring-before($txt,'&newl;')" />
     </xsl:call-template>
     <!-- print tab -->
     <xsl:text>&tab;&space;&space;</xsl:text>
     <!-- wrap the rest of the text -->
     <xsl:call-template name="wrap">
      <xsl:with-param name="txt" select="substring-after($txt,'&newl;')" />
     </xsl:call-template>
   </xsl:when>
   <xsl:when test="(string-length($normtxt) &lt; (($linelen)-9)) or not(contains($normtxt,' '))">
    <!-- this is easy, nothing to do -->
    <xsl:value-of select="$normtxt" />
    <!-- add newline -->
    <xsl:text>&newl;</xsl:text>
   </xsl:when>
   <xsl:otherwise>
    <!-- find the first line -->
    <xsl:variable name="tmp" select="substring($normtxt,1,(($linelen)-9))" />
    <xsl:variable name="line">
     <xsl:choose>
      <!-- if our attempt contains spaces wrap on that -->
      <xsl:when test="contains($tmp,' ')">
       <xsl:call-template name="find-line">
        <xsl:with-param name="txt" select="$tmp" />
       </xsl:call-template>
      </xsl:when>
      <!-- otherwise use the first non-space characters from the text -->
      <xsl:otherwise>
       <xsl:value-of select="substring-before($normtxt,' ')" />
      </xsl:otherwise>
     </xsl:choose>
    </xsl:variable>
    <!-- print line -->
    <xsl:value-of select="$line" />
    <!-- print newline and tab -->
    <xsl:text>&newl;&tab;&space;&space;</xsl:text>
    <!-- wrap the rest of the text -->
    <xsl:call-template name="wrap">
     <xsl:with-param name="txt" select="normalize-space(substring($normtxt,string-length($line)+1))" />
    </xsl:call-template>
   </xsl:otherwise>
  </xsl:choose>
 </xsl:template>

 <!-- template to trim line to contain space as last char -->
 <xsl:template name="find-line">
  <xsl:param name="txt" />
  <xsl:choose>
   <xsl:when test="substring($txt,string-length($txt),1)=' '">
    <xsl:value-of select="substring($txt,1,string-length($txt)-1)" />
   </xsl:when>
   <xsl:otherwise>
    <xsl:call-template name="find-line">
     <xsl:with-param name="txt" select="substring($txt,1,string-length($txt)-1)" />
    </xsl:call-template>
   </xsl:otherwise>
  </xsl:choose>
 </xsl:template>

 <!-- template to trim trailing and starting newlines -->
 <xsl:template name="trim-newln">
  <xsl:param name="txt" />
  <xsl:choose>
   <!-- find starting newlines -->
   <xsl:when test="substring($txt,1,1) = '&newl;'">
    <xsl:call-template name="trim-newln">
     <xsl:with-param name="txt" select="substring($txt,2)" />
    </xsl:call-template>
   </xsl:when>
   <!-- find trailing newlines -->
   <xsl:when test="substring($txt,string-length($txt),1) = '&newl;'">
    <xsl:call-template name="trim-newln">
     <xsl:with-param name="txt" select="substring($txt,1,string-length($txt)-1)" />
    </xsl:call-template>
   </xsl:when>
   <!-- if the message has paragrapgs, find the first one -->
   <xsl:when test="$reparagraph='yes' and contains($txt,'&newl;&newl;')">
     <!-- remove newlines from first paragraph -->
     <xsl:value-of select="normalize-space(substring-before($txt,'&newl;&newl;'))" />
     <!-- paragraph separator -->
     <xsl:text>&newl;&newl;</xsl:text>
     <!-- do the rest of the text -->
     <xsl:call-template name="trim-newln">
      <xsl:with-param name="txt" select="substring-after($txt,'&newl;&newl;')" />
     </xsl:call-template>
   </xsl:when>
   <!-- remove more single newlines -->
   <xsl:when test="$reparagraph='yes'">
    <xsl:value-of select="normalize-space($txt)" />
   </xsl:when>
   <!-- no newlines found, we're done -->
   <xsl:otherwise>
    <xsl:value-of select="$txt" />
   </xsl:otherwise>
  </xsl:choose>
 </xsl:template>

 <!-- insert a number of newlines -->
 <xsl:template name="newlines">
  <xsl:param name="count" />
  <xsl:text>&newl;</xsl:text>
  <xsl:if test="$count&gt;1">
   <xsl:call-template name="newlines">
    <xsl:with-param name="count" select="($count)-1" />
   </xsl:call-template>
  </xsl:if>
 </xsl:template>

</xsl:stylesheet>
