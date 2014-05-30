<?php

namespace AC\WebServicesBundle\Debug;

/**
 * Based on http://www.php.net/manual/en/exception.gettraceasstring.php#114980
 */
class ImprovedStackTrace
{
    /**
     * Returns a string describing in detail the stack context of an exception.
     */
    static function getTrace($e, $seen = null)
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result = array();
        if (!$seen) $seen = array();
        $trace  = $e->getTrace();
        $prev   = $e->getPrevious();
        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());
        $file = $e->getFile();
        $line = $e->getLine();

        while (true) {
            $current = "$file:$line";
            $result[] = sprintf(' at %s%s%s(%s%s%s)',
                    count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                    count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                    count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                    $line === null ? $file : basename($file),
                    $line === null ? '' : ':',
                    $line === null ? '' : $line);
            if (is_array($seen))
                $seen[] = "$file:$line";
            if (!count($trace))
                break;
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }

        $result = join("\n", $result);
        if ($prev)
            $result  .= "\n" . self::getTrace($prev, $seen);
        return $result;
    }
}
