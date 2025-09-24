<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index(); // link to users if we create one
            $table->string('business_name');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->nullable();
            $table->string('location')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('message')->nullable();
            $table->boolean('approved')->default(false);
            $table->text('notes')->nullable(); // admin notes
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // foreign key if users table exists (safe: add only if users table present)
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (Schema::hasTable('users')) {
                $table->dropForeign(['user_id']);
            }
        });

        Schema::dropIfExists('sellers');
    }
};
