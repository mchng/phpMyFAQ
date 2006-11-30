<?php
/**
* $Id: opensearch.php,v 1.1 2006-11-19 01:04:18 thorstenr Exp $
*
* This is XML code for OpenSearch
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2006-11-19
* @copyright:   (c) 2006 phpMyFAQ Team
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

define('PMF_ROOT_DIR', dirname(__FILE__));

require_once(PMF_ROOT_DIR.'/inc/Init.php');
require_once(PMF_ROOT_DIR.'/inc/Link.php');

$plugin_icon    = $_SERVER['HTTP_HOST'].'.pmfsearch.png';
$baseUrl        = PMF_Link::getSystemUri('/index.php');
$search_url     = $baseUrl.'/index.php?action=search';
$src_url        = $baseUrl;

$opensearch     = "<?xml version=\"1.0\" encoding=\"".$PMF_LANG['metaCharset']."\"?>
<OpenSearchDescription xmlns=\"http://a9.com/-/spec/opensearch/1.1/\">
<ShortName>".$faqconfig->get('title')."</ShortName>
<Description>".$faqconfig->get('metaDescription')."</Description>
<Url type=\"text/html\" template=\"".$search_url."&amp;search={searchTerms}\" />
<Language>".$PMF_LANG['metaLanguage']."</Language>
<OutputEncoding>".$PMF_LANG['metaCharset']."</OutputEncoding>
<Contact>".$faqconfig->get('adminmail')."</Contact>
<Image height=\"16\" width=\"16\" type=\"image/png\">".$baseUrl."/images/".$plugin_icon."</Image>
</OpenSearchDescription>";

header("Content-type: text/xml");
print $opensearch;