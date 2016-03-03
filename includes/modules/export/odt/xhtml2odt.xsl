<?xml version='1.0' encoding="UTF-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:saxon="http://saxon.sf.net/" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
	xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
	xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
	xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
	xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
	xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
	xmlns:field="urn:openoffice:names:experimental:ooo-ms-interop:xmlns:field:1.0"
	xpath-default-namespace="http://www.w3.org/1999/xhtml">
	<xsl:strip-space elements="div ul li dl ol table tbody tr"/>

	<xsl:output method="xml" standalone="yes"/>

	<xsl:template match="/">
		<xsl:variable name="contentXml">
			<xsl:apply-templates/>
		</xsl:variable>

		<xsl:result-document href="content.xml" method="xml">
			<xsl:copy-of select="$contentXml"/>
		</xsl:result-document>

		<xsl:result-document href="meta.xml" method="xml">
			<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
				xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:xlink="http://www.w3.org/1999/xlink" office:version="1.1">
				<office:meta>
					<meta:generator>MicrosoftOffice/14.0 MicrosoftWord</meta:generator>
					<meta:initial-creator>Thiyagarajan</meta:initial-creator>
					<dc:creator>Thiyagarajan</dc:creator>
					<meta:creation-date>2015-02-27T16:23:00Z</meta:creation-date>
					<dc:date>2015-02-27T16:24:00Z</dc:date>
					<meta:template xlink:href="Normal.dotm" xlink:type="simple"/>
					<meta:editing-cycles>1</meta:editing-cycles>
					<meta:editing-duration>PT60S</meta:editing-duration>
					<meta:document-statistic meta:page-count="1" meta:paragraph-count="0" meta:word-count="0"
						meta:character-count="0" meta:row-count="0" meta:non-whitespace-character-count="0"/>
				</office:meta>
			</office:document-meta>
		</xsl:result-document>

		<xsl:result-document href="settings.xml" method="xml">
			<office:document-settings office:version="1.1"
				xmlns:anim="urn:oasis:names:tc:opendocument:xmlns:animation:1.0"
				xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"
				xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"
				xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
				xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
				xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"
				xmlns:math="http://www.w3.org/1998/Math/MathML"
				xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
				xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
				xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
				xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0"
				xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"
				xmlns:smil="urn:oasis:names:tc:opendocument:xmlns:smil-compatible:1.0"
				xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
				xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
				xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
				xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
				xmlns:xforms="http://www.w3.org/2002/xforms"
				xmlns:xlink="http://www.w3.org/1999/xlink"/>
		</xsl:result-document>

		<xsl:result-document href="styles.xml" method="xml">
			<office:document-styles xmlns:anim="urn:oasis:names:tc:opendocument:xmlns:animation:1.0"
				xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"
				xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"
				xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
				xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
				xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"
				xmlns:math="http://www.w3.org/1998/Math/MathML"
				xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
				xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
				xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
				xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0"
				xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"
				xmlns:smil="urn:oasis:names:tc:opendocument:xmlns:smil-compatible:1.0"
				xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
				xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
				xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
				xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
				xmlns:xforms="http://www.w3.org/2002/xforms"
				xmlns:xlink="http://www.w3.org/1999/xlink">
				<office:font-face-decls>
					<style:font-face style:name="Calibri" svg:font-family="Calibri"
						style:font-family-generic="swiss" style:font-pitch="variable"
						svg:panose-1="2 15 5 2 2 2 4 3 2 4"/>
					<style:font-face style:name="Latha" svg:font-family="Latha"
						style:font-family-generic="swiss" style:font-pitch="variable"
						svg:panose-1="2 11 6 4 2 2 2 2 2 4"/>
					<style:font-face style:name="Times New Roman" svg:font-family="Times New Roman"
						style:font-family-generic="roman" style:font-pitch="variable"
						svg:panose-1="2 2 6 3 5 4 5 2 3 4"/>
					<style:font-face style:name="Cambria" svg:font-family="Cambria"
						style:font-family-generic="roman" style:font-pitch="variable"
						svg:panose-1="2 4 5 3 5 4 6 3 2 4"/>
					<style:font-face style:name="Courier New" svg:font-family="Courier New"
						style:font-family-generic="modern" style:font-pitch="fixed"
						svg:panose-1="2 7 3 9 2 2 5 2 4 4"/>
				</office:font-face-decls>
				<office:styles>
					<style:default-style style:family="table">
						<style:table-properties fo:margin-left="0in" table:border-model="collapsing"
							style:writing-mode="lr-tb" table:align="left"/>
					</style:default-style>
					<style:default-style style:family="table-column">
						<style:table-column-properties style:use-optimal-column-width="true"/>
					</style:default-style>
					<style:default-style style:family="table-row">
						<style:table-row-properties style:min-row-height="0in"
							style:use-optimal-row-height="true" fo:keep-together="auto"/>
					</style:default-style>
					<style:default-style style:family="table-cell">
						<style:table-cell-properties fo:background-color="transparent"
							style:glyph-orientation-vertical="auto" style:vertical-align="top"
							fo:wrap-option="wrap"/>
					</style:default-style>
					<style:default-style style:family="paragraph">
						<style:paragraph-properties fo:keep-with-next="auto" fo:keep-together="auto"
							fo:widows="2" fo:orphans="2" fo:break-before="auto"
							text:number-lines="true" fo:border="none" fo:padding="0in"
							style:shadow="none" style:line-break="strict"
							style:punctuation-wrap="hanging" style:text-autospace="ideograph-alpha"
							style:snap-to-layout-grid="true" fo:text-align="start"
							style:writing-mode="lr-tb" fo:margin-bottom="0in"
							fo:line-height="115%" fo:background-color="transparent"
							style:tab-stop-distance="0.5in"/>
						<style:text-properties style:font-name="Times New Roman"
							style:font-name-asian="Times New Roman" style:font-name-complex="Latha"
							fo:font-weight="normal" style:font-weight-asian="normal"
							style:font-weight-complex="normal" fo:font-style="normal"
							style:font-style-asian="normal" style:font-style-complex="normal"
							fo:text-transform="none" fo:font-variant="normal"
							style:text-line-through-type="none" style:text-outline="false"
							style:font-relief="none" style:use-window-font-color="true"
							fo:letter-spacing="normal" style:text-scale="100%"
							style:letter-kerning="false" style:text-position="0% 100%"
							fo:font-size="12pt" style:font-size-asian="12pt"
							style:font-size-complex="12pt" fo:background-color="transparent"
							style:text-underline-type="none" style:text-underline-color="font-color"
							style:text-emphasize="none" fo:language="en" fo:country="US"
							style:language-asian="en" style:country-asian="US"
							style:language-complex="ar" style:country-complex="SA"
							style:text-combine="none" fo:hyphenate="true"/>
					</style:default-style>
					<style:style style:name="Standard" style:family="paragraph" style:class="text"/>
					<style:style style:name="Normal" style:display-name="Normal" style:family="paragraph">
						<style:text-properties fo:hyphenate="true"/>
					</style:style>
					<style:style style:name="DefaultParagraphFont" style:display-name="Default Paragraph Font" style:family="text"/>
					<text:notes-configuration text:note-class="footnote" text:start-value="0"
						style:num-format="1" text:start-numbering-at="document"
						text:footnotes-position="page"/>
					<text:notes-configuration text:note-class="endnote" text:start-value="0"
						style:num-format="i" text:start-numbering-at="document"
						text:footnotes-position="document"/>
					<style:default-style style:family="graphic">
						<style:graphic-properties draw:fill="solid" draw:fill-color="#4f81bd"
							draw:opacity="100%" draw:stroke="solid" svg:stroke-width="0.02778in"
							svg:stroke-color="#385d8a" svg:stroke-opacity="100%"/>
					</style:default-style>

					<style:style style:name="italic" style:family="text">
						<style:text-properties fo:font-style="italic"/>
					</style:style>
					<style:style style:name="bold" style:family="text">
						<style:text-properties fo:font-weight="bold"/>
					</style:style>

					<style:style style:name="underline" style:family="text">
						<style:text-properties style:text-underline-style="solid"
							style:text-underline-width="auto"
							style:text-underline-color="font-color"/>
					</style:style>

					<style:style style:name="strikeout" style:family="text">
						<style:text-properties style:text-line-through-style="solid"/>
					</style:style>

					<style:style style:name="kbdTxt" style:family="text">
						<style:text-properties style:font-name="Courier New"/>
					</style:style>

					<style:style style:name="sup" style:family="text">
						<style:text-properties style:text-position="33% 58%"/>
					</style:style>

					<style:style style:name="sub" style:family="text">
						<style:text-properties style:text-position="-33% 58%"/>
					</style:style>

					<style:style style:family="graphic" style:name="imageFrame1">
						<style:graphic-properties fo:border="none" fo:background-color="transparent" style:wrap="none" style:horizontal-pos="center"/>
					</style:style>

					<style:style style:name="pullquotebox" style:family="graphic" style:parent-style-name="imageFrame1">
						<style:graphic-properties fo:margin-left="0.1in" fo:margin-right="0in"
							style:run-through="foreground" style:wrap="parallel"
							style:number-wrapped-paragraphs="no-limit" style:vertical-pos="from-top"
							style:vertical-rel="paragraph" style:horizontal-pos="right"
							style:horizontal-rel="paragraph" fo:padding="0.1in"
							fo:border-left="none" fo:border-right="none"
							fo:border-top="0.0138in solid #000000"
							fo:border-bottom="0.0138in solid #000000" style:shadow="none"/>
					</style:style>

					<style:style style:name="pullquotebox-left" style:family="graphic" style:parent-style-name="imageFrame1">
						<style:graphic-properties fo:margin-left="0.1in" fo:margin-right="0in"
							style:run-through="foreground" style:wrap="parallel"
							style:number-wrapped-paragraphs="no-limit" style:vertical-pos="from-top"
							style:vertical-rel="paragraph" style:horizontal-pos="left"
							style:horizontal-rel="paragraph" fo:padding="0.1in"
							fo:border-left="none" fo:border-right="none"
							fo:border-top="0.0138in solid #000000"
							fo:border-bottom="0.0138in solid #000000" style:shadow="none"/>
					</style:style>

					<style:style style:name="textbox" style:family="graphic" style:parent-style-name="imageFrame1">
						<style:graphic-properties fo:margin-left="0in" fo:margin-right="0in" fo:margin-top="0.1in" fo:margin-bottom="0in"
							style:run-through="foreground" style:wrap="none"
							style:number-wrapped-paragraphs="no-limit" style:vertical-pos="from-top"
							style:vertical-rel="paragraph" style:horizontal-pos="center"
							style:horizontal-rel="paragraph" fo:padding="0.1in"
							fo:border-left="0.0138in solid #000000" fo:border-right="0.0138in solid #000000"
							fo:border-top="0.0138in solid #000000"
							fo:border-bottom="0.0138in solid #000000" style:shadow="none"/>
					</style:style>

					<text:list-style style:name="List1">
						<text:list-level-style-number text:level="1"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="0.25in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="2"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="0.5in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="3"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="0.75in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="4"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="1in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="5"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="1.25in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="6"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="1.5in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="7"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="1.75in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="8"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="2in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="9"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="2.25in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
						<text:list-level-style-number text:level="10"
							text:style-name="Numbering_20_Symbols" style:num-suffix="."
							style:num-format="1">
							<style:list-level-properties text:space-before="2.5in" text:min-label-width="0.25in"/>
						</text:list-level-style-number>
					</text:list-style>
					<text:list-style style:name="List-none">
						<text:list-level-style-image text:level="1"
							text:style-name="none"
							style:num-format="none">
							<style:list-level-properties text:space-before="0.25in" text:min-label-width="0.25in"/>
						</text:list-level-style-image>
						<text:list-level-style-image text:level="2"
							text:style-name="none"
							style:num-format="none">
							<style:list-level-properties text:space-before="0.5in" text:min-label-width="0.25in"/>
						</text:list-level-style-image>
						</text:list-style>

					<style:style style:name="P_Center" style:family="paragraph" style:parent-style-name="Standard">
						<style:paragraph-properties fo:text-align="center" style:justify-single-word="false"/>
					</style:style>
					<style:style style:name="P_Right" style:family="paragraph" style:parent-style-name="Standard">
						<style:paragraph-properties fo:text-align="end" style:justify-single-word="false"/>
					</style:style>
					<style:style style:name="P_Justify" style:family="paragraph" style:parent-style-name="Standard">
						<style:paragraph-properties fo:text-align="justify" style:justify-single-word="false"/>
					</style:style>
					<style:style style:name="P_Indent" style:family="paragraph" style:parent-style-name="Standard">
						<style:paragraph-properties fo:margin-left="0.4925in" fo:margin-right="0in"
							fo:text-indent="0in" style:auto-text-indent="false"/>
					</style:style>
					<style:style style:name="P_Hanging-Indent" style:family="paragraph" style:parent-style-name="Standard">
						<style:paragraph-properties fo:margin-left="0.4925in" fo:margin-right="0in"
							fo:text-indent="-0.4925in" style:auto-text-indent="false"/>
					</style:style>

					<style:style style:name="blockquote" style:family="paragraph" style:parent-style-name="Standard">
						<style:paragraph-properties fo:font-size="10pt"
							fo:margin-left="0.25in" fo:margin-right="0.25in"
							fo:margin-top="0.17in" fo:margin-bottom="0.17in"
							fo:text-indent="0in" style:auto-text-indent="false"/>
					</style:style>

					<text:notes-configuration text:note-class="endnote"
						text:citation-style-name="Endnote_20_Symbol"
						text:citation-body-style-name="Endnote_20_anchor"
						text:master-page-name="Endnote" style:num-format="i" text:start-value="0"/>
					<style:style style:name="Endnote" style:family="paragraph"
						style:parent-style-name="Standard" style:class="extra">
						<style:paragraph-properties fo:margin-left="0.499cm" fo:margin-right="0cm"
							fo:text-indent="-0.499cm" style:auto-text-indent="false"
							text:number-lines="false" text:line-number="0"/>
						<style:text-properties fo:font-size="10pt" style:font-size-asian="10pt"
							style:font-size-complex="10pt"/>
					</style:style>

					<text:notes-configuration text:note-class="footnote"
						text:citation-style-name="Footnote_20_Symbol"
						text:citation-body-style-name="Footnote_20_anchor" style:num-format="1"
						text:start-value="0" text:footnotes-position="page"
						text:start-numbering-at="document"/>

					<style:style style:name="Footnote" style:family="paragraph" style:parent-style-name="Standard" style:class="extra">
						<style:paragraph-properties fo:margin-left="0.499cm" fo:margin-right="0cm" fo:text-indent="-0.499cm"
							style:auto-text-indent="false" text:number-lines="false" text:line-number="0"/>
						<style:text-properties fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
					</style:style>

				</office:styles>
				<office:automatic-styles>
					<style:page-layout style:name="PL0">
						<style:page-layout-properties fo:page-width="8.268in"
							fo:page-height="11.693in" style:print-orientation="portrait"
							fo:margin-top="1in" fo:margin-left="1in" fo:margin-bottom="1in"
							fo:margin-right="1in" style:num-format="1" style:writing-mode="lr-tb">
							<style:footnote-sep style:width="0.007in" style:rel-width="33%"
								style:color="#000000" style:line-style="solid" style:adjustment="left"/>
						</style:page-layout-properties>
					</style:page-layout>
					<style:style style:name="MP1" style:family="paragraph" style:parent-style-name="Header">
						<style:paragraph-properties fo:text-align="end" style:justify-single-word="false"/>
					</style:style>
					<style:page-layout style:name="Mpm1">
						<style:page-layout-properties fo:page-width="21.001cm"
							fo:page-height="29.7cm" style:num-format="1"
							style:print-orientation="portrait" fo:margin-top="2cm"
							fo:margin-bottom="2cm" fo:margin-left="2cm" fo:margin-right="2cm"
							style:writing-mode="lr-tb" style:footnote-max-height="0cm">
							<style:footnote-sep style:width="0.018cm"
								style:distance-before-sep="0.101cm"
								style:distance-after-sep="0.101cm" style:adjustment="left"
								style:rel-width="25%" style:color="#000000"/>
						</style:page-layout-properties>
						<style:header-style>
							<style:header-footer-properties fo:min-height="0cm"
								fo:margin-bottom="0.499cm"/>
						</style:header-style>
						<style:footer-style/>
					</style:page-layout>
					<style:page-layout style:name="Mpm2">
						<style:page-layout-properties fo:page-width="21.001cm"
							fo:page-height="29.7cm" style:num-format="1"
							style:print-orientation="portrait" fo:margin-top="2.54cm"
							fo:margin-bottom="2.54cm" fo:margin-left="2.54cm"
							fo:margin-right="2.54cm" style:writing-mode="lr-tb"
							style:footnote-max-height="0cm">
							<style:footnote-sep style:width="0.018cm" style:adjustment="left"
								style:rel-width="33%" style:color="#000000"/>
						</style:page-layout-properties>
						<style:header-style>
							<style:header-footer-properties fo:min-height="0cm"
								fo:margin-bottom="0.499cm"/>
						</style:header-style>
						<style:footer-style/>
					</style:page-layout>
					<style:page-layout style:name="Mpm3">
						<style:page-layout-properties fo:page-width="21.001cm"
							fo:page-height="29.7cm" style:num-format="1"
							style:print-orientation="portrait" fo:margin-top="2cm"
							fo:margin-bottom="2cm" fo:margin-left="2cm" fo:margin-right="2cm"
							style:writing-mode="lr-tb" style:footnote-max-height="0cm">
							<style:footnote-sep style:adjustment="left" style:rel-width="25%"
								style:color="#000000"/>
						</style:page-layout-properties>
						<style:header-style/>
						<style:footer-style/>
					</style:page-layout>
				</office:automatic-styles>
				<office:master-styles>
					<style:master-page style:name="MP0" style:page-layout-name="PL0"/>
					<style:master-page style:name="Standard" style:page-layout-name="Mpm1">
						<style:header>
							<text:p text:style-name="MP1">
								<text:chapter text:display="name" text:outline-level="1">
									<!--<xsl:value-of select="//h2[@class='chapter-title'][1]"/>-->
								</text:chapter>
								<text:tab/>
								<text:tab/>
								<text:page-number text:select-page="current">1 </text:page-number>
							</text:p>
						</style:header>
					</style:master-page>
					<style:master-page style:name="MP0" style:page-layout-name="Mpm2">
						<style:header>
							<text:p text:style-name="Header"/>
						</style:header>
					</style:master-page>
					<style:master-page style:name="Endnote" style:page-layout-name="Mpm3"/>
				</office:master-styles>
			</office:document-styles>
		</xsl:result-document>


		<xsl:result-document href="mimetype" method="text">
			<xsl:text>application/vnd.oasis.opendocument.text</xsl:text>
		</xsl:result-document>

		<xsl:result-document href="META-INF/manifest.xml" method="xml">
			<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
				<manifest:file-entry manifest:full-path="/"
					manifest:media-type="application/vnd.oasis.opendocument.text"/>
				<manifest:file-entry manifest:full-path="mimetype" manifest:media-type="text/plain"/>
				<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>
				<manifest:file-entry manifest:full-path="settings.xml"
					manifest:media-type="text/xml"/>
				<manifest:file-entry manifest:full-path="styles.xml" manifest:media-type="text/xml"/>
				<manifest:file-entry manifest:full-path="META-INF/manifest.xml"
					manifest:media-type="text/xml"/>
				<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>
			</manifest:manifest>
		</xsl:result-document>
	</xsl:template>



	<xsl:template match="html">
		<office:document-content xmlns:anim="urn:oasis:names:tc:opendocument:xmlns:animation:1.0"
			xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0"
			xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0"
			xmlns:dc="http://purl.org/dc/elements/1.1/"
			xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0"
			xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
			xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
			xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0"
			xmlns:math="http://www.w3.org/1998/Math/MathML"
			xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0"
			xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
			xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
			xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0"
			xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0"
			xmlns:smil="urn:oasis:names:tc:opendocument:xmlns:smil-compatible:1.0"
			xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
			xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0"
			xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"
			xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
			xmlns:xforms="http://www.w3.org/2002/xforms" xmlns:xlink="http://www.w3.org/1999/xlink">
			<office:font-face-decls>
				<style:font-face style:name="Calibri" svg:font-family="Calibri"
					style:font-family-generic="swiss" style:font-pitch="variable"
					svg:panose-1="2 15 5 2 2 2 4 3 2 4"/>
				<style:font-face style:name="Latha" svg:font-family="Latha"
					style:font-family-generic="swiss" style:font-pitch="variable"
					svg:panose-1="2 11 6 4 2 2 2 2 2 4"/>
				<style:font-face style:name="Times New Roman" svg:font-family="Times New Roman"
					style:font-family-generic="roman" style:font-pitch="variable"
					svg:panose-1="2 2 6 3 5 4 5 2 3 4"/>
				<style:font-face style:name="Cambria" svg:font-family="Cambria"
					style:font-family-generic="roman" style:font-pitch="variable"
					svg:panose-1="2 4 5 3 5 4 6 3 2 4"/>
			</office:font-face-decls>
			<office:automatic-styles>
				<style:style style:name="P1" style:parent-style-name="Standard"
					style:master-page-name="MP0" style:family="paragraph">
					<style:paragraph-properties fo:break-before="page"/>
				</style:style>
				<style:style style:name="P3" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="4in" fo:margin-bottom="0in" fo:text-align="start" style:justify-single-word="false"/>
					<style:text-properties fo:color="#5DA4BF" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:text-align="left" fo:font-size="12pt" fo:font-weight="normal" fo:font-style="normal" fo:text-transform="none" fo:font-variant="normal" fo:letter-spacing="none" fo:word-spacing="none"/>
				</style:style>
				<!--Kamlesh-->
				<!--No_Indent-->
				<style:style style:name="firstcharacter" style:family="text" style:parent-style-name="Standard">
					<style:text-properties fo:font-weight="normal" fo:font-style="normal" fo:font-size="54pt" fo:font-family="PT Serif, Tinos, Times New Roman, SBL Greek, serif" fo:color="#2393BD" fo:text-transform="none" fo:float="left" fo:padding-right="3px" fo:margin-top="-5px" fo:margin-bottom="-5px"/>
				</style:style>
				<style:style style:name="epigraph.p" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.166044in" fo:margin-bottom="0in" fo:text-align="start" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="italic" fo:font-size="12pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif"/>
				</style:style>

				<style:style style:name="dedication.p" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.166044in" fo:margin-bottom="0in" fo:text-align="start" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="italic" fo:font-size="12pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif"/>
				</style:style>


				<style:style style:name="No_Indent" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.166044in" fo:margin-bottom="0in" fo:text-align="start" style:justify-single-word="false"/>
					<style:text-properties fo:text-indent="0pt"/>
				</style:style>
				<style:style style:name="P_Indent1" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-left="0in" fo:margin-right="0in"
						fo:text-indent="0.2in" style:auto-text-indent="false" fo:margin-top="0.166044in"/>
				</style:style>
				<!--toc.h1-->
				<style:style style:name="toc.h1" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-left="0.394in" fo:margin-top="0.787402in" fo:margin-bottom="0.787402in" fo:text-align="left" style:justify-single-word="false" fo:padding-bottom="0.19685in"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="italic" fo:font-size="24pt" fo:font-family="Noticia Text, Times, SBL Greek, serif" fo:color="#2393BD" fo:text-transform="none"/>
				</style:style>
				<style:style style:name="toc.ul" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties  fo:list-style="none" fo:margin-left="0.394in" fo:margin-top="0in" fo:line-height="0in" fo:margin-bottom="0in" fo:text-align="left" style:justify-single-word="false" fo:padding-bottom="0.19685in"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="normal" fo:font-size="12pt" fo:font-family="Noticia Text, Times, SBL Greek, serif" fo:color="#2393BD" fo:text-transform="none"/>
				</style:style>

				<!--front-matter-title-wrap.h1.front-matter-title-->
				<style:style style:name="front-matter-title-wrap.h1.front-matter-title" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="italic" fo:font-size="24pt" fo:font-family="Noticia Text, Times, SBL Greek, serif" fo:color="#2393BD" fo:text-transform="none"/>
				</style:style>
				<!--ugc.front-matter-ugc.h2.chapter-subtitle-->
				<style:style style:name="ugc.front-matter-ugc.h2.chapter-subtitle" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="bold" fo:font-style="normal" fo:font-size="12pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:color="#135169" fo:text-transform="none"/>
				</style:style>
				<style:style style:name="ugc.front-matter-ugc.h6.short-title" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0in" fo:margin-bottom="0in" fo:text-align="none" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="none" fo:font-style="none" fo:font-size="0pt" fo:text-transform="none" fo:display="none" fo:visibility="collapse"/>
				</style:style>

				<!--part-title-wrap.h1.part-title-->
				<style:style style:name="part-title-wrap.h1.part-title" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false" fo:text-transform="uppercase"/>
					<style:text-properties fo:color="#2393BD" fo:font-weight="bold" fo:font-style="normal" fo:font-variant="normal" fo:font-size="24pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif"  fo:text-align="left" fo:letter-spacing="1px" fo:word-spacing="2px"/>
				</style:style>
				<!--part-title-wrap.h3.part-number-->
				<style:style style:name="part-title-wrap.h3.part-number" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false" fo:text-transform="uppercase" />
					<style:text-properties fo:color="#135169" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:text-align="left" fo:font-size="14.5pt" fo:font-weight="bold" fo:font-style="normal" fo:font-variant="normal" fo:letter-spacing="1px" fo:word-spacing="2px" fo:border-bottom="0.5px #135169 solid" fo:padding-bottom="5em" fo:display="block" fo:page-break-after="avoid"/>
				</style:style>
				<!--chapter-title-wrap.h2.chapter-title-->
				<style:style style:name="chapter-title-wrap.h2.chapter-title" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:color="#2393BD" fo:font-family="Noticia Text, Times, SBL Greek, serif" fo:text-align="left" fo:font-size="24pt" fo:font-weight="normal" fo:font-style="italic" fo:text-transform="none" fo:font-variant="normal" fo:letter-spacing="0px" fo:word-spacing="2px" fo:padding-bottom="5em" fo:display="block"/>
				</style:style>
				<!--chapter-ugc.h1.section-header-->
				<style:style style:name="chapter-ugc.h1.section-header" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.083022in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"  fo:text-transform="uppercase"/>
					<style:text-properties fo:font-weight="bold" fo:font-style="normal" fo:font-size="18pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:color="#135169"/>
				</style:style>
				<style:style style:name="chapter-ugc.h2.NoClass" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.083022in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="bold" fo:font-style="normal" fo:font-size="16pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:color="#135169" fo:text-transform="none"/>
				</style:style>
				<style:style style:name="chapter-ugc.h3.NoClass" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.083022in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="normal" fo:font-size="16pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:color="#135169" fo:text-transform="none"/>
				</style:style>
				<style:style style:name="chapter-ugc.h4.NoClass" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.083022in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="bold" fo:font-style="italic" fo:font-size="14pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:color="#135169" fo:text-transform="none"/>
				</style:style>
				<style:style style:name="chapter-ugc.h5.NoClass" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.083022in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="normal" fo:font-size="14pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:color="#135169" fo:text-transform="none"/>
				</style:style>
				<style:style style:name="chapter-ugc.h6.NoClass" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.083022in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:font-weight="normal" fo:font-style="italic" fo:font-size="12pt" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:color="#135169" fo:text-transform="none"/>
				</style:style>

				<!--chapter-title-wrap.h3.chapter-number-->
				<style:style style:name="chapter-title-wrap.h3.chapter-number" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties fo:color="#135169" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:text-align="left" fo:font-size="12pt" fo:font-weight="bold" fo:font-style="normal" fo:text-transform="uppercase" fo:font-variant="normal" fo:letter-spacing="1px" fo:word-spacing="2px" fo:border-bottom="0.5px #135169 solid" fo:padding-bottom="5em" fo:display="block"/>
				</style:style>
				<!--title-page.h1.title-->
				<style:style style:name="title-page.h1.title" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0in" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:color="#2393BD" fo:font-family="Noticia Text, Times, SBL Greek, serif" fo:text-align="left" fo:font-size="36pt" fo:font-weight="normal" fo:font-style="italic" fo:text-transform="none" fo:font-variant="normal" fo:letter-spacing="1px" fo:word-spacing="2px" fo:border-bottom="0.5px #135169 solid" fo:padding-bottom="5em" fo:display="block" fo:margin-top="2cm"/>
				</style:style>
				<!--title-page.h2.subtitle-->
				<style:style style:name="title-page.h2.subtitle" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="2cm" fo:margin-bottom="0.083022in" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:color="#135169" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:text-align="left" fo:font-size="14.5pt" fo:font-weight="bold" fo:font-style="normal" fo:text-transform="uppercase" fo:font-variant="normal" fo:letter-spacing="1px" fo:word-spacing="2px"/>
				</style:style>
				<!--title-page.h3.author-->
				<style:style style:name="title-page.h3.author" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="0.083022in" fo:margin-bottom="4cm" fo:text-align="left" style:justify-single-word="false"/>
					<style:text-properties fo:color="#135169" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:text-align="left" fo:font-size="12pt" fo:font-weight="500" fo:font-style="normal" fo:text-transform="uppercase" fo:font-variant="normal" fo:letter-spacing="1px" fo:word-spacing="2px"  fo:border-bottom="40cm #135169 solid" fo:padding-bottom="40cm"/>
				</style:style>
				<!--title-page.h4.publisher-->
				<style:style style:name="title-page.h4.publisher" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:margin-top="4cm" fo:margin-bottom="0.083022in" fo:text-align="start" style:justify-single-word="false"/>
					<style:text-properties fo:color="#5DA4BF" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:text-align="left" fo:font-size="12pt" fo:font-weight="normal" fo:font-style="normal" fo:text-transform="none" fo:font-variant="normal" fo:letter-spacing="none" fo:word-spacing="none"/>
				</style:style>
				<!--title-page.h5.publisher-city-->
				<style:style style:name="title-page.h5.publisher-city" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties fo:color="#5DA4BF" fo:font-family="Open Sans, Helvetica, GFS Neohellenic, sans-serif" fo:text-align="left" fo:font-size="12pt" fo:font-weight="normal" fo:font-style="normal" fo:text-transform="uppercase" fo:font-variant="normal" fo:letter-spacing="1px" fo:word-spacing="2px" fo:border-bottom="3.5cm #135169 solid" fo:padding-bottom="3.5cm"/>
				</style:style>


			</office:automatic-styles>
			<office:body>
				<office:text text:use-soft-page-breaks="true">
					<text:p>
						<draw:frame draw:style-name="imageFrame1" text:anchor-type="paragraph" svg:x="0in"
							svg:y="0in" svg:width="6.26806in" svg:height="4.70139in" style:rel-width="scale"
							style:rel-height="scale">
							<xsl:attribute name="draw:name">
								<xsl:text>Cover </xsl:text>
								<xsl:number/>
							</xsl:attribute>
							<draw:image xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad">
								<xsl:attribute name="xlink:href">
									<xsl:text>media/</xsl:text>
									<xsl:value-of select="escape-html-uri(replace(./head/meta[@name='pb-cover-image']/@content, '(http.+/)(.+?)$', '$2'))"/>
								</xsl:attribute>
							</draw:image>
						</draw:frame>
					</text:p>
					<xsl:apply-templates/>
				</office:text>
			</office:body>
		</office:document-content>
	</xsl:template>

	<xsl:template match="body">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="title">
		<text:h text:outline-level="1">
			<xsl:apply-templates/>
		</text:h>
	</xsl:template>

	<xsl:template match="h1|h2|h3|h4|h5|h6">
					<xsl:choose>
						<xsl:when test="(local-name()='h3') and (@class='front-matter-number') and parent::div[@class='front-matter-title-wrap']"/>
						<xsl:when test="(local-name()='h1') and (@class='front-matter-title') and parent::div[@class='front-matter-title-wrap'] and (child::*[@class='display-none'])"/>
						<xsl:when test="(local-name()='h1') and (@class='title') and parent::div[@id='half-title-page']"/>
						<xsl:when test="(local-name()='h6') and (parent::div[@class='ugc front-matter-ugc']) and (@class='short-title')"/>

					<xsl:otherwise>
					<text:h>
					<xsl:attribute name="text:outline-level">
						<xsl:choose>
							<xsl:when test="@class='chapter-title'">
								<xsl:text>1</xsl:text>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="replace(local-name(), 'h', '')"/>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>

							<!--Kumar-->
							<xsl:choose>

								<xsl:when test="(local-name()='h1') and (@class='section-header') and parent::div[@class='ugc chapter-ugc']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-ugc.h1.section-header</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h2') and (not(@class)) and parent::div[@class='ugc chapter-ugc']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-ugc.h2.NoClass</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h3') and (not(@class)) and parent::div[@class='ugc chapter-ugc']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-ugc.h3.NoClass</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h4') and (not(@class)) and parent::div[@class='ugc chapter-ugc']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-ugc.h4.NoClass</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h5') and (not(@class)) and parent::div[@class='ugc chapter-ugc']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-ugc.h5.NoClass</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h6') and (not(@class)) and parent::div[@class='ugc chapter-ugc']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-ugc.h6.NoClass</xsl:text></xsl:attribute>
								</xsl:when>



								<xsl:when test="(local-name()='h1') and parent::div[@id='toc']">
									<xsl:attribute name="text:style-name"><xsl:text>toc.h1</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h1') and (@class='front-matter-title') and parent::div[@class='front-matter-title-wrap']">
									<xsl:attribute name="text:style-name"><xsl:text>front-matter-title-wrap.h1.front-matter-title</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h2') and (parent::div[@class='ugc front-matter-ugc']) or (@class='chapter-subtitle')">
									<xsl:attribute name="text:style-name"><xsl:text>ugc.front-matter-ugc.h2.chapter-subtitle</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h6') and (parent::div[@class='ugc front-matter-ugc']) or (@class='short-title')">
									<xsl:attribute name="text:style-name"><xsl:text>ugc.front-matter-ugc.h6.short-title</xsl:text></xsl:attribute>
								</xsl:when>

								<xsl:when test="(local-name()='h1') and (@class='part-title') and parent::div[@class='part-title-wrap']">
									<xsl:attribute name="text:style-name"><xsl:text>part-title-wrap.h1.part-title</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h3') and (@class='part-number') and parent::div[@class='part-title-wrap']">
									<xsl:attribute name="text:style-name"><xsl:text>part-title-wrap.h3.part-number</xsl:text></xsl:attribute>
									<xsl:text>PART </xsl:text>
								</xsl:when>

								<xsl:when test="(local-name()='h2') and (@class='chapter-title') and parent::div[@class='chapter-title-wrap']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-title-wrap.h2.chapter-title</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h3') and (@class='chapter-number') and parent::div[@class='chapter-title-wrap']">
									<xsl:attribute name="text:style-name"><xsl:text>chapter-title-wrap.h3.chapter-number</xsl:text></xsl:attribute>
									<xsl:text>Chapter </xsl:text>
								</xsl:when>
								<!--****************-->




								<xsl:when test="(local-name()='h1') and (@class='title') and parent::div[@id='title-page']">
									<xsl:attribute name="text:style-name"><xsl:text>title-page.h1.title</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h2') and (@class='subtitle') and parent::div[@id='title-page']">
									<xsl:attribute name="text:style-name"><xsl:text>title-page.h2.subtitle</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h3') and (@class='author') and parent::div[@id='title-page']">
									<xsl:attribute name="text:style-name"><xsl:text>title-page.h3.author</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h4') and (@class='publisher') and parent::div[@id='title-page']">
									<xsl:attribute name="text:style-name"><xsl:text>title-page.h4.publisher</xsl:text></xsl:attribute>
								</xsl:when>
								<xsl:when test="(local-name()='h5') and (@class='publisher-city') and parent::div[@id='title-page']">
									<xsl:attribute name="text:style-name"><xsl:text>title-page.h5.publisher-city</xsl:text></xsl:attribute>
								</xsl:when>

							</xsl:choose>
					<xsl:choose>
						<xsl:when test="@id">
							<text:bookmark text:name="{@id}"/>
						</xsl:when>
						<xsl:when test="@class='chapter-title' or @class='back-matter-title' or @class='front-matter-title'">
							<xsl:if test="ancestor::div[@id][1]">
								<text:bookmark text:name="{ancestor::div[@id][1]/@id}"/>
							</xsl:if>
						</xsl:when>
					</xsl:choose>
					<!--<xsl:apply-templates/>-->
					<!--KK Gupta-->
						<xsl:apply-templates/>
					</text:h>
				</xsl:otherwise>
				</xsl:choose>
	</xsl:template>

	<xsl:template match="p[@class='wp-caption-text']" mode="captionMode">
		<text:span>
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="p[@class='wp-caption-text']">
		<!-- Do Nothing -->
	</xsl:template>
	<xsl:template match="hr">
		<xsl:choose>
			<xsl:when test="@class='break-symbols'">
				<text:p>
					<xsl:attribute name="text:style-name">
						<xsl:text>P_Center</xsl:text>
					</xsl:attribute>
					<xsl:text>*</xsl:text>
				</text:p>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="p">
		<text:p>
			<xsl:attribute name="text:style-name">
				<xsl:choose>
					<xsl:when test="parent::blockquote">
						<xsl:text>blockquote</xsl:text>
					</xsl:when>
					<xsl:when test="replace(@style, '^(.+)?text-align:\s*(center|right|left|justify)(.+)*$', '$2') = 'center'">
						<xsl:text>P_Center</xsl:text>
					</xsl:when>
					<xsl:when test="replace(@style, '^(.+)?text-align:\s*(center|right|left|justify)(.+)*$', '$2') = 'right'">
						<xsl:text>P_Right</xsl:text>
					</xsl:when>
					<xsl:when test="replace(@style, '^^(.+)?text-align:\s*(center|right|left|justify)(.+)*$', '$2') = 'justify'">
						<xsl:text>P_Justify</xsl:text>
					</xsl:when>
					<xsl:when test="contains(@class, 'hanging-indent')">
						<xsl:text>P_Hanging-Indent</xsl:text>
					</xsl:when>
					<xsl:when test="contains(@style,'padding-left')">
						<xsl:text>P_Indent</xsl:text>
					</xsl:when>
					<xsl:when test="contains(@class,'no-indent')">
						<xsl:text>No_Indent</xsl:text>
					</xsl:when>
					<xsl:when test="ancestor::div[contains(@id,'epigraph')]">
						<xsl:text>epigraph.p</xsl:text>
					</xsl:when>
					<xsl:when test="ancestor::div[contains(@id,'dedication')]">
						<xsl:text>dedication.p</xsl:text>
					</xsl:when>
					<xsl:otherwise>
							<xsl:text>P_Indent1</xsl:text>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:apply-templates/>
		</text:p>
	</xsl:template>

	<xsl:template match="span">
		<xsl:choose>
			<xsl:when test="@class='pullquote-right'">
				<draw:frame draw:style-name="pullquotebox" text:anchor-type="char" svg:y="0.0598in" svg:width="2.2in">
					<draw:text-box fo:min-height="1in">
						<text:p text:style-name="P_Center">
							<xsl:apply-templates/>
						</text:p>
					</draw:text-box>
				</draw:frame>
			</xsl:when>
			<xsl:when test="@class='pullquote-left'">
				<draw:frame draw:style-name="pullquotebox-left" text:anchor-type="char" svg:y="0.0598in" svg:width="2.2in">
					<draw:text-box fo:min-height="1in">
						<text:p text:style-name="Standard">
							<xsl:apply-templates/>
						</text:p>
					</draw:text-box>
				</draw:frame>
			</xsl:when>
			<xsl:when test="@class='footnote'">
				<text:note text:note-class="footnote">
					<!--<text:note-citation text:note-class="footnote">-->
					<!--KK Gupta-->
						<text:note-citation>
						<xsl:value-of select="position()"/>
						<!-- No Citation present for Footnotes -->
					</text:note-citation>
					<text:note-body>
						<text:p text:style-name="Footnote">
							<xsl:value-of select="."/>
						</text:p>
					</text:note-body>
				</text:note>
			</xsl:when>
			<xsl:otherwise>
				<text:span>
					<xsl:if test="contains(@style, 'text-decoration: line-through;')">
						<xsl:attribute name="text:style-name">strikeout</xsl:attribute>
					</xsl:if>
					<xsl:if test="contains(@class, 'firstcharacter')">
						<xsl:attribute name="text:style-name">firstcharacter</xsl:attribute>
					</xsl:if>
					<!--KK Gupta-->
					<xsl:if test="contains(@style, 'text-decoration: underline')">
						<xsl:attribute name="text:style-name">underline</xsl:attribute>
					</xsl:if>
					<xsl:if test="@id">
						<text:bookmark text:name="{@id}"/>
					</xsl:if>
					<xsl:if test="contains(@class, 'chapter-author') or contains(@class, 'chapter-subtitle')">
						<text:line-break/>
					</xsl:if>
					<xsl:apply-templates/>
				</text:span>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="del">
		<text:span text:style-name="strikeout">
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="ins">
		<text:span text:style-name="underline">
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="kbd">
		<text:span text:style-name="kbdTxt">
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="sup">
		<text:span text:style-name="sup">
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="sub">
		<text:span text:style-name="sub">
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="div">
		<xsl:choose>
			<xsl:when test="@id">
				<!--KK GUPTA 30/01/2016-->
				<xsl:if test="not(@id='half-title-page')">
					<text:p text:style-name="P1"/>
				</xsl:if>

				<text:section text:name="{@id}">
					<xsl:apply-templates/>
				</text:section>
			</xsl:when>
			<!--<xsl:when test="div[contains(@class, 'textbox')]">
				<text:p text:style-name="Standard">
					<draw:frame draw:style-name="textbox" text:anchor-type="paragraph" svg:width="6.268in" draw:z-index="0">
						<draw:text-box fo:min-height="1in">
							<text:p text:style-name="Standard">
								<xsl:apply-templates/>
							</text:p>
						</draw:text-box>
					</draw:frame>
				</text:p>
			</xsl:when>-->
			<xsl:when test="contains(@class, 'textbox')">
				<text:p text:style-name="Standard">
					<draw:frame draw:style-name="textbox" text:anchor-type="paragraph" svg:width="6.268in" draw:z-index="0">
						<draw:text-box fo:min-height="1in">
								<xsl:apply-templates/>
						</draw:text-box>
					</draw:frame>
				</text:p>
			</xsl:when>
			<!--Sidebar KK Gupta-->
			<xsl:when test="contains(@class, 'sidebar')">
				<text:p text:style-name="Standard">
					<draw:frame draw:style-name="sidebar" text:anchor-type="paragraph" svg:width="6.268in" draw:z-index="0">
						<draw:text-box fo:min-height="1in">
								<xsl:apply-templates/>
						</draw:text-box>
					</draw:frame>
				</text:p>
			</xsl:when>
			<xsl:when test="@class='myclass'">
				<text:p text:style-name="Standard">
					<xsl:apply-templates/>
				</text:p>
			</xsl:when>
			<xsl:when
				test="(descendant::*)|(ancestor::*[matches(local-name(), '(blockquote|p|h[1-6]|div)')])">
				<xsl:apply-templates/>
			</xsl:when>
			<xsl:otherwise>
				<text:p>
					<xsl:apply-templates/>
				</text:p>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="dl">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:template match="dd|dt">
		<text:p>
			<xsl:apply-templates/>
		</text:p>
	</xsl:template>

	<xsl:template match="ul">
		<text:list>
			<xsl:apply-templates/>
		</text:list>
	</xsl:template>

	<xsl:template match="ol">
		<text:list text:style-name="List1">
			<xsl:apply-templates/>
		</text:list>
	</xsl:template>

	<!--<xsl:template match="li">
		<text:list-item>
		<xsl:choose>
		<xsl:when test="descendant::*[matches(local-name(), '(span|p|h[1-6]|ul)')]">
		<xsl:apply-templates/>
		</xsl:when>
		<xsl:otherwise>
		<text:p>
		<xsl:apply-templates/>
		</text:p>
		</xsl:otherwise>
		</xsl:choose>
		</text:list-item>
		</xsl:template>-->

	<xsl:template match="li">
		<xsl:if test="not (contains(@class, 'display-none') and contains(@class, 'part'))">
			<text:list-item>
				<!--<xsl:if test="a[starts-with(./@href, '#')] and ancestor::div[@id='toc']">
					<xsl:attribute name="text:style-name">List-none</xsl:attribute>
				</xsl:if>-->

				<xsl:choose>
					<!--<xsl:when test="a[starts-with(., '#')] and ancestor::div[@id=toc]">-->
					<xsl:when test="a[starts-with(./@href, '#')] and ancestor::div[@id='toc']">
						<text:p>
							<xsl:apply-templates/>
						</text:p>
					</xsl:when>
					<xsl:when test="./(ul|ol) and not(descendant::*[matches(local-name(), '^(p|h[1-6])$')])">
						<!-- span -->
						<text:p>
							<xsl:for-each select="./(ul|ol)/preceding-sibling::node()">
								<xsl:apply-templates select="."/>
							</xsl:for-each>
						</text:p>
						<xsl:apply-templates select="./(ul|ol)"/>
						<xsl:if test="./(ul|ol)/following-sibling::node()">
							<text:p>
								<xsl:for-each select="./(ul|ol)/following-sibling::node()">
									<xsl:apply-templates select="."/>
								</xsl:for-each>
							</text:p>
						</xsl:if>
					</xsl:when>
					<xsl:when test="descendant::*[matches(local-name(), '^(p|h[1-6])$')]">
						<!-- span -->
						<xsl:apply-templates/>
					</xsl:when>
					<xsl:otherwise>
						<text:p>
							<xsl:apply-templates/>
						</text:p>
					</xsl:otherwise>
				</xsl:choose>
			</text:list-item>
		</xsl:if>
	</xsl:template>

	<xsl:template match="pre">
		<text:p>
			<xsl:apply-templates/>
		</text:p>
	</xsl:template>

	<xsl:template match="code">
		<text:span text:style-name="code">
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="text()">
		<xsl:choose>
			<xsl:when test="not(ancestor::*[matches(local-name(), '(dl|span|blockquote|p|li|h[1-6])')]) and (following-sibling::* or preceding-sibling::*)">
				<text:p>
					<xsl:value-of select="translate(.,'&#x0d;&#x0a;', '')"/>
				</text:p>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="translate(.,'&#x0d;&#x0a;', '')"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	<xsl:template match="br">
		<xsl:if test="ancestor::p">
			<text:line-break/>
		</xsl:if>
	</xsl:template>

	<xsl:template match="blockquote">
		<xsl:choose>
			<xsl:when test="descendant::p">
				<xsl:apply-templates/>
			</xsl:when>
			<xsl:otherwise>
				<text:p text:style-name="blockquote">
					<xsl:apply-templates/>
				</text:p>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!--<xsl:template match="table|tr|td|th"/>-->
	<xsl:template match="caption" mode="tablecaption">
		<text:p text:style-name="Standard">
			<xsl:apply-templates/>
		</text:p>
	</xsl:template>

	<xsl:template match="caption">
		<!--Do Nothing-->
	</xsl:template>

	<xsl:template match="table">
		<xsl:if test="caption">
			<xsl:apply-templates select="caption" mode="tablecaption"/>
		</xsl:if>
		<table:table>
			<!--<table:table-column table:number-columns-repeated="{@colcount}"/>-->
			<!--KK Gupta-->
			<xsl:choose>
				<xsl:when test="@colcount">
					<table:table-column table:number-columns-repeated="{@colcount}"/>
				</xsl:when>
				<xsl:otherwise>
					<table:table-column table:number-columns-repeated="1"/>
				</xsl:otherwise>
			</xsl:choose>
			<xsl:apply-templates/>
		</table:table>
	</xsl:template>

	<xsl:template match="tr">
		<xsl:for-each select=".">
			<table:table-row>
				<xsl:apply-templates/>
			</table:table-row>
		</xsl:for-each>
	</xsl:template>

	<xsl:template match="th">
		<table:table-cell office:value-type="string">
			<text:p text:style-name="P1">
				<xsl:apply-templates/>
			</text:p>
		</table:table-cell>
	</xsl:template>

	<xsl:template match="td">
		<table:table-cell office:value-type="string">
			<xsl:if test="@row-span">
				<xsl:attribute name="table:number-rows-spanned">
					<xsl:value-of select="@row-span"/>
				</xsl:attribute>
			</xsl:if>
			<xsl:if test="@col-span">
				<xsl:attribute name="table:number-columns-spanned">
					<xsl:value-of select="@col-span"/>
				</xsl:attribute>
			</xsl:if>
			<text:p text:style-name="P1">
				<xsl:apply-templates/>
			</text:p>
		</table:table-cell>
	</xsl:template>

	<xsl:template match="img">
		<xsl:choose>
			<xsl:when test="parent::p">
				<draw:frame draw:style-name="imageFrame1" text:anchor-type="paragraph" svg:x="0in"
					svg:y="0in" style:rel-width="scale" style:rel-height="scale">
					<xsl:attribute name="svg:width">
						<xsl:choose>
							<xsl:when test="@width">
								<xsl:choose>
									<xsl:when test="number((@width div 96)) &gt; 6.26806">
										<xsl:text>6.26806in</xsl:text>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="(@width div 96)"/><xsl:text>in</xsl:text>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>6.26806in</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					<xsl:attribute name="svg:height">
						<xsl:choose>
							<xsl:when test="@height">
								<xsl:choose>
									<xsl:when test="number(@height div 96) &gt; 4.70139">
										<xsl:text>4.70139in</xsl:text>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="(@height div 96)"/><xsl:text>in</xsl:text>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:text>4.70139in</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					<xsl:attribute name="draw:name">
						<xsl:text>Picture </xsl:text>
						<xsl:number/>
					</xsl:attribute>
					<draw:image xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad">
						<xsl:attribute name="xlink:href">
							<xsl:text>media/</xsl:text>
							<xsl:value-of select="escape-html-uri(replace(@src, '(http.+/)(.+?)$', '$2'))"/>
						</xsl:attribute>
					</draw:image>
				</draw:frame>
			</xsl:when>
			<xsl:otherwise>
				<text:p>
					<draw:frame draw:style-name="imageFrame1" text:anchor-type="paragraph"
						svg:x="0in" svg:y="0in" style:rel-width="scale" style:rel-height="scale">
						<xsl:attribute name="svg:width">
							<xsl:choose>
								<xsl:when test="@width">
									<xsl:choose>
										<xsl:when test="number((@width div 96)) &gt; 6.26806">
											<xsl:text>6.26806in</xsl:text>
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="(@width div 96)"/><xsl:text>in</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>6.26806in</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
						<xsl:attribute name="svg:height">
							<xsl:choose>
								<xsl:when test="@height">
									<xsl:choose>
										<xsl:when test="number(@height div 96) &gt; 4.70139">
											<xsl:text>4.70139in</xsl:text>
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="(@height div 96)"/><xsl:text>in</xsl:text>
										</xsl:otherwise>
									</xsl:choose>
								</xsl:when>
								<xsl:otherwise>
									<xsl:text>4.70139in</xsl:text>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
						<xsl:attribute name="draw:name">
							<xsl:text>Picture </xsl:text>
							<xsl:number/>
						</xsl:attribute>
						<draw:image xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad">
							<xsl:attribute name="xlink:href">
								<xsl:text>media/</xsl:text>
								<xsl:value-of select="escape-html-uri(replace(@src, '(http.+/)(.+?)$', '$2'))" />
							</xsl:attribute>
						</draw:image>
						<svg:title><xsl:value-of select="@alt"/></svg:title>
					</draw:frame>
					<xsl:choose>
						<xsl:when test="following-sibling::p[@class='wp-caption-text']">
							<xsl:apply-templates select="following-sibling::p[@class='wp-caption-text']" mode="captionMode"/>
						</xsl:when>
						<xsl:when test="ancestor::div[1]/p[@class='wp-caption-text']">
							<xsl:apply-templates select="ancestor::div[1]/p[@class='wp-caption-text']" mode="captionMode"/>
						</xsl:when>
					</xsl:choose>
				</text:p>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="em">
		<text:span>
			<xsl:attribute name="text:style-name">
				<xsl:text>italic</xsl:text>
			</xsl:attribute>
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="strong">
		<text:span text:style-name="bold">
			<xsl:apply-templates/>
		</text:span>
	</xsl:template>

	<xsl:template match="a">
		<xsl:choose>
			<xsl:when test="starts-with(@href, '#_ednref')">
				<!-- Do nothing here it will captured in the endnote section -->
			</xsl:when>
			<xsl:when test="starts-with(@href, '#_edn')">
				<xsl:variable name="endRef">
					<xsl:value-of select="concat('#_ednref', replace(@href, '#_edn', ''))"/>
				</xsl:variable>
				<text:note text:note-class="endnote"
					text:id="{concat('edn', replace(@href, '#_edn', ''))}">
					<text:note-citation>
						<xsl:value-of select="."/>
					</text:note-citation>
					<text:note-body>
						<text:p text:style-name="Endnote">
							<xsl:value-of select="//a[@href=$endRef]/parent::p"/>
						</text:p>
					</text:note-body>
				</text:note>
			</xsl:when>
			<xsl:when test="starts-with(@href, '#') and ancestor::div[@id='toc']">
				<text:a xlink:type="simple" xlink:href="{@href}" text:style-name="Internet_link"
					text:visited-style-name="Visited_Internet_Link">
					<xsl:apply-templates/>
				</text:a>
			</xsl:when>
			<xsl:when test="contains(@href, 'http') and not(./img)">
				<text:a xlink:href="{@href}">
					<xsl:apply-templates/>
				</text:a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

</xsl:stylesheet>
