<?php

namespace Pantono\Database\Migration\Base;

use Phinx\Migration\AbstractMigration;
use Pantono\Database\Migration\Traits\DisableForeignKeyChecksTrait;
use Pantono\Database\Migration\Traits\ReseedIdentityTrait;

class BasePantonoMigration extends AbstractMigration
{
    use DisableForeignKeyChecksTrait;
    use ReseedIdentityTrait;
}
