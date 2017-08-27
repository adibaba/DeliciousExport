<?php
namespace Adibaba\DeliciousExport;

use Exception;

/**
 * Generator for HTML file based on parsed del.icio.us social bookmarks
 *
 * @author Adrian Wilke
 */
class Generator
{

    const MARKER_TAGS = '_TAGS_';

    const MARKER_BOOKMARKS = '_BOOKMARKS_';

    const MARKER_TAG = '_TAG_';

    const MARKER_TAG_SIZE = '_TAG_SIZE_';

    const MARKER_BOOKMARK_DATE = '_BOOKMARK_DATE_';

    const MARKER_BOOKMARK_NOTE = '_BOOKMARK_NOTE_';

    const MARKER_BOOKMARK_TAG = '_BOOKMARK_TAG_';

    const MARKER_BOOKMARK_TAGS = '_BOOKMARK_TAGS_';

    const MARKER_BOOKMARK_TITLE = '_BOOKMARK_TITLE_';

    const MARKER_BOOKMARK_URL = '_BOOKMARK_URL_';

    const MARKER_BOOKMARK_HREF = '_BOOKMARK_HREF_';

    protected $htmlParser;

    protected $templateMain;

    protected $templateTag;

    protected $templateCollection;

    protected $templateBookmark;

    protected $templateBookmarkNote;

    protected $templateBookmarkTag;

    protected $defaultNoTags;

    protected $includeNotes;

    protected $includePrivateBookmarks;

    protected $encodeHtml;

    protected $dateFormat;

    /**
     * Creates new generator for del.icio.us export file
     */
    function __construct()
    {
        $this->setTemplateMain(dirname(__FILE__) . '/templateMain.htm');
        $this->setTemplateTag('<li><a href="#' . self::MARKER_TAG . '">' . self::MARKER_TAG . '<span>' . self::MARKER_TAG_SIZE . '</span></a></li>', false);
        $this->setTemplateCollection('<h1 id="' . self::MARKER_TAG . '">' . self::MARKER_TAG . '</h1><ul>' . self::MARKER_BOOKMARKS . '</ul>', false);
        $this->setTemplateBookmark(dirname(__FILE__) . '/templateBookmark.htm');
        $this->setTemplateBookmarkNote('<pre>' . self::MARKER_BOOKMARK_NOTE . '</pre>', false);
        $this->setTemplateBookmarkTag('<span><a href="#' . self::MARKER_BOOKMARK_TAG . '">' . self::MARKER_BOOKMARK_TAG . '</a></span>', false);
        $this->setDefaultNoTags('No-Tags');
        $this->setIncludeNotes(true);
        $this->setIncludePrivateBookmarks(true);
        $this->setEncodeHtml(true);
        $this->setDateFormat('Y-m-d');
    }

    /**
     * Specifies HTML parser
     */
    public function setHtmlParser($htmlParser)
    {
        $this->htmlParser = $htmlParser;
        return $this;
    }

    /**
     * Template with markers:
     * _TAGS_
     * _BOOKMARKS_
     */
    public function setTemplateMain($template, $isFile = true)
    {
        $this->templateMain = $this->getTemplate($template, $isFile);
        return $this;
    }

    /**
     * Template with markers:
     * _TAG_
     */
    public function setTemplateTag($template, $isFile = true)
    {
        $this->templateTag = $this->getTemplate($template, $isFile);
        return $this;
    }

    /**
     * Template with markers:
     * _TAG_
     * _BOOKMARKS_
     */
    public function setTemplateCollection($template, $isFile = true)
    {
        $this->templateCollection = $this->getTemplate($template, $isFile);
        return $this;
    }

    /**
     * Template with markers:
     * _BOOKMARK_URL_
     * _BOOKMARK_HREF_
     * _BOOKMARK_TITLE_
     * _BOOKMARK_TAGS_
     * _BOOKMARK_DATE_
     * _BOOKMARK_NOTE_
     */
    public function setTemplateBookmark($template, $isFile = true)
    {
        $this->templateBookmark = $this->getTemplate($template, $isFile);
        return $this;
    }

    /**
     * Template with markers:
     * _BOOKMARK_NOTE_
     */
    public function setTemplateBookmarkNote($template, $isFile = true)
    {
        $this->templateBookmarkNote = $this->getTemplate($template, $isFile);
        return $this;
    }

    /**
     * Template with markers:
     * _BOOKMARK_TAG_
     */
    public function setTemplateBookmarkTag($template, $isFile = true)
    {
        $this->templateBookmarkTag = $this->getTemplate($template, $isFile);
        return $this;
    }

    /**
     * Sets tag used for bookmarks without tags
     */
    public function setDefaultNoTags($defaultNoTags)
    {
        $this->defaultNoTags = $defaultNoTags;
        return $this;
    }

    /**
     * Specifies, if bookmark notes/descriptions are added
     */
    public function setIncludeNotes($includeNotes)
    {
        $this->includeNotes = $includeNotes;
        return $this;
    }

    /**
     * Specifies, if only public bookmarks should be included
     */
    public function setIncludePrivateBookmarks($includePrivateBookmarks)
    {
        $this->includePrivateBookmarks = $includePrivateBookmarks;
        return $this;
    }

    /**
     * Specifies, if HTML entities should be encoded
     *
     * @see http://php.net/manual/en/function.htmlspecialchars.php
     */
    public function setEncodeHtml($encodeHtml)
    {
        $this->encodeHtml = $encodeHtml;
        return $this;
    }

    /**
     * Sets representation for date/time
     *
     * @see http://php.net/manual/en/function.date.php
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    /**
     * Generates specified representation of bookmarks
     *
     * @return string
     */
    public function generate()
    {
        // Collections (bookmarks related to tags)
        $collectionsHtml = '';
        foreach ($this->htmlParser->getTags() as $tag => $bookmarkIds) {
            
            // Sort bookmarks by title
            $bookmarks = array();
            foreach ($bookmarkIds as $bookmarkId) {
                $bookmarks[] = $this->htmlParser->getBookmarks()[$bookmarkId];
            }
            
            $collectionsHtml .= $this->generateCollectionsHtml($tag, $bookmarks);
        }
        
        // Collection: Bookmarks without tags
        $bookmarks = array();
        foreach ($this->htmlParser->getBookmarkIdsWithoutTags() as $bookmarkId) {
            $bookmarks[] = $this->htmlParser->getBookmarks()[$bookmarkId];
        }
        $collectionsHtml .= $this->generateCollectionsHtml($this->defaultNoTags, $bookmarks);
        
        // Tags
        $tagsHtml = '';
        foreach ($this->htmlParser->getTags() as $tag => $bookmarkIds) {
            $size = $this->getSize($bookmarkIds);
            if (0 === $size) {
                continue;
            }
            $tagHtml = str_replace(self::MARKER_TAG_SIZE, $size, $this->templateTag);
            $tagsHtml .= str_replace(self::MARKER_TAG, $this->encodeHtml($tag), $tagHtml);
        }
        
        // Bookmarks without tags
        $bookmarkIds = $this->htmlParser->getBookmarkIdsWithoutTags();
        $size = $this->getSize($bookmarkIds);
        if (0 !== $size) {
            $tagHtml = str_replace(self::MARKER_TAG_SIZE, $size, $this->templateTag);
            $tagsHtml .= str_replace(self::MARKER_TAG, $this->encodeHtml($this->defaultNoTags), $tagHtml);
        }
        
        // Site
        $bookmarkHtml = str_replace(self::MARKER_BOOKMARKS, $collectionsHtml, $this->templateMain);
        $bookmarkHtml = str_replace(self::MARKER_TAGS, $tagsHtml, $bookmarkHtml);
        return $bookmarkHtml;
    }

    protected function generateCollectionsHtml($tag, $bookmarks)
    {
        // Sort bookmarks by title
        usort($bookmarks, array(
            $this,
            'compareTitle'
        ));
        
        $collectionHtml = str_replace(self::MARKER_BOOKMARKS, $this->generateBookmarksHtml($bookmarks), $this->templateCollection);
        $collectionHtml = str_replace(self::MARKER_TAG, $this->encodeHtml($tag), $collectionHtml);
        
        return $collectionHtml;
    }

    protected function generateBookmarksHtml($bookmarks)
    {
        // Bookmarks
        $bookmarksHtml = '';
        foreach ($bookmarks as $bookmark) {
            
            // Private bookmark: Skip, if setting is do not include
            if ($bookmark[HtmlParser::PRIVATE] && ! $this->includePrivateBookmarks) {
                continue;
            }
            
            // Bookmark
            $bookmarkHtml = $this->templateBookmark;
            $bookmarkHtml = str_replace(self::MARKER_BOOKMARK_TITLE, $this->getValue($bookmark, HtmlParser::TITLE), $bookmarkHtml);
            $bookmarkHtml = str_replace(self::MARKER_BOOKMARK_URL, $this->getValue($bookmark, HtmlParser::URL), $bookmarkHtml);
            $bookmarkHtml = str_replace(self::MARKER_BOOKMARK_HREF, $bookmark[HtmlParser::URL], $bookmarkHtml);
            
            // Note
            $noteHtml = '';
            if ($this->includeNotes && array_key_exists(HtmlParser::NOTE, $bookmark)) {
                $noteHtml = $this->templateBookmarkNote;
                $noteHtml = str_replace(self::MARKER_BOOKMARK_NOTE, $this->getValue($bookmark, HtmlParser::NOTE), $noteHtml);
            }
            $bookmarkHtml = str_replace(self::MARKER_BOOKMARK_NOTE, $noteHtml, $bookmarkHtml);
            
            // Date
            $date = date($this->dateFormat, $bookmark[HtmlParser::DATE]);
            $bookmarkHtml = str_replace(self::MARKER_BOOKMARK_DATE, $date, $bookmarkHtml);
            
            // Tags
            $tagsHtml = '';
            if (empty($bookmark[HtmlParser::TAGS])) {
                $tagsHtml .= str_replace(self::MARKER_BOOKMARK_TAG, $this->encodeHtml($this->defaultNoTags), $this->templateBookmarkTag);
            } else {
                foreach ($bookmark[HtmlParser::TAGS] as $tag) {
                    $tagsHtml .= str_replace(self::MARKER_BOOKMARK_TAG, $this->encodeHtml($tag), $this->templateBookmarkTag);
                }
            }
            $bookmarkHtml = str_replace(self::MARKER_BOOKMARK_TAGS, $tagsHtml, $bookmarkHtml);
            
            $bookmarksHtml .= $bookmarkHtml;
        }
        return $bookmarksHtml;
    }

    protected function getSize($bookmarkIds)
    {
        if ($this->includePrivateBookmarks) {
            return count($bookmarkIds);
        } else {
            $size = 0;
            foreach ($bookmarkIds as $bookmarkId) {
                $bookmark = $this->htmlParser->getBookmarks()[$bookmarkId];
                
                if (! $bookmark[HtmlParser::PRIVATE]) {
                    $size ++;
                }
            }
            return $size;
        }
    }

    /**
     * Compares bookmarks based on title
     *
     * @param array $bookmarkA
     * @param array $bookmarkB
     * @return number
     */
    protected function compareTitle($bookmarkA, $bookmarkB)
    {
        $a = $this->getValue($bookmarkA, HtmlParser::TITLE);
        $b = $this->getValue($bookmarkB, HtmlParser::TITLE);
        return strcasecmp($a, $b);
    }

    protected function encodeHtml($value)
    {
        if ($this->encodeHtml) {
            return htmlspecialchars($value);
        } else {
            return $value;
        }
    }

    protected function getValue($array, $key, $isUrl = false)
    {
        return $this->encodeHtml($array[$key], $isUrl);
    }

    protected function getTemplate($template, $isFile)
    {
        if (! $isFile) {
            return $template;
        } elseif (! is_readable($template)) {
            throw new Exception('Can not read ' . $template);
        } else {
            return file_get_contents($template);
        }
    }
}
?>