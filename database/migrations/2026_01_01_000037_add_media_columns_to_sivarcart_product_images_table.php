<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tarkontech\SivarCart\Models\Product;

/**
 * Gives the table of the images the shape the media library needs.
 *
 * The package stores the media with Spatie Media Library, but on its own
 * table: the table of the images keeps its name and its prefix, so every
 * table of the package still lives under "sivarcart_".
 *
 * The migration only adds columns: an installation that already had the
 * package keeps its rows and its files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sivarcart_product_images', function (Blueprint $table): void {
            // The table now holds the media of the whole store, not only the
            // media of the products: the logo of a brand and the logo of the
            // store live here too, and they belong to no product.
            $table->foreignId('product_id')->nullable()->change();

            // The path of the file depends on the id of the media, and the
            // media library writes the row before it knows that id: the path
            // is filled right after, inside the same transaction.
            $table->string('path')->nullable()->change();
        });

        Schema::table('sivarcart_product_images', function (Blueprint $table): void {
            $table->string('model_type')->nullable()->after('product_variant_id');
            $table->unsignedBigInteger('model_id')->nullable()->after('model_type');
            $table->uuid('uuid')->nullable()->after('model_id');
            $table->string('collection_name')->default('images')->after('uuid');
            $table->string('name')->default('')->after('collection_name');
            $table->string('file_name')->default('')->after('name');
            $table->string('mime_type')->nullable()->after('file_name');
            $table->string('conversions_disk')->nullable()->after('mime_type');
            $table->unsignedBigInteger('size')->default(0)->after('conversions_disk');
            $table->json('manipulations')->nullable()->after('size');
            $table->json('custom_properties')->nullable()->after('manipulations');
            $table->json('generated_conversions')->nullable()->after('custom_properties');
            $table->json('responsive_images')->nullable()->after('generated_conversions');
        });

        Schema::table('sivarcart_product_images', function (Blueprint $table): void {
            $table->index(['model_type', 'model_id']);
            $table->unique('uuid');
        });

        $this->fillTheRowsThatWereAlreadyThere();
    }

    public function down(): void
    {
        Schema::table('sivarcart_product_images', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropIndex(['model_type', 'model_id']);

            $table->dropColumn([
                'model_type',
                'model_id',
                'uuid',
                'collection_name',
                'name',
                'file_name',
                'mime_type',
                'conversions_disk',
                'size',
                'manipulations',
                'custom_properties',
                'generated_conversions',
                'responsive_images',
            ]);
        });

        // The columns that the media of the brands and of the store left
        // empty stay nullable: a rollback does not throw away the images of
        // the store, and a column that does not accept nulls would not be
        // able to hold them anymore.
        Schema::table('sivarcart_product_images', function (Blueprint $table): void {
            $table->foreignId('product_id')->nullable()->change();
            $table->string('path')->nullable()->change();
        });
    }

    /**
     * Fill the rows that were written before the media library arrived.
     *
     * Nothing ever wrote in this table, so it is normally empty. A store that
     * filled it by hand keeps its rows: the metadata is filled here, but the
     * files stay where they are, because the media library expects them in
     * the folder of the media.
     */
    private function fillTheRowsThatWereAlreadyThere(): void
    {
        $rows = DB::table('sivarcart_product_images')
            ->whereNull('model_type')
            ->get();

        foreach ($rows as $row) {
            $fileName = basename((string) $row->path);

            DB::table('sivarcart_product_images')
                ->where('id', $row->id)
                ->update([
                    'model_type' => Product::class,
                    'model_id' => $row->product_id,
                    'collection_name' => 'images',
                    'name' => pathinfo($fileName, PATHINFO_FILENAME),
                    'file_name' => $fileName,
                    'custom_properties' => json_encode(['alt' => $row->alt]),
                    'manipulations' => json_encode([]),
                    'generated_conversions' => json_encode([]),
                    'responsive_images' => json_encode([]),
                ]);
        }
    }
};
