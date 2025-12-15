<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251215134433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(30) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, location VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, client_id INT NOT NULL, photographer_id INT NOT NULL, INDEX IDX_E00CEDDE19EB6921 (client_id), INDEX IDX_E00CEDDE53EC1A21 (photographer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE booking_attachment (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, booking_id INT NOT NULL, album_id INT DEFAULT NULL, photo_id INT DEFAULT NULL, INDEX IDX_E0C5BACA3301C60 (booking_id), INDEX IDX_E0C5BACA1137ABCF (album_id), INDEX IDX_E0C5BACA7E9E4C8C (photo_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE19EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE53EC1A21 FOREIGN KEY (photographer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE booking_attachment ADD CONSTRAINT FK_E0C5BACA3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id)');
        $this->addSql('ALTER TABLE booking_attachment ADD CONSTRAINT FK_E0C5BACA1137ABCF FOREIGN KEY (album_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE booking_attachment ADD CONSTRAINT FK_E0C5BACA7E9E4C8C FOREIGN KEY (photo_id) REFERENCES photo (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE19EB6921');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE53EC1A21');
        $this->addSql('ALTER TABLE booking_attachment DROP FOREIGN KEY FK_E0C5BACA3301C60');
        $this->addSql('ALTER TABLE booking_attachment DROP FOREIGN KEY FK_E0C5BACA1137ABCF');
        $this->addSql('ALTER TABLE booking_attachment DROP FOREIGN KEY FK_E0C5BACA7E9E4C8C');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE booking_attachment');
    }
}
