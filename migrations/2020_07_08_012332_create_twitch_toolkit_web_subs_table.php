<?php

use GhostZero\TwitchToolkit\Jobs\SubscribeTwitchWebhooks;
use GhostZero\TwitchToolkit\Models\Channel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwitchToolkitWebSubsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('twitch_toolkit_web_subs', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->nullable();
            $table->string('feed_url')->unique();
            $table->string('callback_url');
            $table->string('secret')->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('active')->default(false);
            $table->integer('lease_seconds')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('leased_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->boolean('denied')->default(false);
            $table->timestamp('denied_at')->nullable();
            $table->text('denied_reason')->nullable();
            $table->longText('last_response')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('twitch_toolkit_webhook_subscriptions');

        Channel::query()
            ->whereJsonContains('capabilities', Channel::TYPE_WEBHOOK)
            ->eachById(function (Channel $channel) {
                dispatch(new SubscribeTwitchWebhooks($channel));
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('twitch_toolkit_web_subs');
    }
}
