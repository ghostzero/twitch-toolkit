<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookSubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lpthoot_webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('activity');
            $table->unsignedInteger('channel_id');
            $table->unsignedInteger('lease');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('leased_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lpthoot_webhook_subscriptions');
    }
}
