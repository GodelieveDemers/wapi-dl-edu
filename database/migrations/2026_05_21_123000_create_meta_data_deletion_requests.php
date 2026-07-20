<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('meta_data_deletion_requests')) {
            Schema::create('meta_data_deletion_requests', function (Blueprint $table) {
                $table->id();
                $table->string('meta_user_id')->nullable()->index();
                $table->string('confirmation_code')->unique();
                $table->string('status', 40)->default('received');
                $table->longText('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_data_deletion_requests');
    }
};
