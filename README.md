# Let's Poll The Hell Out Of Twitch

A simple library to poll the streamers status instead of using webhooks. Handle up to 400.000 channels per 5 minutes.

![](https://media1.tenor.com/images/febe616434a96154fb7010bd9fb49322/tenor.gif)


## Installation

```php
/*
 * Package Service Providers...
 */
\GhostZero\LPTHOOT\Providers\PollServiceProvider::class,
```

```php
$schedule->command('lpthoot:poll')->everyFiveMinutes();
```
