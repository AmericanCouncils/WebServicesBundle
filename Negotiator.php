<?php

namespace AC\WebServicesBundle;

use Negotiation\Negotiator;
use Negotiation\FormatNegotiator;
use Negotiation\LanguageNegotiator;
use Symfony\Component\HttpFoundation\Request;

class Negotiator
{
    private $inputFormatMap = array();
    private $langNegotiator;
    private $formatNegotiator;
    private $langPriorities;
    private $formatPriorities;
    private $charsetPriorities;
    private $basicNegotiator;

    public function __construct($inputFormatMap = array(), $formatPriorities = array(), $langPriorities = array(), $charsetPriorities = array())
    {
        $this->inputFormatMap = $inputFormatMap;
        $this->langPriorities = $langPriorities;
        $this->formatPriorities = $formatPriorities;
        $this->charsetPriorities = $charsetPriorities;
    }

    public static function create($map = array(), $formats = array(), $langs = array(), $charsets = array())
    {
        return new Negotiator($map, $formats, $langs, $charsets);
    }

    public function negotiateRequestFormat(Request $req)
    {
        $type = $req->headers->get('Content-Type');

        return isset($this->inputFormatMap[$type]) ? $this->inputFormatMap[$type] : false;
    }

    public function negotiateResponseFormat(Request $req)
    {
        return $this->getFormatNegotiator()->getBestFormat($req->headers->get('Accept'), $this->formatPriorities);
    }

    public function negotiateResponseLanguage(Request $req)
    {
        return $this->getLanguageNegotiator()->getBest($req->headers->get('Accept-Language'), $this->langPriorities)->getValue();
    }

    public function negotiateResponseCharset(Request $req)
    {
        throw new \RuntimeException('Not implemented.');
    }

    public function negotiateResponseEncoding(Request $req)
    {

    }

    public function setInputFormatForType($format, $type)
    {
        $this->inputFormatMap[$format] = $type;

        return $this;
    }

    public function setLanguagePriorities(array $arr = null)
    {
        $this->langPriorities = $arr;

        return $this;
    }

    public function setFormatPriorities(array $arr = null)
    {
        $this->formatPriorities = $arr;

        return $this;
    }

    public function setCharsetPriorities(array $arr = null)
    {
        $this->charsetPriorities = $arr;

        return $this;
    }

    public function getLanguageNegotiator()
    {
        if (!$this->langNegotiator) {
            $this->langNegotiator = new LanguageNegotiator();
        }

        return $this->langNegotiator;
    }

    public function getFormatNegotiator()
    {
        if (!$this->formatNegotiator) {
            $this->formatNegotiator = new FormatNegotiator();
        }

        return $this->formatNegotiator;
    }

    public function getBasicNegotiator()
    {
        if (!$this->basicNegotiator) {
            $this->basicNegotiator = new Negotiator();
        }

        return $this->basicNegotiator;

    }
}
