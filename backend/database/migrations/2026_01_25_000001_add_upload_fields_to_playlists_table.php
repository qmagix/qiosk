<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->boolean('allow_uploads')->default(false)->after('access_token');
            $table->string('upload_token', 32)->nullable()->unique()->after('allow_uploads');
            $table->enum('upload_mode', ['auto_add', 'require_approval'])->default('auto_add')->after('upload_token');
            $table->unsignedInteger('qr_frequency')->default(5)->after('upload_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->dropColumn(['allow_uploads', 'upload_token', 'upload_mode', 'qr_frequency']);
        });
    }
};
