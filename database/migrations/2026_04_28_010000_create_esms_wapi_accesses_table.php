<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esms_wapi_accesses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('esms_user_id')->nullable()->index();
            $table->string('email')->index();
            $table->string('google_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('device_number')->nullable()->index();
            $table->string('role')->default('device_user');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['email', 'device_number'], 'esms_wapi_access_email_device_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esms_wapi_accesses');
    }
};
