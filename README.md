# DeliciousExport

Parser for del.icio.us export files

## Example

    <?php

    // Your Configuration
    $parserFile = '../vendor/adibaba/delicious-export/src/HtmlParser.php';
    $htmlFile   = '/tmp/delicious.html';
    
    // Preparation
    require_once $parserFile;
    echo '<pre>';
    
    // Create the parser
    $htmlParser = new Adibaba\DeliciousExport\HtmlParser($htmlFile);
    
    // Parse your HTML file and print your bookmarks
    print_r($htmlParser->parse()->getBookmarks());
    
    // Your HTML file has already been parsed. Just print your tags.
    print_r($htmlParser->getTags());
    
    // Print your tags, sorted by number of containing bookmarks
    print_r($htmlParser->getTags($true));
    
    ?>
