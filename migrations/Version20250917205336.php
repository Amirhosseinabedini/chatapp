<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917205336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "group" (id SERIAL NOT NULL, owner_id INT NOT NULL, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_public BOOLEAN NOT NULL, invite_code VARCHAR(50) DEFAULT NULL, invite_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6DC044C57E3C61F9 ON "group" (owner_id)');
        $this->addSql('COMMENT ON COLUMN "group".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "group".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "group".invite_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE group_member (id SERIAL NOT NULL, group_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(20) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_muted BOOLEAN NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A36222A8FE54D947 ON group_member (group_id)');
        $this->addSql('CREATE INDEX IDX_A36222A8A76ED395 ON group_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GROUP_USER ON group_member (group_id, user_id)');
        $this->addSql('COMMENT ON COLUMN group_member.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN group_member.last_read_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE group_message (id SERIAL NOT NULL, group_id INT NOT NULL, sender_id INT NOT NULL, reply_to_id INT DEFAULT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, message_type VARCHAR(50) DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, file_name VARCHAR(100) DEFAULT NULL, file_size INT DEFAULT NULL, file_type VARCHAR(20) DEFAULT NULL, is_pinned BOOLEAN NOT NULL, is_edited BOOLEAN NOT NULL, is_system_message BOOLEAN NOT NULL, reactions JSON DEFAULT NULL, edit_history JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_30BD6473FFDF7169 ON group_message (reply_to_id)');
        $this->addSql('CREATE INDEX idx_group_message_group ON group_message (group_id)');
        $this->addSql('CREATE INDEX idx_group_message_sender ON group_message (sender_id)');
        $this->addSql('CREATE INDEX idx_group_message_created_at ON group_message (created_at)');
        $this->addSql('COMMENT ON COLUMN group_message.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN group_message.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN group_message.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_block (id SERIAL NOT NULL, blocker_id INT NOT NULL, blocked_id INT NOT NULL, blocked_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, reason TEXT DEFAULT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_61D96C7A548D5975 ON user_block (blocker_id)');
        $this->addSql('CREATE INDEX IDX_61D96C7A21FF5136 ON user_block (blocked_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BLOCKER_BLOCKED ON user_block (blocker_id, blocked_id)');
        $this->addSql('COMMENT ON COLUMN user_block.blocked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE "group" ADD CONSTRAINT FK_6DC044C57E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_member ADD CONSTRAINT FK_A36222A8FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_member ADD CONSTRAINT FK_A36222A8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_message ADD CONSTRAINT FK_30BD6473FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_message ADD CONSTRAINT FK_30BD6473F624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_message ADD CONSTRAINT FK_30BD6473FFDF7169 FOREIGN KEY (reply_to_id) REFERENCES group_message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_block ADD CONSTRAINT FK_61D96C7A548D5975 FOREIGN KEY (blocker_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_block ADD CONSTRAINT FK_61D96C7A21FF5136 FOREIGN KEY (blocked_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ALTER is_online DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "group" DROP CONSTRAINT FK_6DC044C57E3C61F9');
        $this->addSql('ALTER TABLE group_member DROP CONSTRAINT FK_A36222A8FE54D947');
        $this->addSql('ALTER TABLE group_member DROP CONSTRAINT FK_A36222A8A76ED395');
        $this->addSql('ALTER TABLE group_message DROP CONSTRAINT FK_30BD6473FE54D947');
        $this->addSql('ALTER TABLE group_message DROP CONSTRAINT FK_30BD6473F624B39D');
        $this->addSql('ALTER TABLE group_message DROP CONSTRAINT FK_30BD6473FFDF7169');
        $this->addSql('ALTER TABLE user_block DROP CONSTRAINT FK_61D96C7A548D5975');
        $this->addSql('ALTER TABLE user_block DROP CONSTRAINT FK_61D96C7A21FF5136');
        $this->addSql('DROP TABLE "group"');
        $this->addSql('DROP TABLE group_member');
        $this->addSql('DROP TABLE group_message');
        $this->addSql('DROP TABLE user_block');
        $this->addSql('ALTER TABLE "user" ALTER is_online SET DEFAULT false');
    }
}
