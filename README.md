# ACWebServicesBundle #

This bundle provides generic api workflow tools for developing RESTful apis.

*NIH:*  A lot of the functionality in this bundle already exists in [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle), use that if you want safety.  This is periodically under heavy development.

## Features ##

* Request lifecycle events dispatched for registered *API events*
* Object de-serializer that leverages JMS Metadata to serialize incoming data into already existing objects
* Optionally include response data as part of the outgoing response
* By default handles xml, json, jsonp and yml responses
* Easily convert validation errors to useful API responses

## Usage ##

Generally speaking, you configure some general behavior for your API by setting values that apply to certain routes.  Here's example config:

```yaml
ac_web_services:

    #defaults for content-type header are provided per response format, but you may include custom headers as well
    response_format_headers:
        json:
            'content-type': 'application/json'
        jsonp:
            'content-type': 'text/javascript'
    paths:
        '{^/api/override}':
            include_exception_data: false           #very helpful for debugging
            include_response_data: false            #easier for some clients to parse
            allow_code_suppression: false           #for clients that don't really respect http and intercept errors
            allow_jsonp: false                      #if you really have to...
            default_response_format: json           
            http_exception_map:                     #you may want/need to convert some exceptions for the end clients
                'AC\WebServicesBundle\Tests\Fixtures\FixtureBundle\BundleException': { code: 403, message: 'Custom error message' }
            additional_headers:                     #just because... maybe it'll be useful?
                'x-custom-acwebservices': 'foo-bar-baz'
        '{^/api/}':
            include_exception_data: true
            include_response_data: true
            allow_code_suppression: true
            allow_jsonp: true
            default_response_format: json
```

When a request matches a configured path, an event subscriber is registered with the relevant configuration.  This subscriber fires extra api events, similar to the kernel events.  From your controllers you can return raw data structures, which may include objects configured for serialization via the `JMSSerializerBundle`.  Information returned from a controller will automatically be serialized into the requested data transfer format by the `serializer` service.

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

### Configuration ###

This is an brief description of all configuration options provided by the bundle, more detailed descriptions are given below.

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

#### ValidationException ####

    TODO: document ValidationException, see https://github.com/AmericanCouncils/WebServicesBundle/issues/3

### Events ###

When handling api requests, the bundle fires a few extra events for all API requests.  These are useful hooks for triggering other 
functionality, such as logging or metrics gathering, that should apply to all API service routes.  The events fired include:

* `webservice.request` - When an API request is initiated, and has successfully been matched to a controller
* `webservice.exception` - If an error is encountered during an API route
* `webservice.response` - The final response from the API
* `webservice.terminate` - After the API response has been sent

You can register a listener service for any of these events with the `ac.webservice.listener` container tag, or register
subscribers to multiple events via the `ac.webservice.subscriber` tag.

### Services ###

    TODO: document extra JMS stuff that allows serializing into objects
