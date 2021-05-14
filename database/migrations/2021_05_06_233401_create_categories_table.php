<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class CreateCategoriesTable extends \App\System\Database\Migration
{
    protected $table = 'categories';

    public function up()
    {
        $this->create(function (SchemaBuilder $builder){
            $builder->bigIncrements('category_id')->unsigned();
            $builder->mediumText('name');
            $builder->unsignedBigInteger('mgc_id');
            $builder->unsignedBigInteger('parent_id')->nullable();
        });
    }

    public function down()
    {
        $this->dropIfExists();
    }
}