<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Invoices the system owner issues to institutions. */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('bill_to_name');
            $table->string('bill_to_email')->nullable();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->json('items');
            $table->decimal('total', 14, 2)->default(0);
            $table->string('currency', 3)->default('KES');
            $table->string('status', 10)->default('draft'); // draft | sent | paid | cancelled
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
