<?php

namespace AC\WebServicesBundle\Serializer;

class DateTimeHandler
{
    protected $defaultTimezone;
    protected $defaultFormat;

    public function __construct($defaultFormat = \DateTime::ISO8601, $defaultTimezone = "America/New_York")
    {
        $this->defaultFormat = $defaultFormat;
        $this->defaultTimezone = $defaultTimezone;
    }

    public function deserializeDateTime($visitor, $data, array $type)
    {
        if (null === $data) {
            return null;
        }

        $timezone = isset($type['params'][1]) ? new \DateTimeZone($type['params'][1]) : new \DateTimeZone($this->defaultTimezone);
        $format =  isset($type['params'][0]) ? $type['params'][0] : $this->defaultFormat;

        $datetime = \DateTime::createFromFormat($format, (string) $data, $timezone);

        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected format %s.', $data, $format));
        }

        return $datetime;
    }
}
