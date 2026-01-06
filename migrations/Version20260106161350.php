<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106161350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE live_attendance (id CHAR(36) NOT NULL, joined_at DATETIME NOT NULL, left_at DATETIME DEFAULT NULL, duration_minutes INTEGER DEFAULT NULL, participation_score INTEGER NOT NULL, session_id CHAR(36) NOT NULL, student_id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_3D5C6BCD613FECDF FOREIGN KEY (session_id) REFERENCES live_session (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_3D5C6BCDCB944F1A FOREIGN KEY (student_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_3D5C6BCD613FECDF ON live_attendance (session_id)');
        $this->addSql('CREATE INDEX IDX_3D5C6BCDCB944F1A ON live_attendance (student_id)');
        $this->addSql('CREATE TABLE live_chat_message (id CHAR(36) NOT NULL, message CLOB NOT NULL, sent_at DATETIME NOT NULL, is_answer BOOLEAN NOT NULL, session_id CHAR(36) NOT NULL, sender_id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_486C7A9C613FECDF FOREIGN KEY (session_id) REFERENCES live_session (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_486C7A9CF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_486C7A9C613FECDF ON live_chat_message (session_id)');
        $this->addSql('CREATE INDEX IDX_486C7A9CF624B39D ON live_chat_message (sender_id)');
        $this->addSql('CREATE TABLE live_session (id CHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, status VARCHAR(50) NOT NULL, scheduled_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, ended_at DATETIME DEFAULT NULL, webrtc_room VARCHAR(255) NOT NULL, recording_url VARCHAR(255) DEFAULT NULL, attendees INTEGER NOT NULL, created_at DATETIME NOT NULL, course_id INTEGER NOT NULL, teacher_id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_519995AF591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_519995AF41807E1D FOREIGN KEY (teacher_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_519995AF686F5936 ON live_session (webrtc_room)');
        $this->addSql('CREATE INDEX IDX_519995AF591CC992 ON live_session (course_id)');
        $this->addSql('CREATE INDEX IDX_519995AF41807E1D ON live_session (teacher_id)');
        $this->addSql('CREATE TABLE video (id CHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, video_url VARCHAR(255) DEFAULT NULL, thumbnail_url VARCHAR(255) DEFAULT NULL, duration INTEGER DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, course_id INTEGER NOT NULL, uploaded_by_id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_7CC7DA2C591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_7CC7DA2CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_7CC7DA2C591CC992 ON video (course_id)');
        $this->addSql('CREATE INDEX IDX_7CC7DA2CA2B28FE8 ON video (uploaded_by_id)');
        $this->addSql('CREATE TABLE video_chapter (id CHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, start_time INTEGER NOT NULL, end_time INTEGER DEFAULT NULL, description CLOB DEFAULT NULL, order_index INTEGER NOT NULL, video_id CHAR(36) NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_1C6C5C8329C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_1C6C5C8329C1004E ON video_chapter (video_id)');
        $this->addSql('CREATE TABLE video_note (id CHAR(36) NOT NULL, content CLOB NOT NULL, timestamp INTEGER NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, video_id CHAR(36) NOT NULL, student_id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_C82AA7C829C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_C82AA7C8CB944F1A FOREIGN KEY (student_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C82AA7C829C1004E ON video_note (video_id)');
        $this->addSql('CREATE INDEX IDX_C82AA7C8CB944F1A ON video_note (student_id)');
        $this->addSql('CREATE TABLE video_progress (id CHAR(36) NOT NULL, last_watched_at INTEGER NOT NULL, total_watched INTEGER NOT NULL, percentage_watched NUMERIC(5, 2) NOT NULL, completed BOOLEAN NOT NULL, completed_at DATETIME DEFAULT NULL, resumable_at INTEGER NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, video_id CHAR(36) NOT NULL, student_id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_8A83C0FA29C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8A83C0FACB944F1A FOREIGN KEY (student_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8A83C0FA29C1004E ON video_progress (video_id)');
        $this->addSql('CREATE INDEX IDX_8A83C0FACB944F1A ON video_progress (student_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_video_student ON video_progress (video_id, student_id)');
        $this->addSql('CREATE TABLE video_quiz (id CHAR(36) NOT NULL, question VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, timestamp INTEGER NOT NULL, options CLOB DEFAULT NULL, correct_answer VARCHAR(255) NOT NULL, explanation CLOB DEFAULT NULL, order_index INTEGER NOT NULL, created_at DATETIME NOT NULL, video_id CHAR(36) NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_A385A74E29C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A385A74E29C1004E ON video_quiz (video_id)');
        $this->addSql('CREATE TABLE video_transcript (id CHAR(36) NOT NULL, raw_transcript CLOB NOT NULL, segments CLOB NOT NULL, language VARCHAR(10) NOT NULL, status VARCHAR(50) NOT NULL, generated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, video_id CHAR(36) NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_28510F7529C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_28510F7529C1004E ON video_transcript (video_id)');
        $this->addSql('CREATE TABLE video_variant (id CHAR(36) NOT NULL, resolution VARCHAR(50) NOT NULL, bitrate VARCHAR(50) NOT NULL, file_size BIGINT DEFAULT NULL, minio_path VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, video_id CHAR(36) NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_14AE560029C1004E FOREIGN KEY (video_id) REFERENCES video (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_14AE560029C1004E ON video_variant (video_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE live_attendance');
        $this->addSql('DROP TABLE live_chat_message');
        $this->addSql('DROP TABLE live_session');
        $this->addSql('DROP TABLE video');
        $this->addSql('DROP TABLE video_chapter');
        $this->addSql('DROP TABLE video_note');
        $this->addSql('DROP TABLE video_progress');
        $this->addSql('DROP TABLE video_quiz');
        $this->addSql('DROP TABLE video_transcript');
        $this->addSql('DROP TABLE video_variant');
    }
}
