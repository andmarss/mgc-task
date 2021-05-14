<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class CreateParametersTable extends \App\System\Database\Migration
{
    protected $table = 'parameters';

    public function up()
    {
        $this->create(function (SchemaBuilder $builder){
            $builder->bigIncrements('parameter_id')->unsigned();
            $builder->string('name');
            $builder->unsignedBigInteger('mgc_id')->nullable();
        });
    }

    public function down()
    {
        $this->dropIfExists();
    }
}