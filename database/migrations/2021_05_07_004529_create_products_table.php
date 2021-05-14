<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class CreateProductsTable extends \App\System\Database\Migration
{
    protected $table = 'products';

    public function up()
    {
        $this->create(function (SchemaBuilder $builder){
            $builder->bigIncrements('product_id')->unsigned();
            $builder->unsignedBigInteger('mgc_id');
            $builder->unsignedBigInteger('category_id');
            $builder->string('name');
            $builder->string('brand')->nullable();
            $builder->string('vendor')->nullable();
            $builder->string('prefix')->nullable();
            $builder->string('vendor_code')->nullable();
            $builder->unsignedBigInteger('in_stock');
            $builder->boolean('available')->default(true);
            $builder->boolean('downloadable')->default(false);
            $builder->float('price', 12);
            $builder->float('discount', 12)->nullable()->default(0);
        });

        $this->alter(function (SchemaBuilder $builder) {
            $builder->foreign('category_id')
                ->references('category_id')
                ->on('categories')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        $this->dropIfExists();
    }
}