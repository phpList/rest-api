# Other APIs

## MailChimp

https://developer.mailchimp.com/documentation/mailchimp/reference/overview/

Summary: Very clean API. API keys for authentication. Standard REST resources plus some batch
operations and additional actions set via URL segments.


### Access and authentication

They use API keys. Once account can have multiple API keys, and those can also
be listed (for copy'n'paste) an revoked.

Authentication can use basic auth.

Platform integrations should use OAUh2.
https://developer.mailchimp.com/documentation/mailchimp/guides/how-to-use-oauth2/


### General API format

They use JSON for responses, and also for the request body (for PATCH, PUT, and POST).

* Path parameters:
https://usX.api.mailchimp.com/3.0/lists/{list_id}/members/{email_id}/notes/{id}

* Query string parameters:
https://usX.api.mailchimp.com/3.0/campaigns?option1=foo&option2=bar

* Pagination
https://usX.api.mailchimp.com/3.0/campaigns?offset=0&count=10

* Partial responses (only some fields):
https://usX.api.mailchimp.com/3.0/lists?fields=lists.name,lists.id

(For this, we need to tweak the serializer, or write our own serializer.)


### Available resources

https://developer.mailchimp.com/documentation/mailchimp/reference/overview/

They use standard REST methods for these resources:

/campaigns
/lists

In addition, there are some specific actions:
/campaigns/{campaign_id}/actions/replicate
/campaigns/{campaign_id}/actions/resume
/campaigns/{campaign_id}/actions/send
/campaigns/{campaign_id}/actions/test
/lists/{list_id} Batch sub/unsub list members

etc.


### Adding a subscriber

MailChimp does not provide a way to add a subscribe without also adding them to a subscription list.



## Constant Contact

http://developer.constantcontact.com/docs/developer-guides/api-documentation-index.html

Summary: Not-so-clean API. API keys for authentication.

### Authentication

With an API key as a query parameter.

### Adding a subscriber

CC calls subscribers "contacts", and allows adding them separately from their subscriptions.


## HubSpot

https://developers.hubspot.com/docs/overview

Summary: Clean API.