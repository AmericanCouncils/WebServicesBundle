<?php

namespace AC\WebServicesBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * This is a base integration test case that you can use for your own tests.
 * It contains a few convenience methods for making API calls.
 **/
abstract class TestCase extends WebTestCase
{
    /**
     * Shortcut to get a new client
     */
    protected function getClient()
    {
        return static::createClient(array(
            'environment' => 'test',
            'debug' => true
        ));
    }

    /**
     * Shortcut to run a CLI command - returns a... ?
     */
    protected function runCommand($string)
    {
        $command = sprintf('%s --quiet --env=test', $string);
        $k = $this->createKernel();
        $app = new Application($k);
        $app->setAutoExit(false);

        return $app->run(new StringInput($string), new NullOutput());
    }

    /**
     * Shortcut to make a request and get the returned Response instance.
     *
     * Will fail unless the response's status code equals 200 (or a supplied expectedCode option).
     */
    protected function callApi($method, $uri, $options = array())
    {
        $params = isset($options['params']) ? $options['params'] : array();
        $files = isset($options['files']) ? $options['files'] : array();
        $server = isset($options['server']) ? $options['server'] : array();
        $content = isset($options['content']) ? $options['content'] : null;
        $changeHist = isset($options['changeHistory']) ? $options['changeHistory'] : true;

        $server['SERVER_NAME'] = '127.0.0.1';

        $client = $this->getClient();
        $client->request($method, $uri, $params, $files, $server, $content, $changeHist);
        $response = $client->getResponse();

        if (!isset($options['expectedCode'])) {
            $options['expectedCode'] = 200;
        }
        if (!is_null($options['expectedCode'])) {
            if ($response->getStatusCode() != $options['expectedCode']) {
                $msg = "Expected status code " . $options['expectedCode'] .
                    ", got " . $response->getStatusCode();
                if ($response->headers->get('Content-Type') == "application/json") {
                    $msg .= ". JSON content of invalid response:\n";
                    $content = json_decode($response->getContent(), true);

                    # Clean up the stack trace if there is one
                    if (isset($content['exception']) && isset($content['exception']['trace'])) {
                        $content['exception']['trace'] =
                            $this->cleanTrace($content['exception']['trace']);
                    }

                    $result = var_export($content, true);
                    if (strlen($result) > 20*1024) {
                        $result = substr($result, 0, 20*1024);
                        $result .= "\n.......\n.......";
                    }
                    $msg .= "$result\n";
                }
                $this->fail($msg);
            }
        }

        return $response;
    }

    /**
     * Frontend to callApi that decodes JSON response content.
     *
     * Returns the JSON data.
     */
    protected function callJsonApi($method, $uri, $options = array())
    {
        $res = $this->callApi($method, $uri, $options);
        $ctype = $res->headers->get('Content-Type');
        if ($ctype != "application/json") {
            $this->fail("Expecting JSON response, but instead got $ctype");
        }
        $json = json_decode($res->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail("Couldn't decode response, JSON error: " . json_last_error_msg());
        }
        return $json;
    }

    private function cleanTrace($trace)
    {
        if (!is_array($trace)) { return $trace; }

        $cwd = getcwd();
        $trace = array_map(function($line) use ($cwd) {
            $line = preg_replace('/^\\d+ +/', '', $line);
            $line = str_replace($cwd . '/', '', $line);
            return $line;
        }, $trace);
        $trace = array_filter($trace, function ($line) {
            return (
                (FALSE === strpos($line, 'vendor/phpunit/phpunit'))
                && ($line != "{main}")
                );
            }
        );
        return $trace;
    }
}
