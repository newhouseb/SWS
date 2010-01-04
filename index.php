<?php
include('library.php');
//echo $_SERVER['DOCUMENT_ROOT'];
$path = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));
$path = substr($path, 0, strlen($path) - strlen("index.php"));

echo renderPage('template.sws',substr(urldecode($_REQUEST['p']), strlen($path)),$_GET['rw'], $path);
?>
