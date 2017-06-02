# API notes

## Format

### Request

application/json
JSON-encoded data (if necessary)

URL: with or without trailing slash

### Response

Content-type: application/json
Encoding: utf-8

Later: Last-Modified and ETag

#### JSON with camelCase keys

pretty-printed


```
{
  "message":"Success",
  "token":"token_here"
}
```

Errors:
```
{
  "code" : 1234,
  "message" : "Something bad happened :(",
  "description" : "More details about the error here"
}
```


## Resources

### Meta pages

```
(later) GET api/ -> 200 OK, list of versions
(later) GET api/v1 -> 404 NOT FOUND
(later) GET api/v2 -> 200 OK, API list
```


### Subscribers (Users)
```
(later) GET api/v2/subscribers -> 200 OK, 401 UNAUTHORIZED
GET api/v2/subscribers/1 -> 200 OK, 404 NOT FOUND, 401 UNAUTHORIZED
DELETE api/v2/subscribers/1 -> 204 NO CONTENT, 401 UNAUTHORIZED
```


### Lists
```
(later) GET api/v2/lists/1 -> 200 OK, 404 NOT FOUND, 401 UNAUTHORIZED
```

### Subscriptions
```
(later) GET api/v2/subscriptions -> 200 OK, 401 UNAUTHORIZED
(later) GET api/v2/subscriptions/1 -> 200 OK, 404 NOT FOUND, 401 UNAUTHORIZED
(later) DELETE api/v2/subscriptions/1 -> 204 NO CONTENT, 401 UNAUTHORIZED

(later) GET api/v2/lists/1/subscriptions -> 200 OK, 404 NOT FOUND, 401 UNAUTHORIZED
(later) GET api/v2/lists/1/subscriptions/1 -> 200 OK, 404 NOT FOUND, 401 UNAUTHORIZED
POST api/v2/lists/1/subscriptions -> 201 CREATED (mit URL), 401 UNAUTHORIZED
  Request Content-Type  multipart/form-data
  Response: {id: 123}
```

### All

```
-> ???

```
