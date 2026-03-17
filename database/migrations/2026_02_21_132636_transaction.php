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
    {   Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('orderId');

            $table->integer('custId');

            $table->decimal('credit', 10, 2);
            
            $table->decimal('debit', 10, 2);

            $table->text('description')->nullable();

            $table->json('meta_data')->nullable();

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
        //
    }
};
