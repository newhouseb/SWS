<?php
include('library.php');
// Get the relative path of the current directory
// TODO: Replace this with PHP_SELF?
$path = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));
$path = substr($path, 0, strlen($path) - strlen("index.php"));

// Check to see if we need to statically compile this
$xml = new DOMDocument();
$xml->load('template.sws');
$xml_xpath = new DOMXPath($xml);
if($xml->documentElement->getAttribute("urlpath") && $xml->documentElement->getAttribute("filepath")) {
    compilePages('template.sws', $xml->documentElement->getAttribute("filepath"), $xml->documentElement->getAttribute("urlpath"));
}

// Render the page
echo renderPage('template.sws',substr(urldecode($_REQUEST['p']), strlen($path)),$_GET['rw'], $path);
?>
