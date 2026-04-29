<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('communication_template_id')->nullable()->constrained('communication_templates')->nullOnDelete();
            $table->foreignId('customer_detail_id')->nullable()->constrained('customer_details')->nullOnDelete();
            $table->enum('channel', ['email', 'sms', 'whatsapp']);
            $table->string('recipient')->nullable();
            $table->string('subject')->nullable();
            $table->longText('message')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('communication_logs');
    }
};
