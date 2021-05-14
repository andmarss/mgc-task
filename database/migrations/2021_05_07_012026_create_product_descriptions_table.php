<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class CreateProductDescriptionsTable extends \App\System\Database\Migration
{
    protected $table = 'product_descriptions';

    public function up()
    {
        $this->create(function (SchemaBuilder $builder){
            $builder->bigIncrements('product_description_id')->unsigned();
            $builder->unsignedBigInteger('product_id');
            $builder->mediumText('picture')->nullable();
            $builder->longText('annotation')->nullable();
            $builder->text('terms_conditions')->nullable();
            $builder->text('activation_rules')->nullable();
            $builder->mediumText('terms_of_use')->nullable();
        });

        $this->alter(function (SchemaBuilder $builder) {
            $builder->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        $this->dropIfExists();
    }
}