# Cloudflare Turnstile for Drupal

The Cloudflare Turnstile module uses the Turnstile web service to augment
the CAPTCHAsystem and protect forms. For more information on what Turnstile
is, please visit:
[Turnstile](https://developers.cloudflare.com/turnstile)


## Contents of this file

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements 
 
- Turnstile module depends on the CAPTCHA module.
  [CAPTCHA](https://drupal.org/project/captcha)


## Installation

1. Enable Cloudflare Turnstile and CAPTCHA modules on:
   `admin/modules`


## Configuration

1. You'll now find a Turnstile tab on the CAPTCHA administration page available at:
   `admin/config/people/captcha/turnstile`

1. Register on: [cloudflare](https://cloudflare.com/)

1. Input the site and secret keys into the Cloudflare settings.

1. Visit the CAPTCHA administration page and set where you want the Turnstile form to be 
   presented: `admin/config/people/captcha`


## Maintainers

Current maintainers:
- Adam Weiss - [greatmatter](https://www.drupal.org/u/greatmatter)
