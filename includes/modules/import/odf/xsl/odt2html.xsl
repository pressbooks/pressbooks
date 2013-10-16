<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0" xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" xmlns:anim="urn:oasis:names:tc:opendocument:xmlns:animation:1.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:math="http://www.w3.org/1998/Math/MathML" xmlns:xforms="http://www.w3.org/2002/xforms" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:smil="urn:oasis:names:tc:opendocument:xmlns:smil-compatible:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:ooow="http://openoffice.org/2004/writer" xmlns:oooc="http://openoffice.org/2004/calc" xmlns:int="http://opendocumentfellowship.org/internal" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" exclude-result-prefixes="office meta config text table draw presentation   dr3d chart form script style number anim dc xlink math xforms fo   svg smil ooo ooow oooc int #default">
	<xsl:variable name="lineBreak">
		<xsl:text>
		</xsl:text>
	</xsl:variable>
	<xsl:template match="/office:document">
		<xsl:apply-templates select="office:document-content"/> 
	</xsl:template>
	<xsl:template match="office:document-content">
		<html>
			<xsl:apply-templates select="office:body/office:text"/>
			<xsl:call-template name="add-footnote-bodies"/>
		</html>
	</xsl:template>
	<xsl:key name="tStyles" match="style:style" use="@style:name"/>
	<xsl:template match="text:p">       
		<p>
			<!-- check for children, usually spans with their own text:style-name -->
			<xsl:choose>
				<xsl:when test="*">
					<xsl:apply-templates/>  
				</xsl:when>
				<!-- if there are no children, then treat it with a paragraph style -->
				<xsl:otherwise>
					<!-- P1, P2, P3, etc-->
					<xsl:variable name="pClass">
						<xsl:value-of select="@text:style-name"/>
					</xsl:variable> 
                    
					<xsl:variable name="node" select="key('tStyles',$pClass)//*"/>

					<xsl:choose>
						<xsl:when test="$node/@fo:font-style = 'italic'">
							<i>
								<xsl:apply-templates/>
							</i>
						</xsl:when>

						<xsl:when test="$node/@fo:font-weight = 'bold'">
							<b>
								<xsl:apply-templates/>
							</b>
						</xsl:when>
						<xsl:when test="$node/@style:text-underline-style = 'solid'">
							<u>
								<xsl:apply-templates/>
							</u>
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates/>
						</xsl:otherwise>
					</xsl:choose>                               
				</xsl:otherwise>
			</xsl:choose>
            
			<xsl:if test="count(.)=0">
				<br/>
			</xsl:if>       
		</p>
	</xsl:template>
	<!-- generate a list of all the styles -->
	<xsl:template match="text:span">
		<!-- T1, T2, T3, etc-->
		<xsl:variable name="tClass">
			<xsl:value-of select="@text:style-name"/>
		</xsl:variable>
        
		<xsl:variable name="node" select="key('tStyles',$tClass)//*"/>
		<span>
			<xsl:choose>
				<xsl:when test="$node/@fo:font-style = 'italic'">
					<i>
						<xsl:apply-templates/>
					</i>
				</xsl:when>
                
				<xsl:when test="$node/@fo:font-weight = 'bold'">
					<b>
						<xsl:apply-templates/>
					</b>
				</xsl:when>
				<xsl:when test="$node/@style:text-underline-style = 'solid'">
					<u>
						<xsl:apply-templates/>
					</u>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates/>
				</xsl:otherwise>
            
			</xsl:choose>
            
		</span>
	</xsl:template> 

	<xsl:template match="text:h">
		<!-- Heading levels go only to 6 in XHTML -->
		<xsl:variable name="level">
			<xsl:choose>
				<!-- text:outline-level is optional, default is 1 -->
				<xsl:when test="not(@text:outline-level)">1</xsl:when>
				<xsl:when test="@text:outline-level &gt; 6">6</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@text:outline-level"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:element name="{concat('h', $level)}">
			<xsl:apply-templates/>
		</xsl:element>
	</xsl:template>
	<xsl:template match="text:tab">
		<xsl:text xml:space="preserve"> </xsl:text>
	</xsl:template>
	<xsl:template match="text:line-break">
		<br/>
	</xsl:template>
	<xsl:variable name="spaces" xml:space="preserve"/>
	<xsl:template match="text:s">
		<xsl:choose>
			<xsl:when test="@text:c">
				<xsl:call-template name="insert-spaces">
					<xsl:with-param name="n" select="@text:c"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text> </xsl:text>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template name="insert-spaces">
		<xsl:param name="n"/>
		<xsl:choose>
			<xsl:when test="$n &lt;= 30">
				<xsl:value-of select="substring($spaces, 1, $n)"/>
			</xsl:when>
 
			<xsl:otherwise>
				<xsl:value-of select="$spaces"/>
				<xsl:call-template name="insert-spaces">
					<xsl:with-param name="n">
						<xsl:value-of select="$n - 30"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="text:a">
		<a href="{@xlink:href}">
			<xsl:apply-templates/>
		</a>
	</xsl:template>
	<xsl:template match="text:bookmark-start|text:bookmark">
		<a name="{@text:name}">
			<span style="font-size: 0px">
				<xsl:text> </xsl:text>
			</span>
		</a>
	</xsl:template>
	<xsl:template match="text:note">
		<xsl:variable name="footnote-id" select="text:note-citation"/>
		<a href="#footnote-{$footnote-id}">
			<sup>
				<xsl:value-of select="$footnote-id"/>
			</sup>
		</a>
	</xsl:template>
	<xsl:template match="text:note-body"/>
	<xsl:template name="add-footnote-bodies">
		<xsl:apply-templates select="//text:note" mode="add-footnote-bodies"/>
	</xsl:template>
	<xsl:template match="text:note" mode="add-footnote-bodies">
		<xsl:variable name="footnote-id" select="text:note-citation"/>
		<p>
			<a name="footnote-{$footnote-id}">
				<sup>
					<xsl:value-of select="$footnote-id"/>
				</sup>:</a>
		</p>
		<xsl:apply-templates select="text:note-body/*"/>
	</xsl:template>

	<xsl:template match="table:table">
		<table>
			<colgroup>
				<xsl:apply-templates select="table:table-column"/>
			</colgroup>
			<xsl:if test="table:table-header-rows/table:table-row">
				<thead>
					<xsl:apply-templates select="table:table-header-rows/table:table-row"/>
				</thead>
			</xsl:if>
			<tbody>
				<xsl:apply-templates select="table:table-row"/>
			</tbody>
		</table>
	</xsl:template>
	<xsl:template match="table:table-column">
		<col>
			<xsl:if test="@table:number-columns-repeated">
				<xsl:attribute name="span">
					<xsl:value-of select="@table:number-columns-repeated"/>
				</xsl:attribute>
			</xsl:if>
		</col>
	</xsl:template>
	<xsl:template match="table:table-row">
		<tr>
			<xsl:apply-templates select="table:table-cell"/>
		</tr>
	</xsl:template>
	<xsl:template match="table:table-cell">
		<xsl:variable name="n">
			<xsl:choose>
				<xsl:when test="@table:number-columns-repeated != 0">
					<xsl:value-of select="@table:number-columns-repeated"/>
				</xsl:when>
				<xsl:otherwise>1</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:call-template name="process-table-cell">
			<xsl:with-param name="n" select="$n"/>
		</xsl:call-template>
	</xsl:template>
	<xsl:template name="process-table-cell">
		<xsl:param name="n"/>
		<xsl:if test="$n != 0">
			<td>
				<xsl:if test="@table:number-columns-spanned">
					<xsl:attribute name="colspan">
						<xsl:value-of select="@table:number-columns-spanned"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:if test="@table:number-rows-spanned">
					<xsl:attribute name="rowspan">
						<xsl:value-of select="@table:number-rows-spanned"/>
					</xsl:attribute>
				</xsl:if>
				<xsl:apply-templates/>
			</td>
			<xsl:call-template name="process-table-cell">
				<xsl:with-param name="n" select="$n - 1"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>
	<!-- either L1 (ordered list) or L2 (unordered list) -->
	<xsl:key name="listTypes" match="text:list-style" use="@style:name"/>
	<xsl:template match="text:list">
		<!-- just an integer 1,2,3,etc -->
		<xsl:variable name="level" select="count(ancestor::text:list)+1"/>
 
		<!-- the list class is the @text:style-name of the outermost
		<text:list> element -->
		<xsl:variable name="listClass">
			<xsl:choose>
				<xsl:when test="$level=1">
					<xsl:value-of select="@text:style-name"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="ancestor::text:list[last()]/@text:style-name"/>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
 
		<!-- Now select the <text:list-level-style-foo> element at this
		level of nesting for this list -->
		<xsl:variable name="node" select="key('listTypes',$listClass)//*"/>
		<!-- emit appropriate list type -->
		<xsl:choose>
			<xsl:when test="boolean(local-name($node) = 'list-level-style-number')">
				<ol>
					<xsl:apply-templates/>
				</ol>
			</xsl:when>
			<xsl:otherwise>
				<ul>
					<xsl:apply-templates/>
				</ul>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="text:list-item">
		<li>
			<xsl:apply-templates/>
		</li>
	</xsl:template>
	<xsl:template match="office:change-info"/>
	<xsl:param name="param_baseuri"/>
	<xsl:template match="draw:frame">
		<xsl:element name="div">
			<xsl:apply-templates/>
		</xsl:element>
	</xsl:template>
	<xsl:template match="draw:frame/draw:image">
		<xsl:element name="img">
			<xsl:attribute name="alt">
				<xsl:value-of select="../svg:desc"/>
			</xsl:attribute>
			<xsl:attribute name="src">
				<xsl:value-of select="concat($param_baseuri,@xlink:href)"/>
			</xsl:attribute>
		</xsl:element>
	</xsl:template>
	<xsl:template match="svg:desc"/>
</xsl:stylesheet>