<?php
include('library.php');
echo renderPage('template.sws',urldecode($_REQUEST['p']),$_GET['rw']);
?>
