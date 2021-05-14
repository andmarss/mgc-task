<?php

namespace Migrations;

use App\System\Database\SchemaBuilder;

class StubClass extends \App\System\Database\Migration
{
    protected $table = 'stubTable';

    public function up()
    {
        $this->alter(function (SchemaBuilder $builder){
            //
        });
    }

    public function down()
    {
        $this->dropIfExists();
    }
}