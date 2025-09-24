<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // seller who owns/markets the product (marketplace model)
            $table->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();

            // admin/user who created the product in admin UI (nullable)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('KES'); // keeping 10 for compatibility; switch to 3 later

            $table->string('condition')->nullable(); // e.g. New, Refurbished, Digital

            $table->integer('stock')->default(0);

            // store a public URL (/storage/...) or S3 URL
            $table->string('image_url')->nullable();

            // lifecycle + status for admin controls
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');

            // optional SKU and metadata
            $table->string('sku')->nullable()->index();
            $table->json('meta')->nullable();

            $table->softDeletes(); // deleted_at
            $table->timestamps();

            // indexes helpful for marketplace queries
            $table->index(['category_id', 'status']);
            $table->index(['seller_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
