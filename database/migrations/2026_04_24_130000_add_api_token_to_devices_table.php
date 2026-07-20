<?php

use App\Models\Device;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('api_token', 80)->nullable()->after('body');
        });

        DB::table('devices')
            ->select(['id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $device): void {
                DB::table('devices')
                    ->where('id', $device->id)
                    ->update([
                        'api_token' => Device::generateApiToken(),
                    ]);
            });

        Schema::table('devices', function (Blueprint $table) {
            $table->unique('api_token');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['api_token']);
            $table->dropColumn('api_token');
        });
    }
};
