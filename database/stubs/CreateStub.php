<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class StubClass extends \App\System\Database\Migration
{
    protected $table = 'stubTable';

    public function up()
    {
        $this->create(function (SchemaBuilder $builder){
            $builder->increments('id');
        });
    }

    public function down()
    {
        $this->dropIfExists();
    }
}