<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockMovementsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('type');
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            // Legacy issue: no foreign keys and no indexes for product_id/type.
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
    }
}
