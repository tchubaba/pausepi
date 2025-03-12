<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {
    protected string $table = 'pi_hole_boxes';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->string('api_key')->nullable()->change();
            $table->string('password', 400)->nullable()->after('api_key');
            $table->unsignedSmallInteger('version')->default(5)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->string('api_key')->nullable(false)->change();
            if (Schema::hasColumn($this->table, 'password')) {
                $table->dropColumn('password');
            }
            if (Schema::hasColumn($this->table, 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
