<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Product images are stored in the database, one per product, so they persist on
 * hosts without a permanent disk. This replaces the earlier file path column;
 * images uploaded before it are copied in from the public disk.
 *
 * On MySQL and TiDB every schema change is committed immediately, so a failed run
 * can leave a partly created table or partly copied images behind. Until the old
 * image_path column is removed (the last step), running the migration again
 * rebuilds product_images from scratch and copies the files again; once the
 * column is gone, it does nothing. This is not atomic: the source files are
 * left untouched, and a run that stops part-way must simply be run again.
 */
return new class extends Migration
{
    /**
     * Image types that are copied, matching the upload validation.
     */
    private const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Already completed: the old column is removed last.
        if (! Schema::hasColumn('products', 'image_path')) {
            return;
        }

        // While the old column exists, a product_images table can only be left by
        // an interrupted run and may lack its constraints, so it is rebuilt.
        Schema::dropIfExists('product_images');

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            // One image per product; it is deleted together with its product.
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mime_type', 50);
            $table->binary('data');
            $table->timestamps();
        });

        // Laravel's binary column is a BLOB on MySQL, which holds only 64 KB;
        // MEDIUMBLOB holds up to 16 MB, well above the 2 MB upload limit.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE product_images MODIFY data MEDIUMBLOB NOT NULL');
        }

        $this->copyExistingImages();

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    /**
     * Reverse the migrations. The stored images are deleted and are not written
     * back to files: products keep an empty image_path afterwards.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('products', 'image_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('image_path')->nullable();
            });
        }

        Schema::dropIfExists('product_images');
    }

    /**
     * Copies images uploaded before this migration from the public disk into the
     * database. Only paths in the format the application generated are read
     * (products/<file name>); other paths, symbolic links and missing, unreadable
     * or non-image files are skipped.
     */
    private function copyExistingImages(): void
    {
        $disk = Storage::disk('public');

        DB::table('products')
            ->whereNotNull('image_path')
            ->select('id', 'image_path')
            ->lazyById(100)
            ->each(function (object $product) use ($disk) {
                $path = $product->image_path;

                // Uploads were stored as products/<name>; anything else, including
                // "..", is skipped instead of being read or stopping the migration.
                if (! preg_match('#^products/[A-Za-z0-9._-]+$#', $path) || str_contains($path, '..')) {
                    return;
                }

                if (! $disk->exists($path) || is_link($disk->path($path))) {
                    return;
                }

                $data = $disk->get($path);

                // The type is detected from the bytes alone: the disk's own mimeType()
                // falls back to the file extension when the content is inconclusive.
                $mimeType = $data === null ? null : (new finfo(FILEINFO_MIME_TYPE))->buffer($data);

                if (! in_array($mimeType, self::MIME_TYPES, true)) {
                    return;
                }

                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'mime_type' => $mimeType,
                    'data' => $data,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
};
