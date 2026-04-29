<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_segment_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_segment_id')->constrained('customer_segments')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_detail_id');
            $table->timestamps();

            $table->unique(['customer_segment_id', 'customer_detail_id'], 'segment_member_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_segment_members');
    }
};
