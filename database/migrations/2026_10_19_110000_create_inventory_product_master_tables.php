<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('inv_categories')->nullOnDelete();
            $table->string('name');
            $table->string('default_storage_type')->nullable();
            $table->text('handling_rules')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_uoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('decimal_precision')->default(0);
            $table->timestamps();
        });

        Schema::create('inv_uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('from_uom_id')->constrained('inv_uoms')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('inv_uoms')->cascadeOnDelete();
            $table->decimal('conversion_factor', 14, 4);
            $table->timestamps();
        });

        Schema::table('inv_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained('inv_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('inv_brands')->nullOnDelete();
            $table->foreignId('purchase_uom_id')->nullable()->after('unit')->constrained('inv_uoms')->nullOnDelete();
            $table->foreignId('stock_uom_id')->nullable()->after('purchase_uom_id')->constrained('inv_uoms')->nullOnDelete();
            $table->foreignId('sales_uom_id')->nullable()->after('stock_uom_id')->constrained('inv_uoms')->nullOnDelete();
            $table->string('tax_category')->nullable();
            $table->boolean('track_stock')->default(true);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->foreignId('default_warehouse_id')->nullable()->constrained('inv_warehouses')->nullOnDelete();
            $table->foreignId('parent_item_id')->nullable()->constrained('inv_items')->nullOnDelete();
            $table->boolean('is_variant')->default(false);
        });

        Schema::create('inv_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('inv_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('inv_variant_attributes')->cascadeOnDelete();
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('inv_item_variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('variant_value_id')->constrained('inv_variant_values')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('inv_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->string('barcode');
            $table->string('type')->default('CODE128'); // CODE128, EAN13, QR
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('inv_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('inv_warehouses')->nullOnDelete();
            $table->string('batch_number');
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('supplier_lot')->nullable();
            $table->decimal('quantity', 14, 2)->default(0);
            $table->unsignedInteger('warranty_months')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('inv_warehouses')->nullOnDelete();
            $table->string('serial_number');
            $table->string('status')->default('in_stock'); // in_stock, sold, returned
            $table->date('warranty_expiry')->nullable();
            $table->timestamps();
            $table->unique(['item_id', 'serial_number']);
        });

        Schema::create('inv_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('inv_supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('inv_suppliers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->decimal('cost_price', 14, 2)->default(0);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->unique(['supplier_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_supplier_products');
        Schema::dropIfExists('inv_suppliers');
        Schema::dropIfExists('inv_serials');
        Schema::dropIfExists('inv_batches');
        Schema::dropIfExists('inv_barcodes');
        Schema::dropIfExists('inv_item_variant_values');
        Schema::dropIfExists('inv_variant_values');
        Schema::dropIfExists('inv_variant_attributes');

        Schema::table('inv_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_item_id');
            $table->dropConstrainedForeignId('default_warehouse_id');
            $table->dropColumn(['tax_category', 'track_stock', 'track_batch', 'track_serial', 'track_expiry', 'is_variant']);
            $table->dropConstrainedForeignId('sales_uom_id');
            $table->dropConstrainedForeignId('stock_uom_id');
            $table->dropConstrainedForeignId('purchase_uom_id');
            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('inv_uom_conversions');
        Schema::dropIfExists('inv_uoms');
        Schema::dropIfExists('inv_brands');
        Schema::dropIfExists('inv_categories');
    }
};
