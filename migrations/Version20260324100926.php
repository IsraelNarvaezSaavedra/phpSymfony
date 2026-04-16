<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324100926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE valoracion (id INT AUTO_INCREMENT NOT NULL, valor INT NOT NULL, usuario_id INT NOT NULL, coche_id INT NOT NULL, INDEX IDX_6D3DE0F4DB38439E (usuario_id), INDEX IDX_6D3DE0F4F4621E56 (coche_id), UNIQUE INDEX un_voto_por_usuario (usuario_id, coche_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE valoracion ADD CONSTRAINT FK_6D3DE0F4DB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE valoracion ADD CONSTRAINT FK_6D3DE0F4F4621E56 FOREIGN KEY (coche_id) REFERENCES coche (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE valoracion DROP FOREIGN KEY FK_6D3DE0F4DB38439E');
        $this->addSql('ALTER TABLE valoracion DROP FOREIGN KEY FK_6D3DE0F4F4621E56');
        $this->addSql('DROP TABLE valoracion');
    }
}
