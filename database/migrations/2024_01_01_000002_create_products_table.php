<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            // Legacy issue: category_id has no foreign key and no index.
            // Legacy issue: filters by name/status/price/stock are not indexed.
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
