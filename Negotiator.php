<?php

namespace AC\WebServicesBundle;

use Negotiation\Negotiator as BasicNegotiator;
use Negotiation\FormatNegotiator;
use Negotiation\LanguageNegotiator;
use Symfony\Component\HttpFoundation\Request;

/**
 * A convenience class for using the willdurand/negotiation Negotiator.  It also has the added functionality of using some configuration to
 * map incoming content-type headers to desired formats.  This is mainly used for determining which format to use when deserializing incoming
 * api data.
 *
 */
class Negotiator
{
    private $inputFormatMap = array();
    private $langNegotiator;
    private $formatNegotiator;
    private $langPriorities;
    private $formatPriorities;
    private $charsetPriorities;
    private $basicNegotiator;

    /**
     * Receives configuration to use for negogiation tasks.
     *
     * @param array $inputFormatMap     A map of 'content/type'=>'format'
     * @param array $formatPriorities   Array of priorities for preferred formats
     * @param array $langPriorities     Array of priorities for preferred languages
     * @param array $charsetPriorities  Array of priorities for preferred charsets
     * @param array $encodingPriorities Array of priorities for preferred encodings
     */
    public function __construct(
        $inputFormatMap = array(),
        $formatPriorities = array(),
        $langPriorities = array(),
        $charsetPriorities = array(),
        $encodingPriorities = array()
    )
    {
        $this->inputFormatMap = $inputFormatMap;
        $this->langPriorities = $langPriorities;
        $this->formatPriorities = $formatPriorities;
        $this->charsetPriorities = $charsetPriorities;
        $this->encodingPriorities = $encodingPriorities;
    }

    /**
     * Convenient static creation method for chainable creation/use.
     *
     * @param  array      $map
     * @param  array      $formats
     * @param  array      $langs
     * @param  array      $charsets
     * @return Negotiator
     */
    public static function create($map = array(), $formats = array(), $langs = array(), $charsets = array())
    {
        return new Negotiator($map, $formats, $langs, $charsets);
    }

    /**
     * Return the incoming format, derived from the Requests content-type header.  Generally this is used for determing which
     * format to use during deserialization.
     *
     * @param  Request $req
     * @return string
     */
    public function negotiateRequestFormat(Request $req)
    {
        $header = $req->headers->get('Content-Type');

        $exp = explode(';', $header);
        $type = $exp[0];

        return isset($this->inputFormatMap[$type]) ? $this->inputFormatMap[$type] : false;
    }

    /**
     * Return the response format type, derived from a Request's Accept header, and configured priorities.
     *
     * @param  Request $req
     * @return string
     */
    public function negotiateResponseFormat(Request $req)
    {
        return $this->getFormatNegotiator()->getBestFormat($req->headers->get('Accept'), $this->formatPriorities);
    }

    /**
     * Return preferred language, derived from Requests Accept-Language header, and configured priorities.
     *
     * @param  Request $req
     * @return string
     */
    public function negotiateResponseLanguage(Request $req)
    {
        return $this->getLanguageNegotiator()->getBest($req->headers->get('Accept-Language'), $this->langPriorities)->getValue();
    }

    /**
     * Derive response charset/encoding from incoming requests Accept-Charset header and configured priorities.
     *
     * @param  Request $req
     * @return string
     */
    public function negotiateResponseCharset(Request $req)
    {
        return $this->getBasicNegotiator()->getBest($req->headers->get('Accept-Charset'), $this->charsetPriorities)->getValue();
    }

    public function negotiateResponseEncoding(Request $req)
    {
        return $this->getBasicNegotiator()->getBest($req->headers->get('Accept-Encoding', $this->encodingPriorities))->getValue();
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

    public function setEncodingPriorities(array $arr = null)
    {
        $this->encodingPriorities = $arr;

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

            //TODO: register additional formats
            $this->formatNegotiator->registerFormat('yml', array('application/yaml', 'text/yaml', 'application/x-yaml', 'text/x-yaml'));
        }

        return $this->formatNegotiator;
    }

    public function getBasicNegotiator()
    {
        if (!$this->basicNegotiator) {
            $this->basicNegotiator = new BasicNegotiator();
        }

        return $this->basicNegotiator;

    }
}
