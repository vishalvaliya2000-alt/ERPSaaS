<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations without touching existing data.
     */
    public function up(): void
    {
        // 1. Add tax breakdown and audit columns to existing credit_debit_notes table
        if (Schema::hasTable('credit_debit_notes')) {
            Schema::table('credit_debit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_debit_notes', 'cgst_amount')) {
                    $table->decimal('cgst_amount', 15, 2)->default(0.00)->after('tax_amount');
                }
                if (!Schema::hasColumn('credit_debit_notes', 'sgst_amount')) {
                    $table->decimal('sgst_amount', 15, 2)->default(0.00)->after('cgst_amount');
                }
                if (!Schema::hasColumn('credit_debit_notes', 'igst_amount')) {
                    $table->decimal('igst_amount', 15, 2)->default(0.00)->after('sgst_amount');
                }
                if (!Schema::hasColumn('credit_debit_notes', 'is_interstate')) {
                    $table->boolean('is_interstate')->default(false)->after('igst_amount');
                }
                if (!Schema::hasColumn('credit_debit_notes', 'created_by_user_id')) {
                    $table->unsignedBigInteger('created_by_user_id')->nullable()->after('status');
                }
            });
        }

        // 2. Create credit_debit_note_items table for line-item returns, discounts & weight loss
        if (!Schema::hasTable('credit_debit_note_items')) {
            Schema::create('credit_debit_note_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('credit_debit_note_id')->index();
                $table->unsignedBigInteger('invoice_item_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('description', 255);
                $table->string('hsn_code', 30)->nullable();
                $table->decimal('quantity', 15, 2)->default(0.00);
                $table->string('uom', 20)->default('KGS');
                $table->decimal('rate', 15, 2)->default(0.00);
                $table->decimal('subtotal', 15, 2)->default(0.00);
                $table->decimal('tax_rate_percent', 5, 2)->default(5.00);
                $table->decimal('tax_amount', 15, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->timestamps();

                $table->foreign('credit_debit_note_id')
                    ->references('id')
                    ->on('credit_debit_notes')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_debit_note_items');

        if (Schema::hasTable('credit_debit_notes')) {
            Schema::table('credit_debit_notes', function (Blueprint $table) {
                $columns = ['cgst_amount', 'sgst_amount', 'igst_amount', 'is_interstate', 'created_by_user_id'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('credit_debit_notes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
