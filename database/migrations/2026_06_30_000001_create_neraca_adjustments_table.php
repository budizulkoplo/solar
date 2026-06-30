<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('neraca_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('module', 30);
            $table->unsignedBigInteger('scope_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('side', 20);
            $table->string('row_key', 191);
            $table->string('label');
            $table->decimal('value', 18, 2)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['module', 'scope_id', 'start_date', 'end_date', 'side', 'row_key'], 'neraca_adjustments_unique_row');
            $table->index(['module', 'scope_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neraca_adjustments');
    }
};
