<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('expense_category_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('expense_date');
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_date', 'expense_category_id']);
            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
