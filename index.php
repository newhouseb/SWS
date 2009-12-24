<?php
/*
Copyright (c) Ben Newhouse.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, 
       this list of conditions and the following disclaimer.
    
    2. Redistributions in binary form must reproduce the above copyright 
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    3. Neither the name of Django nor the names of its contributors may be used
       to endorse or promote products derived from this software without
       specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/


function populate(&$html, &$dict, &$links, &$path) {
    if(!$html || $html->nodeType == XML_TEXT_NODE) return;
    
    // If mod_rewrite is not enabled, modify links
    if($html->nodeName == 'a' && $html->nodeType == XML_ELEMENT_NODE) {
        $target = substr($html->getAttribute("href"),0);
        if($target[0] != '/') {
            $target = $path.'/'.$target;
        }
        if(in_array($target, $links)) {
            if(!$_GET['rw'])
                $html->getAttributeNode("href")->nodeValue = "/?p=".$target;
            else 
                $html->getAttributeNode("href")->nodeValue = $target;
        }
    }       

    for($i = 0; $i < $html->childNodes->length; $i++) {
        $element = $html->childNodes->item($i);
        if($element->nodeType == XML_ELEMENT_NODE) {

            // ID and Class innerHTML replacement
            $replacement = $dict[$element->getAttribute('id')];
            if(!$replacement) $replacement = $dict[$element->getAttribute('class')];
            if($replacement) {
                // Clear out old nodes
                foreach($element->childNodes as $child) {
                    $element->removeChild($child);
                }

                // Add in new ones
                foreach($replacement->childNodes as $child) {
                    $element->appendChild($html->ownerDocument->importNode($child, TRUE));
                }
            }

            // Taking care of entire tag replacement
            if(!$replacement) { 
                $replacement = $dict[$element->tagName];
                if($replacement) {
                    populate($imported_element, $dict, $links, $path);
                    foreach($replacement->childNodes as $child) {
                        $imported_element = $html->ownerDocument->importNode($child, TRUE);
                        $element->parentNode->insertBefore($imported_element, $element);
                    }
                    $element->parentNode->removeChild($element);
                }
            }
            populate($element, $dict, $links, $path);
        }
    }
}

// Get Simple Website Schema
$xml = new DOMDocument();
$xml->load("template.sws");
$xml_xpath = new DOMXPath($xml);

// Get the template
$template = $xml->documentElement->getAttribute("template");

// Load the template
$html = new DOMDocument();
$html->load($template);
$html_xpath = new DOMXPath($html);

// Load the default page (the first one)
$requested_page_name = str_replace(" ","_",preg_replace("/[^_.\/a-zA-Z0-9 ']/","",strtolower(rtrim(urldecode($_REQUEST['p']),"/"))));
if($requested_page_name[0] == '/') $requested_page_name = substr($requested_page_name,1);
$requested_page = $xml_xpath->query("/site/page")->item(0);

// Query for the actual page
$sections = explode("/", $requested_page_name);
// This is largely to make the search case insensitive
$query = "/site/page[translate(@link,'ABCDEFGHIJKLMNOPQRSTUVWXYZ ','abcdefghijklmnopqrstuvwxyz_') =\"".implode("\"]/subpages/page[translate(@link, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ ', 'abcdefghijklmnopqrstuvwxyz_') =\"",$sections)."\"]";
$query = $xml_xpath->query($query);
if($query->item(0))
    $requested_page = $query->item(0);

// Start scoping variables
$variables = array();
$links = array();

// Add breadcrumb variables
$path_elements = array();
$count = count($sections) - 1;
$ptr = $requested_page;
while($ptr->nodeName != "site") {
    if($ptr->getAttribute("link")) {
        $path = $ptr->getAttribute("link");
        $path_elements[] = $path;

        $node = $xml->createElement(str_repeat("sub",$count)."path");
        $node->appendChild($xml->createTextNode($path));
        $variables[str_repeat("sub",$count)."path"] = $node;
        $count--;
    }
    $ptr = $ptr->parentNode;
}

// Add variables from page
foreach($requested_page->childNodes as $element) {
    if($element->nodeName != "#text")
        $variables[$element->nodeName] = $element;    
}

// If the page has subpages, set the scope to be of the subpages, otherwise, the current page
$scope;
$offset = 0;
if($xml_xpath->query("subpages",$requested_page)->item(0)) {
    $scope = $xml_xpath->query("subpages", $requested_page)->item(0);
    $offset += 1;
} else {
    $scope = $requested_page;
}

for($i = 0; $i < 2*(count($sections)) + $offset; $i++) {
    // Add variables from owner (if not already defined)
    foreach($scope->childNodes as $element) {
        if(!in_array((string)$element->nodeName,array("#text","variables","page","subpages"))) {
            if(!$variables[$element->nodeName]) {
                $variables[$element->nodeName] = $element;    
            }
        }
    }

    // Pages must be in a subpages node, thus we should bail here
    if ($scope->nodeName == "page") {
        $scope = $scope->parentNode;
        continue;
    }

    // Trace current path
    $path = "";
    $ptr = $scope;
    while($ptr->nodeName != "site") {
        if($ptr->getAttribute("link"))
           $path = ((string) $ptr->getAttribute("link"))."/".$path;
        $ptr = $ptr->parentNode;
    }
    $path = "/".$path;

    // Calculated how many "sub"s to add to this set of links (TODO: fix up this grossness)
    $prefix = str_repeat("sub", (2*count($sections) - 1 - $i)/2 + $offset);

    // Add all pages to sublinks  
    $menu_links = $xml->createElement($prefix."links");

    // Extract the links
    $query = $xml_xpath->query("page", $scope);
    foreach($query as $page) {
        $link = (string) $page->getAttribute("link");
        $links[] = $path.$link;

        // If they're not hidden, link them
        if($page->getAttribute("hidden") != "true") {
            $ref = $page->getAttribute("ref");

            $link_node = $xml->createElement("a");
            $link_href = $xml->createAttribute("href");
            $link_href->appendChild($xml->createTextNode($ref ? $ref : $path.$link));
            $link_node->appendChild($link_href);
            $link_node->appendChild($xml->createTextNode($link));

            if(in_array($link, $path_elements)) {
                $link_style = $xml->createAttribute("class");
                $link_style->appendChild($xml->createTextNode("selected"));
                $link_node->appendChild($link_style);
            }

            $menu_links->appendChild($link_node);
            $menu_links->appendChild($xml->createTextNode(" "));
        }
    }

    $variables[$prefix."links"] = $menu_links;

    // Pop to parent
    $scope = $scope->parentNode;
}

// Build the all encompassing breadcrum
$path = "path";
$breadcrumb = $xml->createElement("breadcrumb");
while($variables[$path]) {
    $container = $xml->createElement("div");
    $source = $variables[$path];
    foreach($source->childNodes as $child) {
        $container->appendChild($child->cloneNode(TRUE));
    }
    $breadcrumb->appendChild($container); 
    $path = "sub".$path; 
}
$variables["breadcrumb"] = $breadcrumb;

// Build the all encompassing links_list
$path = "links";
$alllinks = $xml->createElement("alllinks");
while($variables[$path]) {
    $container = $xml->createElement("div");
    $source = $variables[$path];
    foreach($source->childNodes as $child) {
        $container->appendChild($child->cloneNode(TRUE));
    }
    $alllinks->appendChild($container); 
    $path = "sub".$path; 
}
$variables["alllinks"] = $alllinks;

// Set the title
$site_title = $xml->documentElement->getAttribute('title');
$page_title = $requested_page->getAttribute('title');
$html_xpath->query("/html/head/title")->item(0)->nodeValue = $site_title.' '.$page_title;

// Set the css
$css = $html->createElement("link");
$css_href = $html->createAttribute("href");
$css_href->appendChild($html->createTextNode($xml->documentElement->getAttribute('css')));
$css_rel = $html->createAttribute("rel");
$css_rel->appendChild($html->createTextNode("stylesheet"));
$css_type = $html->createAttribute("type");
$css_type->appendChild($html->createTextNode("text/css"));
$css->appendChild($css_href);
$css->appendChild($css_rel);
$css->appendChild($css_type);
$html_xpath->query("/html/head")->item(0)->appendChild($css);

//print_r($variables);
//print_r($links);

// Substitute all the IDs in the html with the corresponding page elements
$path = "/".implode("/",array_reverse($path_elements));
populate($html, $variables, $links, $path);

echo $html->saveHTML();
?>
