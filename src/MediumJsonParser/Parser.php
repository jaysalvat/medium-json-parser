<?php
/*
Medium Json Parser
Copyright (c) 2016, Jay Salvat
http://jaysalvat.com
*/

namespace MediumJsonParser;

class Parser
{
    protected $html = '';
    protected $id;
    protected $title;
    protected $subtitle;
    protected $language;
    protected $last_version;
    protected $last_published_version;
    protected $url;
    protected $author;
    protected $created_at;
    protected $updated_at;

    protected $previousType;
    protected $content;

    public $iframeProxyPath;
    public $imageQuality = 40;
    public $imageWidth = 1280;

    public $special_chars = [
        "’" => "'",
        "“" => '"',
        "”" => '"',
    ];

    public function __construct($url)
    {
        $url = $this->prepareUrl($url);

        $data = file_get_contents($url);
        $data = str_replace('])}while(1);</x>', '', $data);

        $json = json_decode($data);
        $json = $json->payload;
        $values = $json->value;
        $references = $json->references;

        $this->id = $values->id;
        $this->title = $values->title;
        $this->subtitle = $values->content->subtitle;
        $this->language = $values->detectedLanguage;
        $this->url = $values->mediumUrl;
        $this->last_version = $values->latestVersion;
        $this->last_published_version = $values->latestPublishedVersion;
        $this->created_at = $values->createdAt;
        $this->updated_at = $values->updatedAt;

        $author_id = $values->creatorId;
        $this->author = $references->User->{$author_id}->name;

        $this->content = $values->content->bodyModel->paragraphs;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getVersion()
    {
        return $this->last_published_version;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getSubTitle()
    {
        return $this->subtitle;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function getUrl() 
    {
        return $this->url;
    }

    public function getAuthor() 
    {
        return $this->author;
    }

    public function getLastVersion() 
    {
        return $this->last_version;
    }

    public function getLastPublishedVersion() 
    {
        return $this->last_published_version;
    }

    public function html($options = []) 
    {
        $options = array_merge([
            'skip_header'  => false,
            'return_array' => false
        ], $options);

        $html = '';
        $array = [];

        foreach ($this->content as $element) {
            $part = '';

            if ($this->previousType !== 9 && $element->type === 9) {
                $part = '<ul>';
            }

            if ($this->previousType !== 10 && $element->type === 10) {
                $part = '<ol>';
            }

            if ($this->previousType === 9 && $element->type !== 9) {
                $part = '</ul>';
            }

            if ($this->previousType === 10 && $element->type !== 10) {
                $part = '</ol>';
            }

            if ($part) {
                $array[] = $part;
            }

            switch ($element->type) {
                // paragraph
                case 1: 
                    $part = $this->parseParagraph($element);
                break;

                // Title
                case 3: 
                    $part = $this->parseTitle($element, $options['skip_header']);
                break;

                // Image
                case 4: 
                    $part = $this->parseImage($element, $options['skip_header']);
                break;

                // Blockquote
                case 6: 
                    $part = $this->parseQuote($element);
                break;

                // Code
                case 8: 
                    $part = $this->parseCode($element);
                break;

                // List
                case 9: 
                case 10: 
                    $part = $this->parseList($element);
                break;

                // IFrame
                case 11: 
                    $part = $this->parseMediaFrame($element);
                break;

                // SubTitle
                case 13: 
                    $part = $this->parseSubTitle($element);
                break;
            }

            $this->previousType = $element->type;

            $array[] = $part;
        }

        if ($options['return_array']) {
            return $array;
        }
        
        return implode('', $array);
    }

    private function parseMediaFrame($json)
    {
        $class = $this->getLayoutClass($json);
        $post_id = $this->id;
        $resource_id = $json->iframe->mediaResourceId;
        $caption = $json->text;
        $width = $json->iframe->iframeWidth;
        $height = $json->iframe->iframeHeight;
        $ratio = $height / $width * 100;

        if ($this->iframeProxyPath) {
            $iframe_src = $this->iframeProxyPath . '?resource_id=' . $resource_id . '&post_id=' . $post_id . '';
        } else {
            $iframe_src = 'http://medium.com/media/' . $resource_id . '?postId=' . $post_id . '"';
        }

        $iframe  = '<div ' . $class .'>';
        $iframe .= '<figure>';
        $iframe .= '<div class="medium-ratio" style="max-width:' . $width . 'px;max-height:' . $height . 'px;">';
        $iframe .= '<div class="medium-ratio-inner" style="padding-bottom:' . $ratio . '%;"></div>';
        $iframe .= '<iframe src="' . $iframe_src . '" width="' . $width . '" height="' . $height . '" allowfullscreen frameborder="0"></iframe>';
        $iframe .= '</div>';
        $iframe .= ($caption) ? '<figcaption>' . $caption . '</figcaption>' : '';
        $iframe .= '</figure>';
        $iframe .= '</div>';

        return $iframe;
    }

    private function parseImage($json)
    {
        $class = $this->getLayoutClass($json);

        $id = $json->metadata->id;
        $caption = $json->text;
        $width = $json->metadata->originalWidth;
        $height = $json->metadata->originalHeight;
        $ratio = $height / $width * 100;

        $image  = '<div ' . $class .'>';
        $image .= '<figure>';
        $image .= '<div class="medium-ratio" style="max-width:' . $width . 'px;max-height:' . $height . 'px;">';
        $image .= '<div class="medium-ratio-inner" style="padding-bottom:' . $ratio . '%;"></div>';
        $image .= '<img src="https://cdn-images-1.medium.com/max/' . $this->imageWidth . '/' . $id . '?q=' . $this->imageQuality . '"/>';
        $image .= '</div>';
        $image .= ($caption) ? '<figcaption>' . $caption . '</figcaption>' : '';
        $image .= '</figure>';
        $image .= '</div>';

        return $image;
    }

    private function parseTitle($json, $skip_header = false)
    {
        if ($json->text === $this->title) {
            if ($skip_header) {
                return '';
            }
            return '<h1>' . $this->prepareText($json) . '</h1>';
        }
        return '<h3>' . $this->prepareText($json) . '</h3>'; 
    }

    private function parseSubTitle($json, $skip_header = false)
    {
        if ($json->text === $this->subtitle) {
            if ($skip_header) {
                return '';
            }
            return '<h2>' . $this->prepareText($json) . '</h2>';
        }
        return '<h4>' . $this->prepareText($json) . '</h4>';
    }

    private function parseQuote($json)
    {
        return '<blockquote>' . $this->prepareText($json) . '</blockquote>';
    }

    private function parseCode($json)
    {
        return '<pre>' . htmlentities($this->prepareText($json)). '</pre>';
    }

    private function parseParagraph($json)
    {
        $class = isset($json->hasDropCap) ? 'class="medium-dropcap"' : '';

        return '<p ' . $class . '>' . $this->prepareText($json) . '</p>';
    }

    private function parseList($json)
    {
        return '<li>' . $this->prepareText($json) . '</li>';
    }

    private function prepareUrl($url)
    {
        if (strpos('format=json', $url) === false) {
            $url = preg_replace('/#(.*)$/', '', $url);

            if (strpos('?', $url) === false) {
                $url .= '?';
            }

            $url .= '&format=json';
        }

        return $url;
    }

    private function getLayoutClass($json)
    {
        $class = '';

        if (isset($json->layout)) {
            switch ($json->layout) {
                case 1:
                    $class = 'class="medium-layout-centered"';
                break;

                case 3:
                    $class = 'class="medium-layout-larger"';
                break;

                case 4:
                    $class = 'class="medium-layout-left"';
                break;

                case 5:
                    $class = 'class="medium-layout-fullwidth"';
                break;
            }
        }

        return $class;
    }

    private function prepareText($json)
    {
        $text = strtr($json->text, $this->special_chars);
        $push = 0;
        $markups = $json->markups;

        $markups = $this->arrayOrderby($markups, 'start', SORT_ASC, 'end', SORT_ASC);

        foreach ($markups as $markup) {
            $open = '';
            $close = '';

            $markup = (array) $markup;

            switch ($markup['type']) {
                // Bold
                case 1:
                    $open = '<strong>';
                    $close = '</strong>';
                break;

                // Italic
                case 2:
                    $open = '<em>';
                    $close = '</em>';
                break;

                // Link
                case 3:
                    $open = '<a href="' . $markup['href'] . '" title="' . addslashes($markup['title']) . '" rel="' . $markup['rel'] . '">';
                    $close = '</a>';
                break;
            }

            if ($open || $close) {
                $start = $markup['start'] + $push;
                $end = $markup['end'] + $push + strlen($open);

                $text = substr($text, 0, $start) . $open . substr($text, $start);
                $text = substr($text, 0, $end) . $close . substr($text, $end);

                $push += strlen($open) + strlen($close);;
            }
        }

        return $text;
    }

    private function arrayOrderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        $data = (array) $data;

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();

                foreach ($data as $key => $row) {
                    $row = (array) $row;
                    $tmp[$key] = $row[$field];
                }

                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;

        call_user_func_array('array_multisort', $args);

        return array_pop($args);
    }
}