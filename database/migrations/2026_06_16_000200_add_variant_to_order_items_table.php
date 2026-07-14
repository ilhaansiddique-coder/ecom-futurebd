<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('variant_id')->nullable()->after('product_id');
            // Snapshot of the variant at purchase time, kept even if the variant is later removed.
            $table->string('variant_size')->nullable()->after('product_name');
            $table->string('variant_color')->nullable()->after('variant_size');

            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
            $table->dropColumn(['variant_id', 'variant_size', 'variant_color']);
        });
    }
};
