<?php

declare(strict_types=1);

namespace MyProject\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230322100452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users, access_tokens, and follows tables';
    }

    public function up(Schema $schema): void
    {
        $usersTable = $schema->createTable('users');
        $usersTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $usersTable->addColumn('username', 'string', ['length' => 191]);
        $usersTable->addColumn('instance', 'string', ['length' => 191]);
        $usersTable->addColumn('id_on_instance', 'integer');
        $usersTable->addColumn('created_at', 'datetime');
        $usersTable->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $usersTable->setPrimaryKey(['id']);
        $usersTable->addUniqueIndex(['id_on_instance', 'instance'], 'id_on_instance_instance');

        $accessTokensTable = $schema->createTable('access_tokens');
        $accessTokensTable->addColumn('instance', 'string', ['length' => 191]);
        $accessTokensTable->addColumn('id_on_instance', 'integer');
        $accessTokensTable->addColumn('access_token', 'string', ['length' => 191]);
        $accessTokensTable->addColumn('created_at', 'datetime');

        $followsTable = $schema->createTable('follows');
        $followsTable->addColumn('created_at', 'datetime');
        $followsTable->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $followsTable->addColumn('instance', 'string', ['length' => 191]);
        $followsTable->addColumn('id_on_instance', 'integer');
        $followsTable->addColumn('target_instance', 'string', ['length' => 191]);
        $followsTable->addColumn('target_id_on_instance', 'integer');
        $followsTable->setPrimaryKey(['instance', 'id_on_instance', 'target_instance', 'target_id_on_instance']);

    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('users');
    }
}
