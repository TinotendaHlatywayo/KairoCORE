<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('category', 30); // admissions, finance, academic, communication
            $table->string('mailer', 30)->default('platform'); // platform, smtp, log, sendmail
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('host')->nullable();
            $table->string('port')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('encryption', 10)->nullable(); // tls, ssl
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_configurations');
    }
};
