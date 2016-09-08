# ACWebServicesBundle #

[![Build Status](https://travis-ci.org/AmericanCouncils/WebServicesBundle.png?branch=master)](https://travis-ci.org/AmericanCouncils/WebServicesBundle)

This bundle provides generic api workflow tools for developing RESTful apis.

*NIH:*  A lot of the functionality in this bundle already exists in [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle), use that if you want safety.  This is periodically under heavy development.

## Features ##

* Request lifecycle events dispatched for registered *API events*
* Object de-serializer that leverages JMS Metadata to serialize incoming data into already existing objects
* Optionally include response data as part of the outgoing response
* By default handles xml, json, jsonp and yml responses
* Easily convert validation errors to useful API responses
* Easil serialize incoming data into existing objects

## Installation ##

1. require `"ac/web-services-bundle": "~0.2.0"` in your `composer.json`
2. update it w/ composer: `composer update ac/web-services-bundle`
3. enable in your `AppKernel.php`:

    ```php
    use AC\WebServicesBundle\ACWebServicesBundle;

    //...

    public function getBundles()
    {
        //...
        $bundles = array(
            //...
            new ACWebServicesBundle()
            //...
        );
        //....
    }
    ```

4. Configure the bundle in your `app/config/config.yml`:

    This is an example configuration block.  All sections are optional and explained in more detail in subsequent sections.

    ```yml
    ac_web_services:
        serializer:
            allow_deserialize_into_target: true
        response_format_headers:
            yml:
                'Content-Type': 'text/x-yaml; charset=UTF-8'
            csv:
                'Content-Type': 'text/csv; charset=UTF-8'
        paths:
            '{^/api/override}':
                include_exception_data: false
                include_response_data: false
                allow_code_suppression: false
                allow_jsonp: false
                default_response_format: json
            '{^/api/}':
                include_exception_data: true
                include_response_data: true
                allow_code_suppression: true
                allow_jsonp: true
                default_response_format: json
                http_exception_map:
                    'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\BundleException': { code: 403, message: 'Custom error message' }
                additional_headers:
                    'x-custom-acwebservices': 'foo-bar-baz'
    ```

## API Paths Configuration ##

Generally speaking, you configure some general behavior for your API by setting values that apply to certain routes. When a request matches a configured path, an event subscriber is registered with the relevant configuration.  This subscriber fires extra api events, similar to the kernel events.  From your controllers you can return raw data structures, which may include objects configured for serialization via the `JMSSerializerBundle`.  Information returned from a controller will automatically be serialized into the requested data transfer format by the `serializer` service.

For example:

0. Request to `http://example.com/api/foo?_format=json`
1. Routed to controller `MyBundle\Controller\FooController::someAction`
2. Which looks like this:

        <?php

        namespace MyBundle\Controller;
        use Symfony\Bundle\FrameworkBundle\Controller\Controller;
        use AC\WebServicesBundle\ServiceResponse;

        class FooController extends Controller
        {
            public function someAction()
            {
                return new ServiceResponse(array(
                    'foo' => 'bar',
                    'baz' => 23
                ));
            }
        }

3. Will return this result:

        {
            "foo": "bar",
            "baz": 23
        }

> Note that changing the `_format` parameter to `xml` or `yml` will return the data structure in those formats as well.

> Note: If the `_format` parameter is absent, a default format wil be returned, which is usually `json`.

### Response data & code suppression ###

By default, API response will also include a `response` property that includes the HTTP response code and message.  This
information is also included in the actual response, but is made availabe in the response body as a matter of convenience
for API consumers.

Also, in some cases, some clients do not properly respect the actual HTTP spec.  If dealing with such a client, the bundle
allows you to make API requests that always return a `200` response code.  If this happens, the actual HTTP code and message
will still be set properly in the response body.

The example response above, if `include_response_data` is `true`, would look like this:

    {
        "response": {
            "code": 200,
            "message": "OK"
        },
        "foo": "bar",
        "baz": 23
    }

### Exceptions ###

On API routes that throw exceptions, they are caught and serialized with the response data described above.  Note that
if code suppression is turned on, the actual response code will always be `200`, and the real response code must be
retrieved in the returned object.

If an HTTP exception is thrown from the controllers, the messages and codes are preserved.  If another exception is thrown, however,
the bundle will convert it into an `HttpException` with a `500` response code and default *"Internal Server Error"* message.

This behavior is also configurable - you can specify a map of other exception classes, and the http code and message that should
be returned instead.

Exceptions return the response data structure described above, for example:

    {
        "response": {
            "code": 500,
            "message": "Internal Server Error"
        }
    }

### Events ###

When handling api requests, the bundle fires a few extra events for all API requests.  These are useful hooks for triggering other
functionality, such as logging or metrics gathering, that should apply to all API service routes.  The events fired include:

* `ac.webservice.request` - When an API request is initiated, and has successfully been matched to a controller
* `ac.webservice.exception` - If an error is encountered during an API route
* `ac.webservice.response` - The final response from the API
* `ac.webservice.terminate` - After the API response has been sent

You can register a listener service for any of these events with the `ac.webservice.listener` container tag, or register
subscribers to multiple events via the `ac.webservice.subscriber` tag.


### ValidationException ###

    TODO: document ValidationException, see https://github.com/AmericanCouncils/WebServicesBundle/issues/3

## Serialization Extras ##

If `serializer.allow_deserialize_into_target` is configured to `true`, some extra services will be registered that allow serialization into
pre-existing objects.  Here is some example usage from a controller:

```php
use AC\WebServicesBundle\Serializer\DeserializationContext;
use AC\WebServicesBundle\ServiceResponse;

//...

public function userUpdateAction(Request $req)
{
    $user = //... fetch pre-existing user however you do that
    $serializer = $this->container->get('serializer');
    $context = DeserializationContext::create()
        ->setTarget($user)
        ->setSerializeNested(true)
    ;

    //we'll assume the input is json for documentation purposes
    $modifiedUser = $serializer->deserialize($req->getContent(), get_class($user), 'json', $context);

    return ServiceResponse::create($modifiedUser);
}
```
