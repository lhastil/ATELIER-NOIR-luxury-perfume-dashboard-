<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Product images move from the database to files on the "public" disk. Each
 * product keeps the relative path of its file in products.image_path, and the
 * product_images table, which held the image bytes, is removed.
 *
 * The migration does not copy images: it stops with an error if product_images
 * still contains rows, so no image is ever deleted silently. Each check runs
 * before any schema change, because MySQL and TiDB commit schema changes
 * immediately and a half-finished run could not be rolled back.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('product_images') && DB::table('product_images')->exists()) {
            throw new RuntimeException(
                'The product_images table still contains images. They would be lost if the table '
                .'were dropped, so the migration was stopped. Move or remove those images first.'
            );
        }

        if (! Schema::hasColumn('products', 'image_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('image_path')->nullable();
            });
        }

        Schema::dropIfExists('product_images');
    }

    /**
     * Reverse the migrations: restores the empty product_images table exactly
     * as 2026_10_21_000006 created it, and removes products.image_path. Image
     * files are not moved back into the database, so this stops with an error
     * if any product still has an image path.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'image_path') && DB::table('products')->whereNotNull('image_path')->exists()) {
            throw new RuntimeException(
                'Some products still have image files. Rolling back would lose their image paths, '
                .'so the rollback was stopped. Remove those images first.'
            );
        }

        if (! Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                // One image per product; it is deleted together with its product.
                $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('mime_type', 50);
                $table->binary('data');
                $table->timestamps();
            });

            // As in the original migration: MEDIUMBLOB holds up to 16 MB on MySQL.
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                DB::statement('ALTER TABLE product_images MODIFY data MEDIUMBLOB NOT NULL');
            }
        }

        if (Schema::hasColumn('products', 'image_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }
};
