<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class CreateCategoryParameterTable extends \App\System\Database\Migration
{
    protected $table = 'category_parameter';

    public function up()
    {
        $this->create(function (SchemaBuilder $builder){
            $builder->bigIncrements('id')->unsigned();
            $builder->unsignedBigInteger('category_id');
            $builder->unsignedBigInteger('parameter_id');
            $builder->string('value')->nullable();
            $builder->json('array_value')->nullable();
        });

        $this->alter(function (SchemaBuilder $builder) {
            $builder->foreign('category_id')
                ->references('category_id')
                ->on('categories')
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