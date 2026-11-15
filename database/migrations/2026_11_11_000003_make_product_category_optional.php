<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A product's category becomes optional: products.category_id may be NULL.
 * Existing products keep their categories, and the foreign key is unchanged,
 * so a category still cannot be deleted while products use it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations. Stops with an error while any product has no
     * category, instead of deleting those products or inventing a category.
     */
    public function down(): void
    {
        if (DB::table('products')->whereNull('category_id')->exists()) {
            throw new RuntimeException(
                'Some products have no category, so category_id cannot be made required again. '
                .'Assign a category to every product first.'
            );
        }

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }
};
