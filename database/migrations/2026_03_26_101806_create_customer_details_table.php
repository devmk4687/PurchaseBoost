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
        Schema::create('customer_details', function (Blueprint $table) {
            $table->id();
            $table->string('customerId')->unique();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('company');
            $table->string('city');
            $table->string('country');
            $table->string('phone1');
            $table->string('phone2');
            $table->string('email');
            $table->string('subscriptionDate');
            $table->string('website');
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
        Schema::dropIfExists('customer_details');
    }
};
