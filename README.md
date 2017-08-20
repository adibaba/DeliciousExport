# DeliciousExport

The social bookmarking service Delicious ([del.icio.us](https://del.icio.us/)) was acquired by Pinboard (see [blog post](https://blog.pinboard.in/2017/06/pinboard_acquires_delicious/)) and is in read-only mode now.
You can export your delicious bookmarks ([del.icio.us/export](https://del.icio.us/export)) and save them in a HTML file.
This script parses those files.

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
