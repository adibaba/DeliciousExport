<?php
namespace Adibaba\DeliciousBookmarks\Export;

use DOMDocument;
use Exception;

/**
 * Parser for HTML files exported from del.icio.us social bookmarks
 *
 * @author Adrian Wilke
 */
class HtmlParser
{

    const DATE = 'date';

    const NOTE = 'note';

    const PRIVATE = 'private';

    const TAGS = 'tags';

    const TITLE = 'title';

    const URL = 'url';

    /**
     *
     * @var string Path to bookmarks file
     */
    protected $htmlFile;

    /**
     *
     * @var array
     */
    protected $bookmarks;

    /**
     *
     * @var array Tags -> Array of bookmark IDs
     */
    protected $tags;

    /**
     *
     * @var boolean
     */
    protected $splitTags;

    /**
     *
     * @var string
     */
    protected $currentTag;

    /**
     * Creates new parser for del.icio.us export file
     *
     * @param string $htmlFile
     *            Path to bookmarks file
     */
    function __construct($htmlFile)
    {
        if (! is_readable($htmlFile)) {
            throw new Exception('Can not read ' . $htmlFile);
        }
        $this->htmlFile = $htmlFile;
        $this->bookmarks = array();
        $this->tags = array();
        $this->splitTags = false;
    }

    /**
     * Splits tags which are separated by whitespaces
     *
     * @param boolean $splitTags
     * @return HtmlParser
     */
    public function setSplitTags($splitTags)
    {
        $this->splitTags = $splitTags;
        
        return $this;
    }

    /**
     * Loads export file and extracts bookmark information
     *
     * @return HtmlParser
     */
    public function parse()
    {
        // http://php.net/manual/en/class.domdocument.php
        $domDocument = new DOMDocument();
        $domDocument->loadHTMLFile($this->htmlFile, LIBXML_NOERROR | LIBXML_PARSEHUGE);
        $xml = $domDocument->saveXML();
        
        // http://php.net/manual/en/book.xml.php
        $xmlParser = xml_parser_create();
        xml_set_object($xmlParser, $this);
        xml_set_element_handler($xmlParser, 'xmlParserStartElementHandler', 'xmlParserEndElementHandler');
        xml_set_character_data_handler($xmlParser, 'xmlParserDataHandler');
        xml_parse($xmlParser, $xml, true);
        xml_parser_free($xmlParser);
        
        // Sort tags by name
        uksort($this->tags, 'strcasecmp');
        
        return $this;
    }

    /**
     * Gets array of bookmarks.
     * Every bookmark is represented as array with the following keys:
     * url (string)
     * date (string, timestamp)
     * private (boolean)
     * tags (array)
     * title (string)
     *
     * @return array Bookmarks
     */
    public function getBookmarks()
    {
        return $this->bookmarks;
    }

    /**
     * Gets array of tags.
     * Every tag is represented as array with:
     * key: name of the tag
     * value: array with IDs of bookmarks
     *
     * @param string $sortBySize
     *            Default is alphanumeric
     * @return array Tags
     */
    public function getTags($sortBySize = false)
    {
        if ($sortBySize) {
            $tagsBySize = $this->tags;
            uasort($tagsBySize, array(
                $this,
                'compareArraySize'
            ));
            return $tagsBySize;
        } else {
            return $this->tags;
        }
    }

    /**
     * Helper for tag sorting
     *
     * @param array $a
     * @param array $b
     * @return number
     */
    protected function compareArraySize($a, $b)
    {
        if (count($a) == count($b)) {
            return 0;
        }
        return (count($a) > count($b)) ? - 1 : 1;
    }

    /**
     * Helper for XML parser
     *
     * @param resource $parser
     * @param string $name
     * @param array $attributes
     */
    protected function xmlParserStartElementHandler($parser, $name, $attributes)
    {
        $this->currentTag = $name;
        
        if ($name == 'A') {
            $index = count($this->bookmarks);
            
            $key = 'HREF';
            if (isset($attributes[$key])) {
                $this->bookmarks[$index][self::URL] = $attributes[$key];
            }
            
            $key = 'ADD_DATE';
            if (isset($attributes[$key])) {
                $this->bookmarks[$index][self::DATE] = $attributes[$key];
            }
            
            $key = 'PRIVATE';
            if (isset($attributes[$key])) {
                if ($attributes[$key] === '0') {
                    $this->bookmarks[$index][self::PRIVATE] = false;
                } else {
                    $this->bookmarks[$index][self::PRIVATE] = true;
                }
            }
            
            $key = 'TAGS';
            if (isset($attributes[$key])) {
                $tags = array();
                
                // Do not explode empty string
                if (! empty($attributes[$key])) {
                    $tags = explode(',', $attributes[$key]);
                    $tags = array_map('trim', $tags);
                    
                    // Split tags which are separated by whitespace
                    if ($this->splitTags) {
                        $splittedTags = array();
                        foreach ($tags as $tag) {
                            foreach (preg_split('/\s+/', $tag) as $splittedTag) {
                                if (! in_array($splittedTag, $splittedTags)) {
                                    $splittedTags[] = $splittedTag;
                                }
                            }
                        }
                        $tags = $splittedTags;
                    }
                }
                
                // Bookmarks
                $this->bookmarks[$index][self::TAGS] = $tags;
                
                // Tags
                foreach ($tags as $tag) {
                    if (! array_key_exists($tag, $this->tags)) {
                        $this->tags[$tag] = array();
                    }
                    $this->tags[$tag][] = $index;
                }
            }
        }
    }

    /**
     * Helper for XML parser
     *
     * @param resource $parser
     * @param string $name
     */
    protected function xmlParserEndElementHandler($parser, $name)
    {}

    /**
     * Helper for XML parser
     *
     * @param resource $parser
     * @param string $data
     */
    protected function xmlParserDataHandler($parser, $data)
    {
        $index = count($this->bookmarks) - 1;
        
        if ($this->currentTag == 'A') {
            if (isset($this->bookmarks[$index][self::TITLE])) {
                $this->bookmarks[$index][self::TITLE] .= $data;
            } else {
                $this->bookmarks[$index][self::TITLE] = $data;
            }
        }
        
        if ($this->currentTag == 'DD') {
            if (isset($this->bookmarks[$index][self::NOTE])) {
                $this->bookmarks[$index][self::NOTE] .= $data;
            } else {
                $this->bookmarks[$index][self::NOTE] = $data;
            }
        }
    }
}