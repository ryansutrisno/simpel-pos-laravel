<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_item']);
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('min_quantity')->default(2);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'start_date', 'end_date']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
    }
};
