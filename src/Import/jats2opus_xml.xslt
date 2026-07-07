<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:php="http://php.net/xsl">

    <xsl:output method="xml" omit-xml-declaration="yes" indent="yes" encoding="utf-8"/>

    <xsl:variable name="lang" select="php:functionString('Opus\Xml\PhpFunctions::getPart2b', /article/@xml:lang)"/>

    <xsl:template match="/">
        <import>
            <opusDocument>
                <xsl:attribute name="language">
                    <xsl:value-of select="$lang"/>
                </xsl:attribute>
                <xsl:attribute name="type">
                    <xsl:text>article</xsl:text>
                </xsl:attribute>
                <xsl:if test="//article-meta/fpage">
                    <xsl:attribute name="pageFirst">
                        <xsl:value-of select="//article-meta/fpage"/>
                    </xsl:attribute>
                </xsl:if>
                <xsl:if test="//article-meta/lpage">
                    <xsl:attribute name="pageLast">
                        <xsl:value-of select="//article-meta/lpage"/>
                    </xsl:attribute>
                </xsl:if>
                <xsl:if test="//article-meta/volume">
                    <xsl:attribute name="volume">
                        <xsl:value-of select="//article-meta/volume"/>
                    </xsl:attribute>
                </xsl:if>
                <xsl:if test="//article-meta/issue">
                    <xsl:attribute name="issue">
                        <xsl:value-of select="//article-meta/issue"/>
                    </xsl:attribute>
                </xsl:if>
                <xsl:attribute name="publisherName">
                    <xsl:value-of select="//journal-meta/publisher/publisher-name"/>
                </xsl:attribute>
                <xsl:if test="//journal-meta/publisher/publisher-loc">
                    <xsl:attribute name="publisherPlace">
                        <xsl:value-of select="//journal-meta/publisher/publisher-loc"/>
                    </xsl:attribute>
                </xsl:if>
                <xsl:attribute name="belongsToBibliography">
                    <xsl:text>false</xsl:text>
                </xsl:attribute>
                <xsl:attribute name="serverState">
                    <xsl:text>unpublished</xsl:text>
                </xsl:attribute>
                <xsl:if test="//article-meta/counts/page-count/@count">
                    <xsl:attribute name="pageNumber">
                    <xsl:value-of select="//article-meta/counts/page-count/@count"/>
                    </xsl:attribute>
                </xsl:if>

                <titlesMain>
                    <titleMain>
                        <xsl:attribute name="language"><xsl:value-of select="$lang"/></xsl:attribute>
                        <xsl:value-of select="normalize-space(//article-meta/title-group/article-title)"/>
                    </titleMain>
                    <xsl:for-each select="//article-meta/title-group/trans-title-group/trans-title">
                        <titleMain>
                            <xsl:call-template name="insert-lang-attrib"/>
                            <xsl:value-of select="normalize-space()"/>
                        </titleMain>
                    </xsl:for-each>
                </titlesMain>
                <titles>
                    <xsl:for-each select="//journal-meta//journal-title">
                        <title>
                            <xsl:attribute name="language"><xsl:value-of select="$lang"/></xsl:attribute>
                            <xsl:attribute name="type"><xsl:text>parent</xsl:text></xsl:attribute>
                            <xsl:value-of select="normalize-space(text())"/>
                        </title>
                    </xsl:for-each>
                     <xsl:if test="//article-meta/title-group/subtitle[not(@content-type='running-title') and not(@content-type='running-author')]">
                        <!-- exclude page headers from Springer -->
                        <title>
                        <xsl:attribute name="language"><xsl:value-of select="$lang"/></xsl:attribute>
                        <xsl:attribute name="type"><xsl:text>sub</xsl:text></xsl:attribute>
                        <xsl:value-of select="normalize-space(//article-meta/title-group/subtitle[not(@content-type='running-title') and not(@content-type='running-author')])"/>
                        </title>
                    </xsl:if>
                    <xsl:for-each select="//article-meta/title-group/trans-title-group/trans-subtitle">
                        <title>
                        <xsl:call-template name="insert-lang-attrib"/>
                        <xsl:attribute name="type"><xsl:text>sub</xsl:text></xsl:attribute>
                        <xsl:value-of select="normalize-space()"/>
                        </title>
                    </xsl:for-each>
                </titles>

                <xsl:if test="//article-meta/abstract[not(@abstract-type='graphical')
                                            and not(@abstract-type='toc')
                                            and not(@abstract-type='precis')]
                                        or //article-meta/trans-abstract">
                    <abstracts>
                        <xsl:if test="//article-meta/abstract[not(@abstract-type='graphical')
                                              and not(@abstract-type='toc')
                                              and not(@abstract-type='precis')]">
                            <xsl:for-each select="//article-meta/abstract[not(@abstract-type='graphical')
                                    and not(@abstract-type='toc')
                                    and not(@abstract-type='precis')][1]">
                                <abstract>
                                    <!-- selecting the first non-toc non-grapical non-precis abstract -->
                                    <xsl:attribute name="language"><xsl:value-of select="$lang"/></xsl:attribute>
                                    <xsl:call-template name="insert-abstract"/>
                                </abstract>
                            </xsl:for-each>
                        </xsl:if>
                        <xsl:for-each select="//article-meta/trans-abstract">
                            <abstract>
                                <xsl:call-template name="insert-lang-attrib"/>
                                <xsl:call-template name="insert-abstract"/>
                            </abstract>
                        </xsl:for-each>
                  </abstracts>
                </xsl:if>
                <persons>
                    <xsl:for-each select="//article-meta/contrib-group/contrib">
                        <xsl:if test="string-length(normalize-space(.//surname/text()))>0 and
                                                (@contrib-type='guest-editor' or
                                                @contrib-type='editor' or
                                                @contrib-type='author')">
                            <person>
                                <xsl:attribute name="role">
                                    <xsl:choose>
                                        <xsl:when test="@contrib-type='guest-editor'">
                                            <xsl:text>editor</xsl:text>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:value-of select="@contrib-type"/>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:attribute>
                                <xsl:attribute name="firstName"><xsl:value-of select=".//given-names"/></xsl:attribute>
                                <xsl:attribute name="lastName"><xsl:value-of select=".//surname"/></xsl:attribute>
                                <xsl:if test=".//email">
                                    <xsl:attribute name="email"><xsl:value-of select=".//email"/></xsl:attribute>
                                </xsl:if>
                                <xsl:if test="./contrib-id[@contrib-id-type='orcid']">
                                    <identifiers>
                                        <identifier type='orcid'>
                                        <xsl:variable name='orcid' select="./contrib-id[@contrib-id-type='orcid']/text()"/>
                                        <xsl:choose>
                                            <xsl:when test="substring($orcid, string-length($orcid))='/'">
                                                <xsl:variable name="orcid2" select="substring($orcid, 1, string-length($orcid)-1)"/>
                                                <xsl:call-template name="insert-orcid">
                                                <xsl:with-param name="orcid" select="$orcid2"/>
                                                </xsl:call-template>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:call-template name="insert-orcid">
                                                <xsl:with-param name="orcid" select="$orcid"/>
                                                </xsl:call-template>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                    </identifier>
                                </identifiers>
                            </xsl:if>
                            </person>
                        </xsl:if>
                    </xsl:for-each>
                </persons>
                <keywords>
                    <keyword>
                        <xsl:attribute name="language"><xsl:value-of select="$lang"/></xsl:attribute>
                        <xsl:attribute name="type"><xsl:text>swd</xsl:text></xsl:attribute>
                        <xsl:text>-</xsl:text>
                    </keyword>
                    <xsl:for-each select="//article-meta/kwd-group/kwd">
                        <xsl:if test="string-length(normalize-space(text()))>0">
                            <keyword>
                                <xsl:attribute name="language"><xsl:value-of select="$lang"/></xsl:attribute>
                                <xsl:attribute name="type"><xsl:text>uncontrolled</xsl:text></xsl:attribute>
                                <xsl:value-of select="normalize-space(text())"/>
                            </keyword>
                        </xsl:if>
                    </xsl:for-each>
                </keywords>

                <dates>
                    <xsl:for-each select="//article-meta/pub-date">
                        <xsl:choose>
                            <xsl:when test="(contains(@pub-type,'epub') and year) or
                                        (contains(@pub-type,'ppub') and year) or
                                        (contains(@pub-type, 'epub-ppub') and year) or
                                        (contains(@date-type,'pub') and year) or
                                        (not(@*) and year)">
                                <xsl:call-template name="compose-date">
                                </xsl:call-template>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:if test="not(year)">
                                    <!--to comply with opus requirement that a date has to be given-->
                                    <date>
                                        <xsl:attribute name="type"><xsl:text>completed</xsl:text></xsl:attribute>
                                        <xsl:attribute name="monthDay">
                                            <xsl:text>--11-11</xsl:text>
                                        </xsl:attribute>
                                        <xsl:attribute name="year">
                                            <xsl:text>1111</xsl:text>
                                        </xsl:attribute>
                                    </date>
                                </xsl:if>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:for-each>
                </dates>
                <identifiers>
                    <xsl:for-each select="//journal-meta/issn[@pub-type='ppub' or @pub-type='epub' or @publication-format='print' or @publication-format='electronic']">
                        <identifier>
                            <xsl:attribute name="type"><xsl:text>issn</xsl:text></xsl:attribute>
                            <xsl:value-of select="normalize-space(text())"/>
                        </identifier>
                    </xsl:for-each>
                    <xsl:if test="//article-meta/article-id[@pub-id-type='doi']">
                        <identifier>
                            <xsl:attribute name="type"><xsl:text>doi</xsl:text></xsl:attribute>
                            <xsl:value-of select="//article-meta/article-id[@pub-id-type='doi']"/>
                        </identifier>
                    </xsl:if>
                    <xsl:if test="//article-meta/article-id[@pub-id-type='pmid']">
                        <identifier>
                            <xsl:attribute name="type"><xsl:text>pmid</xsl:text></xsl:attribute>
                            <xsl:value-of select="//article-meta/article-id[@pub-id-type='pmid']"/>
                        </identifier>
                    </xsl:if>
                </identifiers>
            </opusDocument>
        </import>
    </xsl:template>

    <xsl:template name="compose-date">
        <xsl:param name="xpath" select="."/>
        <date>
            <xsl:attribute name="type"><xsl:text>published</xsl:text></xsl:attribute>
            <xsl:attribute name="monthDay">
                <xsl:text>--</xsl:text>
                <xsl:choose>
                    <!-- <xsl:when test="//article-meta/pub-date[contains(@pub-type,$xpub)]/month"> -->
                    <xsl:when test="$xpath/month">
                        <xsl:value-of select="php:functionString('Opus\Xml\PhpFunctions::formatMonth', $xpath/month)"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text>12</xsl:text>
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:text>-</xsl:text>
                <xsl:choose>
                    <xsl:when test="$xpath/day">
                        <xsl:value-of select="format-number($xpath/day,'00')"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:text>01</xsl:text>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>
            <xsl:attribute name="year">
                <xsl:value-of select="$xpath/year"/>
            </xsl:attribute>
        </date>
    </xsl:template>

    <xsl:template name="insert-orcid">
        <xsl:param name="orcid"/>
        <!-- This template accepts an url as input and selects the substring after the last "/".
        orcids consist of four 4-digit blocks, separated by dashes. i.e. the resulting string should be precisely 19 characters long.
        Lacking any regex capabilities in xslt 1.0, the template makes a last check for string-length before returning the orcid-id.
        Recursive template, as substring-after() can only ever select the substring after the first instance of a character.
        -->
        <xsl:choose>
        <xsl:when test="not(contains($orcid,'/'))">
            <xsl:if test="string-length($orcid)=19">
            <xsl:value-of select="$orcid"/>
            </xsl:if>
        </xsl:when>
        <xsl:otherwise>
            <xsl:call-template name="insert-orcid">
            <xsl:with-param name="orcid" select="substring-after($orcid,'/')"/>
            </xsl:call-template>
        </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="insert-lang-attrib">
    <!-- try to insert local language attribute, or fallback to global language variable -->
        <xsl:choose>
            <xsl:when test="@xml:lang">
                <xsl:attribute name="language">
                    <xsl:value-of select="php:functionString('Opus\I18n\Languages::getPart2b', @xml:lang)"/>
                </xsl:attribute>
            </xsl:when>
            <xsl:when test="../@xml:lang">
                <xsl:attribute name="language">
                    <xsl:value-of select="php:functionString('Opus\I18n\Languages::getPart2b', ../@xml:lang)"/>
                </xsl:attribute>
            </xsl:when>
            <xsl:otherwise>
                <xsl:attribute name="language"><xsl:value-of select="$lang"/></xsl:attribute>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="insert-abstract">
        <!-- Transform abstract content to normalize whitespacing between child elements.
        Any tex-math content is exluded and title "Abstract" as well.
        -->
    <xsl:choose>
      <xsl:when test="descendant::sub">
      <!-- abstract contains chemical formulas, no whitespace normalization will be done -->
        <xsl:for-each select="descendant-or-self::text()">
          <xsl:if test="string-length(normalize-space())&gt;0">
            <xsl:choose>
              <xsl:when test="local-name(parent::*)='tex-math'
                              or (local-name(parent::*)='title' and .='Abstract')">
              <!--ignore tex-math elements and skip "Abstract" headers-->
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="."/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:if>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <xsl:for-each select="descendant-or-self::text()">
          <xsl:if test="string-length(normalize-space())&gt;0">
            <xsl:choose>
              <xsl:when test="local-name(parent::*)='title' and not(.='Abstract')">
              <!-- when text of title element is selected, add line breaks before and after-->
                <xsl:text>
                </xsl:text>
                <xsl:value-of select="normalize-space()"/>
                <xsl:text>
                </xsl:text>
              </xsl:when>
              <xsl:when test="local-name(parent::*)='tex-math'
                              or (local-name(parent::*)='title' and .='Abstract')">
              <!--ignore tex-math elements and skip "Abstract" headers-->
              </xsl:when>
              <xsl:when test="contains(name(parent::*),'mml:')">
                <xsl:value-of select="."/>
              </xsl:when>
              <xsl:otherwise> <!--otherwise select text and add a whitespace afterward-->
                <xsl:value-of select="normalize-space()"/>
                <xsl:text> </xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:if>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>


</xsl:stylesheet>
