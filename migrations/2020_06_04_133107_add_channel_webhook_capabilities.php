<?php

use GhostZero\LPTHOOT\Models\Channel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChannelWebhookCapabilities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lpthoot_channels', function (Blueprint $table) {
            $table->string('capabilities')->default(json_encode([
                Channel::TYPE_POLLING,
            ]));
            $table->string('oauth_access_token')->nullable();
            $table->string('oauth_refresh_token')->nullable();
            $table->timestamp('oauth_expires_at')->nullable();
            $table->string('broadcaster_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lpthoot_channels', function (Blueprint $table) {
            $table->dropColumn([
                'capabilities',
                'oauth_access_token',
                'oauth_refresh_token',
                'oauth_expires_at',
            ]);
        });
    }
}
