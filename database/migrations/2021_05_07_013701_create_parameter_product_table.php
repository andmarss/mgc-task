<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class CreateParameterProductTable extends \App\System\Database\Migration
{
    protected $table = 'parameter_product';

    public function up()
    {
        $this->create(function (SchemaBuilder $builder){
            $builder->bigIncrements('id')->unsigned();
            $builder->unsignedBigInteger('product_id');
            $builder->unsignedBigInteger('parameter_id');
            $builder->string('value')->nullable();
            $builder->json('array_value')->nullable();
        });

        $this->alter(function (SchemaBuilder $builder) {
            $builder->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->onDelete('cascade');

            $builder->foreign('parameter_id')
                ->references('parameter_id')
                ->on('parameters')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        $this->dropIfExists();
    }
}