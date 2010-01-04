<?php
include('library.php');
// Get the relative path of the current directory
$path = $_SERVER['PHP_SELF'];
$path = substr($path, 0, strlen($path) - strlen("index.php"));

// Check to see if we need to statically compile this
$xml = new DOMDocument();
$xml->load('template.sws');
$xml_xpath = new DOMXPath($xml);
if($xml->documentElement->getAttribute("urlpath") && $xml->documentElement->getAttribute("filepath")) {
    compilePages('template.sws', $xml->documentElement->getAttribute("filepath"), $xml->documentElement->getAttribute("urlpath"));
}

// Render the page
$page = urldecode($_REQUEST['p']);
if(strstr($page, $path))
    $page = substr($page, strlen($path));

echo renderPage('template.sws',$page,$_GET['rw'], $path);
?>
