<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<!-- 
v. 0.4, Dec 2009

This script is copyright 2009 by John W. Maxwell, Meghan MacDonald, 
and Travis Nicholson at Simon Fraser University's Master of Publishing
program.

Our intent is that this script be free licensed; you are hereby free to
use, study, modify, share, and redistribute this software as needed. 
This script would be GNU GPL-licensed, except that small parts of it come 
directly from Adobe's excellent IDML Cookbook and SDK and so aren't ours
to license. That said, the point of the thing is educational, so go to it.
See also http://www.adobe.com/devnet/indesign/

This script is not meant to be comprehensive or perfect. It was written
and tested in the context of the CCSP's Book Publishing 1 title, and content
from out ZWiki-based webCM system. To make it work with your content, you
will probably need to make modifications. That said, it is a working 
proof-of-concept and a foundation for further work. - JMax June 5, 2009.

CHANGES
===========
v0.2 - JMax: Nov 2009. Tweaks to make this work with TinyMCE's content rather than the HTML that ZWiki's ReStructured Text creates.
v0.2.5 - Meghan: Dec 2009. Added handlers for crude p-level metadata
v0.3 - JMax: merged 0.2 and 0.25, tweaked support for "a" links
v0.4 - Keith Fahlgren: Refactored XSLT for clarity, organization, and extensibility; added support for hyperlinks
v0.4.3 - John's minor tweaks anf bugfixes: start para, ignored para, etc. some image-handling hacks
-->
<xsl:stylesheet xmlns:xhtml="http://www.w3.org/1999/xhtml" 
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
                exclude-result-prefixes="xhtml"
                version="1.0">

  <xsl:param name="table-width">540</xsl:param>

  <!-- Fixed strings used to indicate ICML and software version -->
  <xsl:variable name="icml-decl-pi">
    <xsl:text>style="50" type="snippet" readerVersion="6.0" featureSet="257" product="6.0(352)"</xsl:text> <!-- product string will change with specific InDesign builds (but probably doesn't matter) -->
  </xsl:variable>  
  <xsl:variable name="snippet-type-pi">
    <xsl:text>SnippetType="InCopyInterchange"</xsl:text>
  </xsl:variable>  


  <!-- Default Rule: Match everything, ignore it,  and keep going "down". -->
  <xsl:template match="@*|node()">
    <xsl:apply-templates select="@*|node()"/>
  </xsl:template>

  <!-- ==================================================================== -->
  <!-- Document root generation and boilerplate. -->
  <!-- ==================================================================== -->
  <xsl:template match="xhtml:body">
    <xsl:processing-instruction name="aid"><xsl:value-of select="$icml-decl-pi"/></xsl:processing-instruction>
    <xsl:processing-instruction name="aid"><xsl:value-of select="$snippet-type-pi"/></xsl:processing-instruction>
    <Document DOMVersion="6.0" Self="xhtml2icml_document">
      <RootCharacterStyleGroup Self="xhtml2icml_character_styles">
        <CharacterStyle Self="CharacterStyle/link" Name="link"/>
        <CharacterStyle Self="CharacterStyle/i" Name="i"/>
        <CharacterStyle Self="CharacterStyle/b" Name="b"/>
        <!-- Generate the rest of the CharacterStyles using the @class value -->
        <xsl:apply-templates select="//xhtml:span[@class]" mode='character-style'/>
      </RootCharacterStyleGroup>
      <RootParagraphStyleGroup Self="xhtml2icml_paragraph_styles">
        <ParagraphStyle Self="ParagraphStyle/h1" Name="h1"/>
        <ParagraphStyle Self="ParagraphStyle/h2" Name="h2"/>
        <ParagraphStyle Self="ParagraphStyle/h3" Name="h3"/>
        <ParagraphStyle Self="ParagraphStyle/h4" Name="h4"/>
        <ParagraphStyle Self="ParagraphStyle/h5" Name="h5"/>
        <ParagraphStyle Self="ParagraphStyle/h6" Name="h6"/>
        <ParagraphStyle Self="ParagraphStyle/p" Name="p"/>
        <ParagraphStyle Self="ParagraphStyle/pFollowsP" Name="pFollowsP"/>
        <ParagraphStyle Self="ParagraphStyle/ul" Name="ul"/>
        <ParagraphStyle Self="ParagraphStyle/ol" Name="ol"/>
        <ParagraphStyle Self="ParagraphStyle/table" Name="table"/>
        <ParagraphStyle Self="ParagraphStyle/quote" Name="quote"/>
        <ParagraphStyle Self="ParagraphStyle/footnote" Name="footnote"/>
        <!-- Generate the rest of the ParagraphStyles using the @class value -->
        <xsl:apply-templates select="//xhtml:p[@class]" mode='paragraph-style'/>
      </RootParagraphStyleGroup>
      <Story Self="xhtml2icml_default_story" AppliedTOCStyle="n" TrackChanges="false" StoryTitle="MyStory" AppliedNamedGrid="n">
        <StoryPreference OpticalMarginAlignment="false" OpticalMarginSize="12" FrameType="TextFrameType" StoryOrientation="Horizontal" StoryDirection="LeftToRightDirection"/>
        <InCopyExportOption IncludeGraphicProxies="true" IncludeAllResources="false"/>
        <xsl:apply-templates/>
      </Story>
      <xsl:apply-templates select=".//xhtml:a" mode="hyperlink-url-destinations"/>
      <xsl:apply-templates select=".//xhtml:a" mode="hyperlinks"/>
    </Document>
  </xsl:template>

  <!-- Headings -->
  <xsl:template match="xhtml:h1|
                       xhtml:h2|
                       xhtml:h3|
                       xhtml:h4|
                       xhtml:h5|
                       xhtml:h6">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name" select="name()"/>
    </xsl:call-template>
  </xsl:template>


  <!-- ==================================================================== -->
  <!-- Paras and block-level elements -->
  <!-- ==================================================================== -->

  <!-- == Initial-vs-subsequent paragraph treatment == 
       The more I think about it (and muse on Bringhurst's elegant simplicity), 
       the more I think that the right way to treat this is to say that the 
       default paragraph treatment should use the 'p' style and the 
       *special case* is a "normal" (@class-less) paragraph that immediately 
       follows another normal paragraph. Use the 'pFollowsP' for that case. -->

  <!-- Normal initial paragraphs -->
  <xsl:template match="xhtml:p[not(@class)]">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">p</xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <!-- Paragraphs that follow paragraphs -->
  <xsl:template match="xhtml:p[not(@class)]
                              [preceding-sibling::*[1][self::xhtml:p[not(@class)] or @class='start']] 
                              ">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">pFollowsP</xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <!-- Dynamically-style-named paragraphs -->
  <xsl:template match="xhtml:p[@class and not(starts-with(@class, 'dc-'))]">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name" select="@class"/>
    </xsl:call-template>
  </xsl:template>

  <!-- Document metadata paragraphs have a @class starting with 'dc-' -->
  <xsl:template match="xhtml:p[@class='dc-creator']">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">author</xsl:with-param>
      <xsl:with-param name="prefix-content">by </xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <!-- Quotes (also available from <p class="quote">) -->
  <xsl:template match="xhtml:blockquote/xhtml:p">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">quote</xsl:with-param>
    </xsl:call-template>
  </xsl:template>    

  <!-- p class = 'ignore': strip out entirely -->
  <xsl:template match="xhtml:p[@class='ignore']">
  </xsl:template>    

  <!-- ==================================================================== -->
  <!-- Lists -->
  <!-- ==================================================================== -->
  <!-- TODO: What about other children of the li? What about multiple children
       on a single li? -->
<!--  <xsl:template match="xhtml:ol/xhtml:li[*]|
                       xhtml:ul/xhtml:li[*]">
    <xsl:if test="count(*) &gt; 1">
      <xsl:message terminate="yes">Multi-element list items not handled!
</xsl:message>
    </xsl:if>
    <xsl:if test="*[not(self::xhtml:p)]">
      <xsl:message terminate="yes">Non-paragraph list children not handled!
</xsl:message>
    </xsl:if>
    <xsl:apply-templates/>
  </xsl:template> -->
  <xsl:template match="xhtml:ol/xhtml:li/xhtml:p|
                       xhtml:ol/xhtml:li[not(*)]">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">ol</xsl:with-param>
    </xsl:call-template>
  </xsl:template>
  <xsl:template match="xhtml:ul/xhtml:li/xhtml:p|
                       xhtml:ul/xhtml:li[not(*)]">
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">ul</xsl:with-param>
    </xsl:call-template>
  </xsl:template>


  <!-- ==================================================================== -->
  <!-- Tables -->
  <!-- ==================================================================== -->

  <!-- TODO: 
         Allow for tables with less than 3 rows
         Make code more idiomatic XSLT (even if functionality isn't increased)
         -->
  <xsl:template match="xhtml:table[@class='docutils']/xhtml:tbody">
    <ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/table">
      <CharacterStyleRange>
        <Br/>
        <Table HeaderRowCount="0" FooterRowCount="0" AppliedTableStyle="TableStyle/$ID/[Basic Table]" TableDirection="LeftToRightDirection">
          <xsl:attribute name="BodyRowCount">
            <xsl:value-of select="count(child::xhtml:tr)"/>
          </xsl:attribute>
          <xsl:attribute name="ColumnCount">
            <xsl:value-of select="count(child::xhtml:tr[3]/xhtml:td)"/>
          </xsl:attribute>
          <xsl:variable name="columnWidth" select="$table-width div count(xhtml:tr[3]/xhtml:td)"/>
          <xsl:for-each select="xhtml:tr[3]/xhtml:td">
            <Column Name="{position() - 1}" SingleColumnWidth="{$columnWidth}"/>
          </xsl:for-each>
          <xsl:for-each select="xhtml:tr">
            <xsl:variable name="rowNum" select="position() - 1"/>
            <xsl:for-each select="xhtml:td">
              <xsl:variable name="colNum" select="position() - 1"/>
              <xsl:choose>
                <xsl:when test="@colspan">
                  <Cell Name="{$colNum}:{$rowNum}" RowSpan="1" ColumnSpan="{@colspan}" AppliedCellStyle="CellStyle/$ID/[None]" AppliedCellStylePriority="0">
                    <ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/$ID/NormalParagraphStyle">
                      <CharacterStyleRange AppliedCharacterStyle="CharacterStyle/$ID/[No character style]">
                        <Content>
                          <xsl:value-of select="*|text()"/>
                        </Content>
                      </CharacterStyleRange>
                    </ParagraphStyleRange>
                  </Cell>
                </xsl:when>
                <xsl:otherwise>
                  <Cell Name="{$colNum}:{$rowNum}" RowSpan="1" ColumnSpan="1" AppliedCellStyle="CellStyle/$ID/[None]" AppliedCellStylePriority="0">
                    <ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/$ID/NormalParagraphStyle">
                      <CharacterStyleRange AppliedCharacterStyle="CharacterStyle/$ID/[No character style]">
                        <Content>
                          <xsl:value-of select="*|text()"/>
                        </Content>
                      </CharacterStyleRange>
                    </ParagraphStyleRange>
                  </Cell>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:for-each>
          </xsl:for-each>
        </Table>
      </CharacterStyleRange>
    </ParagraphStyleRange>
  </xsl:template>

  <xsl:template match="xhtml:tr">
    <xsl:if test="position() &gt; 2">
      <Br/>
    </xsl:if>
  </xsl:template>

  <xsl:template match="xhtml:td">
    <xsl:if test="position() &gt; 2">
      <Br/>
    </xsl:if>
  </xsl:template>

  <!-- ==================================================================== -->
  <!-- Images -->
  <!-- ==================================================================== -->
  <xsl:template match="xhtml:img">
    <xsl:variable name="halfwidth" select="@width div 4"/>
    <xsl:variable name="halfheight" select="@height div 4"/>
    <ParagraphStyleRange>
      <CharacterStyleRange>
        <Rectangle Self="uec" ItemTransform="1 0 0 1 {$halfwidth} -{$halfheight}">
          <Properties>
            <PathGeometry>
              <GeometryPathType PathOpen="false">
                <PathPointArray>
                  <PathPointType Anchor="-{$halfwidth} -{$halfheight}" 
                                 LeftDirection="-{$halfwidth} -{$halfheight}" 
                                 RightDirection="-{$halfwidth} -{$halfheight}"/>
                  <PathPointType Anchor="-{$halfwidth} {$halfheight}" 
                                 LeftDirection="-{$halfwidth} {$halfheight}" 
                                 RightDirection="-{$halfwidth} {$halfheight}"/>
                  <PathPointType Anchor="{$halfwidth} {$halfheight}" 
                                 LeftDirection="{$halfwidth} {$halfheight}" 
                                 RightDirection="{$halfwidth} {$halfheight}"/>
                  <PathPointType Anchor="{$halfwidth} -{$halfheight}" 
                                 LeftDirection="{$halfwidth} -{$halfheight}" 
                                 RightDirection="{$halfwidth} -{$halfheight}"/>
                </PathPointArray>
              </GeometryPathType>
            </PathGeometry>
          </Properties>
          <Image Self="ue6" ItemTransform="1 0 0 1 -{$halfwidth} -{$halfheight}">
            <Properties>
              <Profile type="string">$ID/Embedded</Profile>
              <GraphicBounds Left="0" Top="0" Right="{@width div 2}" Bottom="{@height div 2}"/>
            </Properties>
            <Link Self="ueb" LinkResourceURI="file:///{@src}"/>
          </Image>
        </Rectangle>
        <Br/>
      </CharacterStyleRange>
    </ParagraphStyleRange>
  </xsl:template>

  <!-- ==================================================================== -->
  <!-- Links -->
  <!-- ==================================================================== -->
  <xsl:template match="xhtml:a" mode="character-style-range">
    <xsl:variable name="hyperlink-key" select="count(preceding::xhtml:a) + 1"/>
    <xsl:variable name="self" select="concat('htss-', $hyperlink-key)"/>
    <xsl:variable name="name" select="."/>
    <CharacterStyleRange>
      <xsl:attribute name="AppliedCharacterStyle">CharacterStyle/link</xsl:attribute> 
      <HyperlinkTextSource Self="{$self}" Name="{$name}" Hidden="false">
        <Content><xsl:value-of select="."/></Content>
      </HyperlinkTextSource>  
    </CharacterStyleRange>
  </xsl:template>  

  <!-- TODO: Add support for internal hyperlinks -->
  <xsl:template match="xhtml:a[not(@href)]" mode="hyperlinks"/>
  <xsl:template match="xhtml:a[not(@href)]" mode="hyperlink-url-destinations"/>

  <xsl:template match="xhtml:a[@href]" mode="hyperlink-url-destinations">
    <xsl:variable name="hyperlink-key" select="count(preceding::xhtml:a) + 1"/>
    <xsl:variable name="hyperlink-text-source-self" select="concat('htss-', $hyperlink-key)"/>
    <xsl:variable name="hyperlink-url-destination-self" select="concat('huds-', $hyperlink-key)"/>
    <xsl:variable name="hyperlink-text-source-name" select="."/>
    <xsl:variable name="destination-unique-key" select="$hyperlink-key"/>
    <HyperlinkURLDestination Self="{$hyperlink-url-destination-self}" 
                             Name="{$hyperlink-text-source-name}"
                             DestinationURL="{@href}" 
                             DestinationUniqueKey="{$destination-unique-key}"/> 
  </xsl:template>  

  <xsl:template match="xhtml:a[@href]" mode="hyperlinks">
    <xsl:variable name="hyperlink-key" select="count(preceding::xhtml:a) + 1"/>
    <xsl:variable name="hyperlink-self" select="concat('hs-', $hyperlink-key)"/>
    <xsl:variable name="hyperlink-url-destination-self" select="concat('huds-', $hyperlink-key)"/>
    <xsl:variable name="hyperlink-text-source-self" select="concat('htss-', $hyperlink-key)"/>
    <xsl:variable name="hyperlink-text-source-name" select="."/>
    <xsl:variable name="destination-unique-key" select="$hyperlink-key"/>
    <Hyperlink Self="{$hyperlink-self}" 
               Name="{$hyperlink-text-source-name}" 
               Source="{$hyperlink-text-source-self}" 
               Visible="true" 
               DestinationUniqueKey="{$destination-unique-key}">
      <Properties>
        <BorderColor type="enumeration">Black</BorderColor>
        <Destination type="object"><xsl:value-of select="$hyperlink-url-destination-self"/></Destination>
      </Properties>
    </Hyperlink>
  </xsl:template>  


  <!-- ==================================================================== -->
  <!-- Inlines -->
  <!-- ==================================================================== -->
  <xsl:template match="xhtml:em|xhtml:i" mode="character-style-range">
    <xsl:call-template name="char-style-range">
      <xsl:with-param name="style-name">i</xsl:with-param>
    </xsl:call-template>
  </xsl:template>  

  <xsl:template match="xhtml:strong|xhtml:b" mode="character-style-range">
    <xsl:call-template name="char-style-range">
      <xsl:with-param name="style-name">b</xsl:with-param>
    </xsl:call-template>
  </xsl:template>  

  <xsl:template match="xhtml:span[@class]" mode="character-style-range">
    <xsl:call-template name="char-style-range">
      <xsl:with-param name="style-name" select="@class"/>
    </xsl:call-template>
  </xsl:template>  

  <xsl:template match="text()" mode="character-style-range">
    <xsl:call-template name="char-style-range">
      <xsl:with-param name="style-name">[No character style]</xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="xhtml:br" mode="character-style-range">
    <Br/> <!-- TODO: Is this always going to appear in an acceptable location? -->
  </xsl:template>

  <xsl:template match="xhtml:sub" mode="character-style-range">
    <xsl:call-template name="char-style-range">
      <xsl:with-param name="style-name">[No character style]</xsl:with-param>
      <xsl:with-param name="vertical-position">Subscript</xsl:with-param>
    </xsl:call-template>
  </xsl:template>  

  <xsl:template match="xhtml:sup" mode="character-style-range">
    <xsl:call-template name="char-style-range">
      <xsl:with-param name="style-name">[No character style]</xsl:with-param>
      <xsl:with-param name="vertical-position">Superscript</xsl:with-param>
    </xsl:call-template>
  </xsl:template>  


  <!-- ==================================================================== -->
  <!-- Footnotes -->
  <!-- ==================================================================== -->

       
  <!-- Ignore the target anchors in the footnote "body" and insert the
       footnote markup at the point in the text where the superscripted/boxed
       footnote anchor appears. 

       Additionally, we must ignore the footnoe content paragraphs where they
       actually appear in the document. -->

  <!-- == OpenOffice.org footnotes == -->

  <!-- The paragraphs that contain the footnotes at the end of the document
       should be ignored in the default mode, as above. -->
  <xsl:template match="xhtml:*[xhtml:a[contains(@name, 'sdfootnote') or 
                                       contains(@name, 'sdendnote')]]" 
                priority="1"/>

  <!-- The second paragraphs of two-paragraph footnotes should be ignored as well. -->
  <xsl:template match="xhtml:*[not(xhtml:a[contains(@name, 'sdfootnote') or 
                                            contains(@name, 'sdendnote')])]
                              [preceding-sibling::*[1]
                                                   [xhtml:a[contains(@name, 'sdfootnote') or 
                                                            contains(@name, 'sdendnote')]]]" 
                priority="1"/>

  <!-- The third paragraphs of multi-paragraph footnotes should generate a
       warning. This XPath is horrific, sorry. 

       Find <p>s that do not have a footnote marker and whose immediate
       predecessors also do not have a footnote marker but that are immediately 
       preceded by an element that DOES have a footnote marker. This will 
       always match the third <p> that follows a "normal" start of footnote 
       paragraph. -->
  <xsl:template match="xhtml:p[not(xhtml:a[contains(@name, 'sdfootnote') or 
                                           contains(@name, 'sdendnote')])]
                              [preceding-sibling::*[1]
                                                   [not(xhtml:a[contains(@name, 'sdfootnote') or 
                                                                contains(@name, 'sdendnote')])]]
                              [preceding-sibling::*[2]
                                                   [xhtml:a[contains(@name, 'sdfootnote') or 
                                                            contains(@name, 'sdendnote')]]]"
                priority="1">
  <xsl:message>WARNING: Footnotes with more than 2 paragraphs are not supported. Extra paragraphs will appear at the end of the document!
Problematic text starts with: <xsl:value-of select="."/> 
</xsl:message>
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">p</xsl:with-param>
    </xsl:call-template>
  </xsl:template>              


  <!-- The hardcoded anchors that used to link the footnotes together should
       also be omitted, as InDesign will generate auto-numbered foonote Markers. -->
  <xsl:template match="xhtml:*/xhtml:a[contains(@name, 'sdfootnote') or
                                       contains(@name, 'sdendnote')]"  
                mode="character-style-range"/>

  <xsl:template match="xhtml:sup[xhtml:a[contains(@name, 'sdfootnote') or
                                         contains(@name, 'sdendnote')]]" 
                mode="character-style-range">
    <xsl:variable name="marker-name" select="xhtml:a[contains(@name, 'sdfootnote') or
                                                     contains(@name, 'sdendnote')]/@name"/>
    <xsl:variable name="target" select="concat('#', $marker-name)"/>
    <xsl:call-template name="process-footnote">
      <xsl:with-param name="content">
        <xsl:apply-templates select="//xhtml:*[xhtml:a[@href = $target]]" 
                             mode="character-style-range"/>

        <!-- Check if there are extra paragraphs hanging around after this one -->
        <xsl:if test="//xhtml:*[not(xhtml:a[contains(@name, 'sdfootnote') or
                                            contains(@name, 'sdendnote')])]
                               [preceding-sibling::*[1]
                                                    [xhtml:a[@href = $target]]]">
          <!-- This is how we fake InDesign into separating "multi-paragraph"
               footnotes (they are actually one paragraph). -->
          <CharacterStyleRange>
            <Br/>
          </CharacterStyleRange>
          <!-- The content from the second paragraph itself is matched. Note
               that any more than two paragraphs is unsupported. -->
          <xsl:apply-templates select="//xhtml:*[not(xhtml:a[contains(@name, 'sdfootnote') or
                                                             contains(@name, 'sdendnote')])]
                                                [preceding-sibling::*[1]
                                                                     [xhtml:a[@href = $target]]]"
                               mode="character-style-range"/>
        </xsl:if>                                                             
      </xsl:with-param>  
    </xsl:call-template>
  </xsl:template>  


  <!-- == Word footnotes == -->
  <!-- The paragraphs that contain the footnotes at the end of the document
       should be ignored in the default mode, as above. -->
  <xsl:template match="xhtml:*[xhtml:a[contains(@href, '#_ftnref')]]" 
                priority="1"/>

  <!-- The second paragraphs of two-paragraph footnotes should be ignored as well. -->
  <xsl:template match="xhtml:*[not(xhtml:a[contains(@href, '#_ftnref')])]
                              [preceding-sibling::*[1]
                                                   [xhtml:a[contains(@href, '#_ftnref')]]]" 
                priority="1"/>

  <!-- The third paragraphs of multi-paragraph footnotes should warn. See
       above for explanation of this horrific XPath. -->
  <xsl:template match="xhtml:p[not(xhtml:a[contains(@href, '#_ftnref')])]
                              [preceding-sibling::*[1]
                                                   [not(xhtml:a[contains(@href, '#_ftnref')])]]
                              [preceding-sibling::*[2]
                                                   [xhtml:a[contains(@href, '#_ftnref')]]]"
                priority="1">
  <xsl:message>WARNING: Footnotes with more than 2 paragraphs are not supported. Extra paragraphs will appear at the end of the document!
Problematic text starts with: <xsl:value-of select="."/> 
</xsl:message>
    <xsl:call-template name="para-style-range">
      <xsl:with-param name="style-name">p</xsl:with-param>
    </xsl:call-template>
  </xsl:template>              

  <!-- The hardcoded anchors that used to link the footnotes together should
       also be omitted, as InDesign will generate auto-numbered foonote Markers. -->
  <xsl:template match="xhtml:*/xhtml:a[contains(@href, '#_ftnref')]"
                mode="character-style-range"/>

  <xsl:template match="xhtml:a[contains(@id, '_ftnref')]" 
                mode="character-style-range">
    <xsl:variable name="marker-name" select="@id"/>
    <xsl:variable name="target" select="concat('#', $marker-name)"/>
    <xsl:call-template name="process-footnote">
      <xsl:with-param name="content">
        <xsl:apply-templates select="//xhtml:*[xhtml:a[@href = $target]]" 
                             mode="character-style-range"/>
        <!-- Check if there are extra paragraphs hanging around after this one -->
        <xsl:if test="//xhtml:*[not(xhtml:a[contains(@href, '#_ftnref')])]
                               [preceding-sibling::*[1]
                                                    [xhtml:a[@href = $target]]]">
          <!-- This is how we fake InDesign into separating "multi-paragraph"
               footnotes (they are actually one paragraph). -->
          <CharacterStyleRange>
            <Br/>
          </CharacterStyleRange>
          <xsl:apply-templates select="//xhtml:*[not(xhtml:a[contains(@href, '#_ftnref')])]
                                                [preceding-sibling::*[1]
                                                                     [xhtml:a[@href = $target]]]"
                               mode="character-style-range"/>
        </xsl:if>

      </xsl:with-param>  
    </xsl:call-template>
  </xsl:template>  

  <!-- == Docutils footnotes (legacy) == -->
  <xsl:template match="xhtml:table[@class='docutils footnote']/xhtml:tbody/xhtml:tr">
    <ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/footnote">
      <xsl:for-each select="xhtml:td">
        <xsl:choose>
          <xsl:when test="self::xhtml:td[@class='label']">
            <CharacterStyleRange>
              <Content><xsl:value-of select="substring-before(substring-after(.,'['),']')"/>. </Content>
            </CharacterStyleRange>
          </xsl:when>
          <xsl:otherwise>
            <Content>
              <xsl:value-of select="."/>
            </Content>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:for-each>
    </ParagraphStyleRange>
    <Br/>
  </xsl:template>
  <xsl:template match="xhtml:div[@class='footnotes']/xhtml:p">
    <ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/footnote">
      <CharacterStyleRange>
        <Content>
          <xsl:value-of select="."/>
        </Content>
        <Br/>
      </CharacterStyleRange>
    </ParagraphStyleRange>
  </xsl:template>

    
  <!-- ==================================================================== -->
  <!-- StyleGroup boilerplate -->
  <!-- ==================================================================== -->

  <!-- Grab only the first instance of each @class -->
  <xsl:template match="xhtml:p[@class]"
                mode="paragraph-style">
    <xsl:variable name="c" select="@class"/>
    <xsl:if test="not(following::xhtml:p[@class = $c])">
      <xsl:choose>
        <xsl:when test="@class='quote'">
          <!-- Ignore; already hardcoded because of <blockquote> -->
        </xsl:when>
        <xsl:when test="@class='dc-creator'">
          <xsl:call-template name="generate-paragraph-style">
            <xsl:with-param name="style-name">author</xsl:with-param>
          </xsl:call-template>
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="generate-paragraph-style">
            <xsl:with-param name="style-name" select="@class"/>
          </xsl:call-template>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
  </xsl:template>    

  <xsl:template match="xhtml:span[@class]"
                mode="character-style">
    <xsl:variable name="c" select="@class"/>
    <xsl:if test="not(following::xhtml:span[@class = $c])">
      <xsl:call-template name="generate-character-style">
        <xsl:with-param name="style-name" select="@class"/>
      </xsl:call-template>
    </xsl:if>
  </xsl:template>    


  <!-- ==================================================================== -->
  <!-- Named templates -->
  <!-- ==================================================================== -->
  <xsl:template name="para-style-range">
    <!-- The name of the paragraph style in InDesign -->
    <xsl:param name="style-name"/> 
    <!-- A string of text that will precede the paragraph's actual content (ex: 'by ')-->
    <xsl:param name="prefix-content" select="''"/>
    <ParagraphStyleRange>
      <xsl:attribute name="AppliedParagraphStyle">
        <xsl:value-of select="concat('ParagraphStyle/', $style-name)"/>
      </xsl:attribute> 
      <xsl:if test="$prefix-content != ''">
        <CharacterStyleRange>
          <Content><xsl:value-of select="$prefix-content"/></Content>
        </CharacterStyleRange>  
      </xsl:if>
      <xsl:apply-templates select="text()|*" mode="character-style-range"/>
      <Br/>
    </ParagraphStyleRange>
  </xsl:template>


  <xsl:template name="char-style-range">
    <!-- The name of the character style in InDesign -->
    <xsl:param name="style-name"/> 
    <xsl:param name="vertical-position" select="0"/> 

    <CharacterStyleRange>
      <xsl:attribute name="AppliedCharacterStyle">
        <xsl:value-of select="concat('CharacterStyle/', $style-name)"/>
      </xsl:attribute> 
      <xsl:if test="$vertical-position != 0">
        <xsl:attribute name="Position">
          <xsl:value-of select="$vertical-position"/>
        </xsl:attribute>
      </xsl:if>
      <Content><xsl:value-of select="."/></Content>
    </CharacterStyleRange>  
  </xsl:template>

  <xsl:template name="process-footnote">
    <xsl:param name="content"/>
    <CharacterStyleRange AppliedCharacterStyle="CharacterStyle/$ID/[No character style]" 
                         Position="Superscript">
      <Footnote>
        <ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/footnote">
          <CharacterStyleRange AppliedCharacterStyle="CharacterStyle/$ID/[No character style]">
            <!-- InDesign magical footnote character -->
            <Content><xsl:processing-instruction name="ACE">4</xsl:processing-instruction></Content>
          </CharacterStyleRange>
          <xsl:copy-of select="$content"/>
        </ParagraphStyleRange>
      </Footnote>
    </CharacterStyleRange>  
  </xsl:template>

  <xsl:template name="generate-paragraph-style">
    <xsl:param name="style-name"/>
    <ParagraphStyle>
      <xsl:attribute name="Self"><xsl:value-of select="concat('ParagraphStyle/', $style-name)"/></xsl:attribute> 
      <xsl:attribute name="Name"><xsl:value-of select="$style-name"/></xsl:attribute>
    </ParagraphStyle>
  </xsl:template>

  <xsl:template name="generate-character-style">
    <xsl:param name="style-name"/>
    <CharacterStyle>
      <xsl:attribute name="Self"><xsl:value-of select="concat('CharacterStyle/', $style-name)"/></xsl:attribute> 
      <xsl:attribute name="Name"><xsl:value-of select="$style-name"/></xsl:attribute>
    </CharacterStyle>
  </xsl:template>

</xsl:stylesheet>
