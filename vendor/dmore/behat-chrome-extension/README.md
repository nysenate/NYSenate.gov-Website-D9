Chrome Behat Extension
======================

Behat extension for controlling chrome without the overhead of selenium.

It communicates directly with chrome over HTTP and WebSockets, which allows it to work at least twice as fast as chrome with selenium.

For chrome 59+ it supports headless mode, eliminating the need to install a display server, and the overhead that comes with it.

This driver is tested and benchmarked against a behat suite of 1800 scenarios and 19000 steps. It can successfully run it in less than 18 minutes with chrome 60 headless.

The same suite running against chrome 58 with xvfb and selenium takes ~60 minutes.

## Installation:

```bash
composer require dmore/behat-chrome-extension
```

## Requirements:

* Google chrome or chromium running with remote debugging

Example:

```bash
google-chrome-stable --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222
```

or headless (59+):

```bash
google-chrome-unstable --disable-gpu --headless --remote-debugging-address=0.0.0.0 --remote-debugging-port=9222
```

The official docker image includes chrome 60 running headless.

See https://gitlab.com/DMore/behat-chrome-skeleton for a fully working example.

## Usage:

```yaml
default:
    extensions:
        DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension: ~
        Behat\MinkExtension:
            browser_name: chrome
            base_url: http://localhost
            sessions:
                default:
                    chrome:
                        api_url: "http://localhost:9222"
```

## Configuration

The following optional configuration settings are available

### validate_certificate

Default: true

Set to false to ignore invalid ssl certificates.

### socket_timeout

Default: 10

Timeout in seconds before the HTTP or WebSocket connection to the chrome browser is considered dead.

### download_behavior

Default: falls back to browser configuration

Set to 'allow' to allow downloads.

### dom_wait_timeout

Default: 3000

How many milliseconds we should wait for DOM to be ready after each action/transition

### download_path

Default: /tmp

Set to a directory path where you would like files to be downloaded. Requires download_behavior: allow

Example:

```yaml
default:
    extensions:
        DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension: ~
        Behat\MinkExtension:
            browser_name: chrome
            base_url: http://localhost
            sessions:
                default:
                    chrome:
                        api_url: "http://localhost:9222"
                        download_behavior: allow
                        download_path: /download
                        validate_certificate: false
```

## Contributing

You are encouraged to fork this repository and contribute your own improvements.
