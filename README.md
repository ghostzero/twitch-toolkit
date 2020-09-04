
# GhostZero's Twitch Toolkit

The main idea of this laravel package is to make it performant and easy to solve certain API problems with Twitch. Furthermore this package offers a toolkit to simplify certain processes (which have been provided by my [StreamKit](https://streamkit.gg/) platform):

* Twitch Username/ID Resolver & Cache
* Twitch Extension Guard Middleware
* Twitch Webhook/Polling Management
* Twitch Webhooks as Laravel Events

## TwitchUserResolver

```php
use GhostZero\TwitchToolkit\Utils\TwitchUserResolver;
use romanzipp\Twitch\Twitch;

// Fetch the user information from twitch based on the login or id.
// This response will be cached for 12h (because of rate limits).
if ($user = TwitchUserResolver::fetchUser('name or id', app(Twitch::class))) {
	$this->info("Fetched user {$user->login} successfully!");
}
```

## WIP: TwitchExtGuard

### 1. Register TwitchExtGuard (app/Providers/AuthServiceProvider.php)

```php
public function boot()  
{  
	...
	
	TwitchExtGuard::register(config('twitch-api.ext_secret'), new TwitchUserProvider);  
}
```

### 2. Configure TwitchUserProvider

```php
namespace App\Utils;

use App\User;
use GhostZero\TwitchToolkit\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
  
class TwitchUserProvider extends UserProvider  
{  
	public function retrieveById($identifier): ?Authenticatable  
	{
		/** @var User $user */  
		$user = User::query()->whereKey($identifier)->first();  

		return $user;  
	}  

	public function createFromTwitchToken($decoded): ?Authenticatable  
	{
		return User::createFromTwitchToken($decoded);  
	}  
}
```

### 3. Guard

```php
<?php

return [
    ...
    'guards' => [
        ...
        'twitch' => [
            'driver' => 'twitch',
            'provider' => 'users', // this is wrong, will be fixed soon
        ],
    ],
    ...
];

```

### 4. Middleware

```php
$this->middleware('auth:twitch')->only(['update']);
```

## Poll Installation (if you don't install Webhooks)

### 1. Setup Schedule (app/Console/Kernel.php)
```php
$schedule->command('twitch-toolkit:poll')->everyFiveMinutes();
```

### 2. Setup Events (app/Providers/EventServiceProvider.php)
```php
use GhostZero\TwitchToolkit\Events\WebhookWasCalled;

protected $listen = [  
	StreamUp::class => [  
		StreamUpListener::class,  
	],
	StreamDown::class => [  
		StreamDownListener::class,  
	],
	...
];
```

### 3. Create your listeners (StreamUpListener/StreamDownListener)

### 4. Register Events for a User (eg. Registered Event)

## Webhook Installation

### 1. Setup Schedule (app/Console/Kernel.php)
```php
$schedule->command('twitch-toolkit:subscribe-webhooks')->everyMinute();
```

### 2. Setup Events (app/Providers/EventServiceProvider.php)
```php
use GhostZero\TwitchToolkit\Events\WebhookWasCalled;

protected $listen = [  
	WebhookWasCalled::class => [  
		StoreWebhookActivity::class,  
	],
	...
];
```

### 3. Create your own Event Listener

```php
namespace App\Listeners;

use GhostZero\TwitchToolkit\Events\WebhookWasCalled;  
  
class StoreWebhookActivity  
{  
	public function handle(WebhookWasCalled $event): void  
	{
		// business logic  
    }  
}
```

### 4. Setup Access Token Handler (app/Providers/AppServiceProvider.php)

```php
use GhostZero\TwitchToolkit\Models\Channel as WebhookChannel;

public function boot(): void
{
	// sometimes twitch-toolkit requires fresh access tokens
	// they will be handled in the requiresFreshOauthCredentials closure
    WebhookChannel::requiresFreshOauthCredentials(function (WebhookChannel $channel) {
        // fetch your user access tokens and fill them in the channel object        
        $channel->forceFill([
            'broadcaster_type' => '...',
            'oauth_access_token' => '...',
            'oauth_refresh_token' => '...',
            'oauth_expires_at' => new CarbonImmutable('...'),
        ])->save();
    });
}
```

### 5. Expand your own Channel Model

```php
namespace App\Models;

use GhostZero\TwitchToolkit\Models\Channel as WebhookChannel;  
use GhostZero\TwitchToolkit\Models\WebSub;

/**
 * @property string id  
 * @property WebhookChannel webhook_channel  
 * @property WebSub[] web_subs  
 */
class Channel extends Model  
{
	public function webhook_channel(): BelongsTo  
	{  
		return $this->belongsTo(WebhookChannel::class, 'id');  
	}  
	  
	public function web_subs(): HasMany  
	{  
		return $this->hasMany(WebSub::class, 'channel_id');  
	}
}
```

### 6. Register Events for a User (eg. Registered Event)
```php
use GhostZero\TwitchToolkit\Jobs\SubscribeTwitchWebhooks;  
use GhostZero\TwitchToolkit\Models\Channel as WebhookChannel;

// creates a twitch webhook subscription  
$webhookChannel = WebhookChannel::subscribe($channel->getKey(), [  
	WebhookChannel::OPTION_CAPABILITIES => $attributes['capabilities'],  
	WebhookChannel::OPTION_BROADCASTER_TYPE => $attributes['broadcaster_type'],  
]);  
  
if (in_array(WebhookChannel::TYPE_WEBHOOK, $attributes['capabilities'], true)) {  
	dispatch_now(new SubscribeTwitchWebhooks($webhookChannel));  
}  

// check webhook setup
if ($channel->web_subs()->count() < 2) {  
	return response()->json([  
		'message' => 'We need at least 2 WebSubs from Twitch.'  
	], 409);  
}  

// check token generation
if (!$webhookChannel->oauth_access_token) {  
	return response()->json([  
		'message' => 'We couldn\'t get a Twitch access token from our SSO.'  
	], 409);  
}  
  
return response('', 204);
```
