<?php
/**
 * The RSS feed with the top ten.
 *
 * @package    phpMyFAQ
 * @subpackage RSS
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @version    SVN: $Id$ 
 * @copyright  2004-2009 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

define('PMF_ROOT_DIR', dirname(dirname(dirname(__FILE__))));

require_once(PMF_ROOT_DIR.'/inc/Init.php');
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

//
// get language (default: english)
//
$Language = new PMF_Language();
$LANGCODE = $Language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
// Preload English strings
require_once (PMF_ROOT_DIR.'/lang/language_en.php');

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    // Overwrite English strings with the ones we have in the current language
    require_once(PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php');
} else {
    $LANGCODE = 'en';
}

$faq     = new PMF_Faq();
$rssData = $faq->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN);
$num     = count($rssData);

$rss = new XMLWriter();
$rss->openMemory();
$rss->setIndent(true);

$rss->startDocument('1.0', $PMF_LANG['metaCharset']);
$rss->startElement('rss');
$rss->writeAttribute('version', '2.0');
$rss->startElement('channel');
$rss->writeElement('title', utf8_encode($PMF_CONF['main.titleFAQ']) . ' - ' . utf8_encode($PMF_LANG['msgTopTen']));
$rss->writeElement('description', utf8_encode($PMF_CONF['main.metaDescription']));
$rss->writeElement('link', PMF_Link::getSystemUri('/feed/topten/rss.php'));

if ($num > 0) {
    $i = 0;
    foreach ($rssData as $item) {
        $i++;
        // Get the url
        $link = str_replace($_SERVER['PHP_SELF'], '/index.php', $item['url']);
        if (PMF_RSS_USE_SEO) {
            if (isset($item['thema'])) {
                $oL = new PMF_Link($link);
                $oL->itemTitle = $item['thema'];
                $link = $oL->toString();
            }
        }

        $rss->startElement('item');
        $rss->writeElement('title', utf8_encode(PMF_Utils::makeShorterText($item['thema'], 8)." (".$item['visits']." ".$PMF_LANG['msgViews'].")"));
        
        $rss->startElement('description');
        $rss->writeCdata(utf8_encode("[".$i.".] ".$item['thema']." (".$item['visits']." ".$PMF_LANG['msgViews'].")"));
        $rss->endElement();
        
        $rss->writeElement('link', utf8_encode(PMF_Link::getSystemUri('/feed/topten/rss.php').$link));
        $rss->writeElement('pubDate', PMF_Date::createRFC822Date($item['last_visit'], true));
        $rss->endElement();
    }
}

$rss->endElement();
$rss->endElement();
$rssData = $rss->outputMemory();

header('Content-Type: application/rss+xml');
header('Content-Length: '.strlen($rssData));

print $rssData;

$db->dbclose();
