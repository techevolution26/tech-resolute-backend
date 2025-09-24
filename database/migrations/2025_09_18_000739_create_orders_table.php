<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string(column: 'customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('shipping_address')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('message')->nullable();
            $table->enum('status',['new','pending','contacted','completed','cancelled'])->default('new');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('orders');
    }
};
