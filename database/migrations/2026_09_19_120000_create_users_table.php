<?php

declare(strict_types=1);

use Core\Model\Database\Blueprint;
use Core\Model\Database\Migration;
use Core\Model\Database\Schema;

class CreateUsersTable extends Migration
{
    public function up(Schema $schema): void
    {
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('users');
    }
}
