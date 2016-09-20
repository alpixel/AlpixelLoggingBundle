
AlpixelLoggingBundle
=================

Purpose of this bundle is to offer better automation of log handler.
Mainly about Slack reporting.

### Configuration

In your AppKernel

```
new Alpixel\Bundle\LoggingBundle\AlpixelLoggingBundle(),
new Symfony\Bundle\MonologBundle\MonologBundle(),
```

In your config.yml

```
alpixel_logging:
    slack:
      token:    "my token"
      bot_name: "Error Bot"
      channel:  "alerts"
      debug: false #To activate in debug environment
```
