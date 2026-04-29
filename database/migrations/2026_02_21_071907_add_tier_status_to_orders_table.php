<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('orders') || Schema::hasColumn('orders', 'tierStatus')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('tierStatus')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'tierStatus')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tierStatus');
        });
    }
};
