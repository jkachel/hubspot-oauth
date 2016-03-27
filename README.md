# hubspot-oauth
PHP API wrapper for the HubSpot API, that uses OAuth

*Note:* Nothing works!

This is a pretty simple wrapper for the HubSpot API as it stands currently (late March 2016). The main reason this exists outside of the other available API wrappers is that the other wrappers work with API keys, where this works with OAuth instead (which is what it seems they'd prefer you do).

The API itself works much like Laravel Eloquent models. So, if you want to query the Contacts API, you can use `Contacts::find('email@address')` to search using the contacts-by-email API call.

 There is also some magic stuff in to handle a few things in Laravel apps; namely, a controller that will handle the bits and pieces of token verification and all that. This package can be used sans Laravel, however.