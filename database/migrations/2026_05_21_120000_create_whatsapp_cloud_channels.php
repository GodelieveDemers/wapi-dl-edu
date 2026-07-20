<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_cloud_channels')) {
            Schema::create('whatsapp_cloud_channels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('provider', 30)->default('cloud_api');
                $table->string('business_name', 160)->nullable();
                $table->string('phone_number', 30);
                $table->string('phone_number_id', 80);
                $table->string('waba_id', 80);
                $table->text('access_token')->nullable();
                $table->string('app_id', 80)->nullable();
                $table->text('app_secret')->nullable();
                $table->string('verify_token', 160)->nullable();
                $table->string('quality_rating', 40)->nullable();
                $table->string('messaging_limit', 80)->nullable();
                $table->string('webhook_status', 40)->default('pending');
                $table->string('status', 40)->default('draft');
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'phone_number_id']);
                $table->index(['user_id', 'provider']);
            });
        }

        if (!Schema::hasTable('whatsapp_embedded_signup_logs')) {
            Schema::create('whatsapp_embedded_signup_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event', 120)->default('callback');
                $table->longText('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_embedded_signup_logs');
        Schema::dropIfExists('whatsapp_cloud_channels');
    }
};
