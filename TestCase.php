<?php

namespace AC\WebServicesBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;

use \Mockery as m;

/**
 * This is a base integration test case that you can use for your own tests.
 * It contains a few convenience methods for making API calls.
 **/
abstract class TestCase extends WebTestCase
{
    private static $fixtures = [];
    protected $fixtureData = [];

    /**
     * Override from child class to return a CachedFixture
     */
    protected function getFixtureClass()
    {
        # TODO Maybe should use ac_web_services.default_fixture_class instead?
        return null;
    }

    public function setUp()
    {
        parent::setUp();

        $this->dieOnException("fixture loading", function() {
            $c = $this->getFixtureClass();
            if (is_null($c)) { return; }
            if (!array_key_exists($c, self::$fixtures)) {
                self::$fixtures[$c] = new $c;
            }
            if (!is_null(self::$fixtures[$c])) {
                $client = $this->getClient();
                $g = self::$fixtures[$c]->loadInto($client->getContainer());
                $this->fixtureData = $g;
            } else {
                $this->fixtureData = [];
            }
        });
    }

    /**
     * Shortcut to get a new client
     */
    protected function getClient()
    {
        return static::createClient([
            'environment' => 'test',
            'debug' => true
        ]);
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
    protected function callApi($method, $uri, $options = [])
    {
        $params = isset($options['params']) ? $options['params'] : [];
        $files = isset($options['files']) ? $options['files'] : [];
        $server = isset($options['server']) ? $options['server'] : [];
        $content = isset($options['content']) ? $options['content'] : null;
        $changeHist = isset($options['changeHistory']) ? $options['changeHistory'] : true;

        $server['SERVER_NAME'] = '127.0.0.1';

        $client = $this->getClient();

        if (isset($options['containerConfig'])) {
            call_user_func($options['containerConfig'], $client->getContainer());
        }

        if (isset($options['auth'])) {
            $user = $options['auth']['user'];
            $this->fakeUserAuth($client, $user);
        }

        $client->request($method, $uri, $params, $files, $server, $content, $changeHist);
        $response = $client->getResponse();

        if (!array_key_exists('expectedCode', $options)) {
            $options['expectedCode'] = 200;
        }
        if (!is_null($options['expectedCode'])) {
            if ($response->getStatusCode() != $options['expectedCode']) {
                $msg = "Expected status code " . $options['expectedCode'] .
                    ", got " . $response->getStatusCode() . ".\n";
                if ($response->headers->get('Content-Type') == "application/json") {
                    $content = json_decode($response->getContent(), true);
                    if (is_null($content)) {
                        $msg .= "Response content (unparseable JSON):\n$result";
                    } else {
                        $msg .= "JSON content of invalid response:\n";
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
                } else {
                    $msg .= "Response content (first 16K):\n" . substr($result, 0, 1024*16);
                }
                $this->fail(trim($msg));
            }
        }

        return $response;
    }

    /**
     * Frontend to callApi that decodes JSON response content.
     *
     * Returns the JSON data.
     */
    protected function callJsonApi($method, $uri, $options = [])
    {
        if (isset($options['content'])) {
            if (
                !isset($options['server']) ||
                !isset($options['server']['CONTENT_TYPE'])
            ) {
                $options['server']['CONTENT_TYPE'] = 'application/json';
            }

            if (
                is_array($options['content']) &&
                $options['server']['CONTENT_TYPE'] == 'application/json'
            ) {
                $options['content'] = json_encode($options['content']);
            }
        }

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

    protected function dieOnException($desc, $fn)
    {
        try {
            call_user_func($fn);
        } catch (\Exception $e) {
            print "\n\nFailure in $desc\n";
            print $e;
            print $e->getTraceAsString();
            die(1);
        }
    }

    protected function assertIdsMatch($ids, $data)
    {
        $dataIds = array_map(function($x) { return $x['id']; }, $data);
        sort($dataIds);
        $this->assertEquals($ids, $dataIds);
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

    private function fakeUserAuth($client, $user)
    {
        $c = $client->getContainer();

        // The user object may have been made in the context of a different instance of the
        // object manager than the one being used by this client.
        if ($fixtureCls = $this->getFixtureClass()) {
            $fixture = new $fixtureCls;
            $manager = null;
            if (is_a($fixture, '\AC\WebServicesBundle\Fixture\CachedSqliteFixture')) {
                $manager = $c->get('doctrine')->getManager();
                $user = $manager->merge($user);
            } else if (is_a($fixture, '\AC\WebServicesBundle\Fixture\CachedMongoFixture')) {
                $manager = $c->get('doctrine_mongodb')->getManager();
                $user = $manager->merge($user);
            }
        }

        $token = new PreAuthenticatedToken($user, [], 'mock', $user->getRoles());
        $c->set('security.context', m::mock(
            $c->get('security.context'),
            [
                'getToken' => $token
            ]
        ));
    }
}
