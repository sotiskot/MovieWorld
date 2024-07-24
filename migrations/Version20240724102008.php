<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240724102008 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE movie_reactions (
            id INT AUTO_INCREMENT NOT NULL,
            movie_id INT NOT NULL,
            user_id INT NOT NULL,
            type ENUM(\'like\', \'dislike\') NOT NULL,
            INDEX IDX_MOVIE_ID (movie_id),
            INDEX IDX_USER_ID (user_id),
            PRIMARY KEY(id),
            FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE movie_reactions');
    }
}
