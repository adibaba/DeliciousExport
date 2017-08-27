# DeliciousExport

The social bookmarking service Delicious ([del.icio.us](https://del.icio.us/)) was acquired by Pinboard (see [blog post](https://blog.pinboard.in/2017/06/pinboard_acquires_delicious/)) and is in read-only mode now.
You can export your delicious bookmarks and save them in a HTML file.
This script parses those files.

## Features

- Backup private bookmarks (or exclude them)
- Create a structured HTML file
- Generate JSON file with PHP
- Split tags which were accidentally separated with whitespaces
- Include or exclude bookmark notes/descriptions
- Sort tags by size or name
- Include bookmarks without tags
- Specify the format of date/time
- Adapt templates or extend PHP classes (no private methods)

## Examples

### Preparation

- Install [PHP](http://php.net/manual/en/install.php).
- Download the latest DeliciousExport release at [GitHub](https://github.com/adibaba/DeliciousExport/releases)  
  Decompress the ZIP-file.
- Download your bookmarks at [del.icio.us/export](https://del.icio.us/export).  
  Copy the downloaded *delicious.html* into the folder *src*.
- Create a file *run.php* with an example code below and store it in the folder *src*.  
  Open the console (Windows: Press [Win] + [R], enter *cmd*).  
  Navigate to the *src* directory and enter *php run.php*.

### Create structured HTML bookmarks file

    <?php
    require_once 'HtmlParser.php';
    require_once 'Generator.php';

    $parser = new Adibaba\DeliciousExport\HtmlParser('delicious.html');
    $parser->parse();
    
    $generator = new Adibaba\DeliciousExport\Generator();
    $generator->setHtmlParser($parser);
    file_put_contents('bookmarks.html', $generator->generate());
    ?>

### Create JSON bookmarks file

    <?php
    require_once 'HtmlParser.php';
    $parser = new Adibaba\DeliciousExport\HtmlParser('delicious.html');
    file_put_contents('bookmarks.json', json_encode($parser->parse()->getBookmarks()));
    ?>

### Option for HTML parser: Split tags

A tag 'php code' will become two tags 'php' and 'code'.

    <?php
    require_once 'HtmlParser.php';
    $parser = new Adibaba\DeliciousExport\HtmlParser('delicious.html');
    $parser->setSplitTags(true);
    file_put_contents('bookmarks.json', json_encode($parser->parse()->getBookmarks()));
    ?>

### Options for HTML Generator

The following options are defaults.

    <?php
    require_once 'HtmlParser.php';
    require_once 'Generator.php';

    $parser = new Adibaba\DeliciousExport\HtmlParser('delicious.html');
    $parser->parse();
    
    $generator = new Adibaba\DeliciousExport\Generator();
    $generator->setHtmlParser($parser)
    
    // Templates
    ->setTemplateMain('./templateMain.htm')
    ->setTemplateTag('<li><a href="#_TAG_">_TAG_<span>_TAG_SIZE_</span></a></li>', false)
    ->setTemplateCollection('<h1 id="_TAG_">_TAG_</h1><ul>_BOOKMARKS_</ul>', false)
    ->setTemplateBookmark('./templateBookmark.htm')
    ->setTemplateBookmarkNote('<pre>_BOOKMARK_NOTE_</pre>', false)
    ->setTemplateBookmarkTag('<span><a href="#_BOOKMARK_TAG_">_BOOKMARK_TAG_</a></span>', false)
    
    // Default tag for bookmarks without specified tag
    ->setDefaultNoTags('No-Tags')
    
    // Specifies, if bookmark notes/descriptions are added
    ->setIncludeNotes(true)
    
    // Specifies, if only public bookmarks should be included
    ->setIncludePrivateBookmarks(true)
    
    // Specifies, if HTML entities should be encoded
    ->setEncodeHtml(true)
    
    // Sets representation for date/time
    ->setDateFormat('Y-m-d');
    
    file_put_contents('bookmarks.html', $generator->generate());
    ?>

### Work with PHP objects

    <?php
    require_once 'HtmlParser.php';
    $parser = new Adibaba\DeliciousExport\HtmlParser('delicious.html');
    $parser->parse();
    
    // Bookmarks
    print_r($parser->getBookmarks());
    
    // Tags, sorted by name
    print_r($parser->getTags());
    
    // Tags, sorted by size
    print_r($parser->getTags(true));
    
    // Bookmarks without tags
    print_r($parser->getBookmarkIdsWithoutTags());
    ?>
