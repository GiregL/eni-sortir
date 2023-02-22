<?php

namespace DoctrineMigrations;

use App\Model\EventState;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationException;

class Version20230222103300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajout des valeurs possibles pour l'énum des états de sorties.";
    }


    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM `state`");
        $this->addSql("ALTER TABLE `state` ADD CONSTRAINT UK_STATE_IDENTIFIER UNIQUE (`label`)");

        foreach (EventState::values() as $state) {
            $this->addSql('INSERT INTO state(label) VALUES (\'' . $state->getIdentifier() . '\')');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `state` DELETE CONSTRAINT UK_STATE_IDENTIFIER");
        $this->addSql("DELETE FROM `state`");
    }


}