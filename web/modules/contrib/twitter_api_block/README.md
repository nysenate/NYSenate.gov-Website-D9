# Twitter API Block

This module adds a blocks to search and display/embed tweets in Drupal.

It uses the Twitter API v2.

It loads the necessary [Javascript](https://platform.twitter.com/widgets.js)
from Twitter for you.

It requires `drupal/key` module to create a Twitter application and securely
save credentials.

There is no other dependencies.

## Configuration

Create a Twitter app and save its secret credentials in a new _Key_ in Drupal:
- Signin/Signup on the Twitter Developer platform
- Create a new project
- Create a new application in this project
- Copy `client_id` and `client_secret`
- Go to **Admin > Configuration > System > Keys** and create a new key
- Select `Twitter API` as key type
- Past `client_id` and `client_secret`
- Save the key

## Usage

Create a Twitter block as follow:
- Go to **Admin > Block layout**
- Place a new _Twitter search_ block somewhere
- Configure the search (e.g. see [examples](#Examples) below for advanced search)
- Save the block and enjoy your tweets!

Template are is customizable in your theme with;

- `tweets.html.twig`
- `tweet.html.twig`

Tweets can be rendered as _oembed_ content (e.g. the default embed Tweet) or as
raw text.

## Examples

This module uses the powerful `query` parameter provided by Twitter API v2.

There are infinite possibilities... but here are a few common use cases:

- **Display tweets matching word(s)**
```
apple
apple chocolate
apple OR iphone ipad
manzana lang:es has:media -is:retweet
```

- **Display tweets for a specific #hashtag**
```
#drupal
#drupal has:media
#drupal has:links -is:retweet
```

- **Display tweets in the current language**
```
#drupal lang:fr
#drupal lang:[language:langcode]
```

- **Display tweets from a given user**
```
composer from:drupal
composer from:[your-token-with-the-twitter-user-name]
```

## Resources

- [Twitter API doc](https://developer.twitter.com/en/docs/twitter-api)
- [Build a search query](https://developer.twitter.com/en/docs/twitter-api/tweets/search/integrate/build-a-query).
- [Search API reference](https://developer.twitter.com/en/docs/twitter-api/tweets/search/api-reference/get-tweets-search-recent).
- [Fields references](https://developer.twitter.com/en/docs/twitter-api/fields)
