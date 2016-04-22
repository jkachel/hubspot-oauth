# hubspot-oauth
PHP API wrapper for the HubSpot API, that uses OAuth

*Note:* Sort of works now!

This is a pretty simple wrapper for the HubSpot API as it stands currently (late March 2016). The main reason this exists outside of the other available API wrappers is that the other wrappers work with API keys, where this works with OAuth instead (which is what it seems they'd prefer you do).

This is designed to work with composer but at press time I haven't set the package up yet. However, you can check it out and play with it by using the hubspot-oauth.php file as an example. The Authenticate class in there is the meat of it - it will take care of the OAuth-ish authentication flow and contains the methods necessary to re-auth and run calls against the API.