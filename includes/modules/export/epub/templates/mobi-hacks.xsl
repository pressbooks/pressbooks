<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:strip-space elements="blockquote div p ul ol li" />
	<xsl:output encoding="UTF-8" indent="yes" method="xml" omit-xml-declaration="yes" />

	<!-- The identity template -->
	<xsl:template match="@*|node()">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()" />
		</xsl:copy>
	</xsl:template>

	<!-- Remove <html> nodes -->
	<xsl:template match="html">
		<xsl:apply-templates />
	</xsl:template>

	<!-- Change <aside> to <div class="aside"> -->
	<!--
    <xsl:template match="aside">
      <div class="aside">
        <xsl:apply-templates />
      </div>
    </xsl:template>
    -->

	<!-- Change <del> to <span class="strike"> -->
	<xsl:template match="del">
		<span class="strike">
			<xsl:apply-templates />
		</span>
	</xsl:template>

	<!-- Change <blockquote> to <div class="blockquote"> -->
	<xsl:template match="blockquote">
		<div>
			<xsl:choose>
				<xsl:when test="@class">
					<xsl:attribute name="class">blockquote <xsl:value-of select="@class" /></xsl:attribute>
				</xsl:when>
				<xsl:otherwise>
					<xsl:attribute name="class">blockquote</xsl:attribute>
				</xsl:otherwise>
			</xsl:choose>

			<xsl:for-each select="node()">
				<xsl:choose>
					<xsl:when test="(position( )) = 1 and (name(self::node())) = 'p'">
						<xsl:apply-templates select="." mode="first-p-in-blockquote" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="." />
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
		</div>
	</xsl:template>

	<!-- Add bl_nonindent to the first <p> inside a <blockquote> -->
	<xsl:template match="p" mode="first-p-in-blockquote">
		<p>
			<xsl:choose>
				<xsl:when test="@class">
					<xsl:attribute name="class">bl_nonindent <xsl:value-of select="@class" /></xsl:attribute>
				</xsl:when>
				<xsl:otherwise>
					<xsl:attribute name="class">bl_nonindent</xsl:attribute>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@style">
				<xsl:attribute name="style"><xsl:value-of select="@style" /></xsl:attribute>
			</xsl:if>

			<xsl:apply-templates />
		</p>
	</xsl:template>

	<!-- Add gross "indent" and "nonindent" classes to every paragraph -->
	<xsl:template match="p">
		<p>
			<xsl:choose>
				<xsl:when test="parent::blockquote">
					<xsl:choose>
						<xsl:when test="@class">
							<xsl:attribute name="class">bl_indent <xsl:value-of select="@class" /></xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="class">bl_indent</xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>

					<xsl:variable name="pclass">
						<xsl:if test="@class">
							<xsl:value-of select="concat(' ', @class)" />
						</xsl:if>
					</xsl:variable>

					<xsl:choose>
						<xsl:when test="not(preceding-sibling::p)">
							<xsl:attribute name="class">nonindent<xsl:value-of select="$pclass" /></xsl:attribute>
						</xsl:when>

						<xsl:when
								test="preceding-sibling::*[1][local-name() = 'h1'] or preceding-sibling::*[1][local-name() = 'h2'] or preceding-sibling::*[1][local-name() = 'h3'] or preceding-sibling::*[1][local-name() = 'h4'] or preceding-sibling::*[1][local-name() = 'h5'] or preceding-sibling::*[1][local-name() = 'h6'] or preceding-sibling::*[1][local-name() = 'hr']">
							<xsl:attribute name="class">nonindent<xsl:value-of select="$pclass" /></xsl:attribute>
						</xsl:when>

						<xsl:when test="preceding-sibling::*[1][local-name() = 'blockquote']">
							<xsl:attribute name="class">nonindent<xsl:value-of select="$pclass" /></xsl:attribute>
						</xsl:when>

						<xsl:otherwise>
							<xsl:attribute name="class">indent<xsl:value-of select="$pclass" /></xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>

				</xsl:otherwise>
			</xsl:choose>

			<xsl:if test="@id">
				<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			</xsl:if>
			<xsl:if test="@style">
				<xsl:attribute name="style"><xsl:value-of select="@style" /></xsl:attribute>
			</xsl:if>

			<xsl:apply-templates />
		</p>
	</xsl:template>

	<!-- Add a hidden <br /> at the end of every footnote -->
	<xsl:template match="li[starts-with(@id,'footnote-')]">
		<li>
			<xsl:attribute name="id"><xsl:value-of select="@id" /></xsl:attribute>
			<xsl:apply-templates />
			<br style="line-height:0px;" />
		</li>
	</xsl:template>

</xsl:stylesheet>