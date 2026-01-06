# 🎥 Video Learning System - Comprehensive Implementation Plan

**Project Goal:** Integrate a full-featured, self-hosted video learning system into the Symfony school management app with zero external cloud dependencies.

**Status:** Phase 1 - Foundation & Architecture  
**Last Updated:** January 6, 2026

---

## 📋 System Overview

### Architecture Stack
- **Backend:** Symfony 7.4 + Doctrine ORM
- **Video Storage:** MinIO (S3-compatible, self-hosted)
- **Video Processing:** FFmpeg (transcoding, thumbnail generation)
- **Frontend:** Video.js (HTML5 player) + JavaScript
- **Streaming:** WebRTC (peer-to-peer live streaming)
- **Transcription:** OpenAI Whisper (optional, local-installable)
- **Database:** SQLite/MySQL/PostgreSQL (existing)
- **Job Queue:** Symfony Messenger (async video processing)

### Tech Stack Rationale
✅ 100% free and open-source
✅ Self-hosted on VPS or local server
✅ No external dependencies or billing
✅ Compatible with existing Symfony setup
✅ Production-ready and scalable

---

## 🗄️ Database Schema

### Core Entities

#### 1. `Video` (Video Metadata)
```php
class Video {
    - id: UUID
    - course: Course (ManyToOne)
    - title: string
    - description: text
    - type: enum (ON_DEMAND, LIVE, RECORDING)
    - status: enum (DRAFT, PROCESSING, READY, ARCHIVED)
    - videoUrl: string (MinIO path)
    - thumbnailUrl: string
    - duration: int (seconds)
    - uploadedBy: User (teacher)
    - createdAt: datetime
    - updatedAt: datetime
}
```

#### 2. `VideoVariant` (Multi-resolution versions)
```php
class VideoVariant {
    - id: UUID
    - video: Video (OneToMany)
    - resolution: string (360p, 720p, 1080p)
    - bitrate: string
    - fileSize: int
    - minioPath: string
    - status: enum (PENDING, READY, FAILED)
    - createdAt: datetime
}
```

#### 3. `VideoChapter` (Interactive timestamps)
```php
class VideoChapter {
    - id: UUID
    - video: Video (OneToMany)
    - title: string
    - startTime: int (seconds)
    - endTime: int (seconds)
    - description: text
    - order: int
}
```

#### 4. `VideoTranscript` (Auto-generated transcripts)
```php
class VideoTranscript {
    - id: UUID
    - video: Video (OneToOne)
    - rawTranscript: text
    - segments: JSON (timestamp-indexed segments)
    - language: string
    - generatedAt: datetime
    - status: enum (PENDING, READY, FAILED)
}
```

#### 5. `VideoQuiz` (Embedded quizzes)
```php
class VideoQuiz {
    - id: UUID
    - video: Video (OneToMany)
    - question: string
    - type: enum (MULTIPLE_CHOICE, TRUE_FALSE, SHORT_ANSWER)
    - timestamp: int (seconds where to pause)
    - options: JSON (for MCQ)
    - correctAnswer: string
    - explanation: text
    - order: int
}
```

#### 6. `VideoNote` (Student notes)
```php
class VideoNote {
    - id: UUID
    - video: Video (ManyToOne)
    - student: User (ManyToOne)
    - content: text
    - timestamp: int (seconds)
    - createdAt: datetime
    - updatedAt: datetime
}
```

#### 7. `VideoProgress` (Watch tracking)
```php
class VideoProgress {
    - id: UUID
    - video: Video (ManyToOne)
    - student: User (ManyToOne)
    - lastWatchedAt: int (seconds)
    - totalWatched: int (seconds)
    - percentageWatched: decimal
    - completed: bool
    - completedAt: datetime
    - resumableAt: int (resume from here)
    - updatedAt: datetime
}
```

#### 8. `LiveSession` (Live streaming)
```php
class LiveSession {
    - id: UUID
    - course: Course (ManyToOne)
    - title: string
    - teacher: User (ManyToOne)
    - status: enum (SCHEDULED, LIVE, ENDED, ARCHIVED)
    - scheduledAt: datetime
    - startedAt: datetime
    - endedAt: datetime
    - webrtcRoom: string (unique room ID)
    - recordingUrl: string (MinIO path)
    - attendees: int
}
```

#### 9. `LiveAttendance` (Participation tracking)
```php
class LiveAttendance {
    - id: UUID
    - session: LiveSession (ManyToOne)
    - student: User (ManyToOne)
    - joinedAt: datetime
    - leftAt: datetime
    - durationMinutes: int
    - participationScore: int (1-100)
}
```

#### 10. `LiveChatMessage` (Real-time chat)
```php
class LiveChatMessage {
    - id: UUID
    - session: LiveSession (ManyToOne)
    - sender: User (ManyToOne)
    - message: text
    - sentAt: datetime
    - isAnswer: bool (teacher marking as answer)
}
```

---

## 🏗️ Project Structure

```
src/
├── Entity/
│   ├── Video.php
│   ├── VideoVariant.php
│   ├── VideoChapter.php
│   ├── VideoTranscript.php
│   ├── VideoQuiz.php
│   ├── VideoNote.php
│   ├── VideoProgress.php
│   ├── LiveSession.php
│   ├── LiveAttendance.php
│   └── LiveChatMessage.php
│
├── Service/
│   ├── Video/
│   │   ├── VideoUploadService.php
│   │   ├── VideoProcessingService.php
│   │   ├── VideoTranscodingService.php
│   │   ├── VideoProgressService.php
│   │   ├── TranscriptService.php
│   │   └── QuizService.php
│   ├── Storage/
│   │   ├── MinIOService.php
│   │   └── StorageManager.php
│   └── Live/
│       ├── LiveSessionService.php
│       ├── WebRTCSignalingService.php
│       ├── LiveAttendanceService.php
│       └── LiveChatService.php
│
├── Controller/
│   ├── VideoController.php
│   ├── VideoUploadController.php
│   ├── VideoPlayerController.php
│   ├── LiveStreamingController.php
│   └── Api/
│       ├── VideoApiController.php
│       ├── ProgressApiController.php
│       └── LiveApiController.php
│
├── Repository/
│   ├── VideoRepository.php
│   ├── VideoProgressRepository.php
│   ├── LiveSessionRepository.php
│   └── LiveChatMessageRepository.php
│
├── Messenger/
│   └── Message/
│       ├── TranscodeVideoMessage.php
│       ├── GenerateTranscriptMessage.php
│       ├── ProcessVideoMessage.php
│       └── RecordLiveSessionMessage.php
│
├── EventListener/
│   └── VideoEventListener.php
│
└── Validator/
    └── VideoUploadValidator.php

config/
├── packages/
│   ├── minio.yaml
│   └── video_system.yaml
└── services.yaml

migrations/
└── Version*.php (auto-generated)

templates/
├── video/
│   ├── list.html.twig
│   ├── player.html.twig
│   ├── upload.html.twig
│   ├── chapters.html.twig
│   ├── transcript.html.twig
│   ├── notes.html.twig
│   └── quiz.html.twig
├── live/
│   ├── list.html.twig
│   ├── viewer.html.twig
│   ├── schedule.html.twig
│   └── chat.html.twig
└── components/
    ├── video-player.html.twig
    ├── progress-bar.html.twig
    ├── chapter-list.html.twig
    └── quiz-modal.html.twig

assets/
├── controllers/
│   ├── video_player_controller.js
│   ├── video_upload_controller.js
│   ├── live_stream_controller.js
│   └── quiz_controller.js
└── styles/
    ├── video-player.css
    ├── live-stream.css
    └── progress-bar.css

public/
└── uploads/ (symlink to MinIO)

var/
├── videos/ (temporary processing)
└── cache/
    └── transcripts/
```

---

## 🔄 Implementation Phases

### Phase 1: Foundation & Core Infrastructure (Weeks 1-2)
- [x] Create database entities (Video, VideoVariant, VideoProgress)
- [ ] Set up MinIO service and configuration
- [ ] Implement storage manager
- [ ] Create video upload validator
- [ ] Set up Symfony Messenger for async processing
- [ ] Create database migrations

**Deliverable:** Basic video entity structure + storage integration

### Phase 2: Video Upload & Processing (Weeks 3-4)
- [ ] Implement video upload controller
- [ ] Create FFmpeg transcoding service
- [ ] Build multi-resolution processing pipeline
- [ ] Generate thumbnails automatically
- [ ] Add progress notifications
- [ ] Error handling and retry logic

**Deliverable:** Teachers can upload videos → auto-transcoded to 3-4 resolutions

### Phase 3: Video Player & Frontend (Weeks 5-6)
- [ ] Integrate Video.js player
- [ ] Implement quality selection
- [ ] Add playback speed control
- [ ] Picture-in-Picture mode
- [ ] Resume playback functionality
- [ ] Responsive design

**Deliverable:** Production-ready video player with all playback features

### Phase 4: Progress & Engagement (Weeks 7-8)
- [ ] VideoProgress tracking
- [ ] Resume-from feature
- [ ] Progress API endpoints
- [ ] Chapter/timestamp navigation
- [ ] Note-taking feature
- [ ] Exportable notes (PDF/Markdown)

**Deliverable:** Full progress tracking + student engagement tools

### Phase 5: Interactive Learning (Weeks 9-10)
- [ ] VideoQuiz/VideoChapter system
- [ ] Embedded quiz modal
- [ ] Quiz scoring and analytics
- [ ] Chapter navigation
- [ ] Searchable transcripts
- [ ] Transcript generation (FFprobe basic version)

**Deliverable:** Interactive learning features + comprehension tracking

### Phase 6: Live Streaming (Weeks 11-12)
- [ ] WebRTC signaling server setup
- [ ] LiveSession entities
- [ ] Attendance tracking
- [ ] Live chat functionality
- [ ] Auto-recording to MinIO
- [ ] Replay from recording

**Deliverable:** Full live streaming capability with recording

### Phase 7: Advanced Features (Weeks 13-14)
- [ ] Whisper integration (optional)
- [ ] Full-text search on transcripts
- [ ] Peer-to-peer note sharing
- [ ] Advanced analytics dashboard
- [ ] Video recommendations
- [ ] Gamification (badges, points)

**Deliverable:** Advanced analytics and learning insights

### Phase 8: Documentation & Deployment (Week 15)
- [ ] Complete API documentation
- [ ] Docker setup for MinIO
- [ ] Deployment guide
- [ ] Performance optimization guide
- [ ] Troubleshooting guide

**Deliverable:** Production-ready deployment documentation

---

## 🛠️ Dependencies to Add

### Composer Packages
```bash
composer require aws/aws-sdk-php           # MinIO SDK
composer require symfony/messenger          # Already installed
composer require symfony/process            # FFmpeg integration
composer require guzzlehttp/guzzle          # API calls
composer require ramsey/uuid-doctrine       # UUID support
```

### System Dependencies
- **FFmpeg** - Video transcoding
- **MinIO** - S3-compatible object storage
- **OpenAI Whisper** (optional) - Transcription
- **NodeJS** (for Stimulus) - Already installed

### Npm Packages
```bash
npm install video.js              # Video player
npm install hls.js               # HLS streaming
npm install simple-peer          # WebRTC peer connections
npm install socket.io            # Real-time communication
npm install socket.io-client
npm install wavesurfer.js        # Audio visualization
```

---

## 🔐 Configuration Files

### `.env` Variables
```dotenv
MINIO_ENDPOINT=http://localhost:9000
MINIO_ROOT_USER=minioadmin
MINIO_ROOT_PASSWORD=minioadmin
MINIO_REGION=us-east-1
MINIO_BUCKET_VIDEOS=school-videos
MINIO_BUCKET_THUMBNAILS=school-thumbnails

VIDEO_MAX_SIZE=5242880000          # 5GB
VIDEO_ALLOWED_FORMATS=mp4,mov,mkv
VIDEO_TRANSCODING_RESOLUTIONS=360,720,1080

FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe

WEBRTC_STUN_SERVERS=stun:stun.l.google.com:19302,stun:stun1.l.google.com:19302
WEBRTC_TURN_SERVERS=turn:turnserver.example.com

TRANSCRIPT_SERVICE=none # or whisper
WHISPER_MODEL=base      # tiny, base, small, medium, large
```

### Video System Configuration
```yaml
# config/packages/video_system.yaml
video_system:
    storage:
        driver: minio
        bucket: school-videos
        region: us-east-1
    
    transcoding:
        enabled: true
        resolutions:
            - 360p
            - 720p
            - 1080p
        bitrates:
            360p: 500k
            720p: 2500k
            1080p: 5000k
    
    player:
        autoplay: false
        controls: true
        preload: metadata
        responsive: true
    
    limits:
        max_file_size: 5242880000  # 5GB
        max_upload_duration: 3600  # 1 hour
        allowed_formats:
            - mp4
            - mov
            - mkv
```

---

## 📊 API Endpoints (REST + WebSocket)

### Video Management
```
GET    /api/videos                          # List videos
POST   /api/videos                          # Create video metadata
GET    /api/videos/{id}                     # Get video details
PUT    /api/videos/{id}                     # Update video
DELETE /api/videos/{id}                     # Delete video
POST   /api/videos/{id}/upload              # Start upload (resumable)
```

### Video Player
```
GET    /videos/{id}/play                    # View player page
GET    /api/videos/{id}/stream              # Get stream URL
PUT    /api/videos/{id}/progress            # Update watch progress
GET    /api/videos/{id}/progress            # Get current progress
```

### Chapters & Navigation
```
GET    /api/videos/{id}/chapters            # List chapters
POST   /api/videos/{id}/chapters            # Add chapter
PUT    /api/videos/{id}/chapters/{chapId}   # Update chapter
DELETE /api/videos/{id}/chapters/{chapId}   # Delete chapter
```

### Transcripts & Search
```
GET    /api/videos/{id}/transcript          # Get transcript
GET    /api/videos/{id}/transcript/search   # Search transcript
POST   /api/videos/{id}/transcript/generate # Force regenerate
```

### Quizzes
```
GET    /api/videos/{id}/quizzes             # List quizzes
POST   /api/videos/{id}/quizzes             # Create quiz
PUT    /api/quizzes/{quizId}                # Update quiz
DELETE /api/quizzes/{quizId}                # Delete quiz
POST   /api/quizzes/{quizId}/answer         # Submit answer
GET    /api/videos/{id}/quiz-results        # Get student results
```

### Notes
```
GET    /api/videos/{id}/notes               # List notes
POST   /api/videos/{id}/notes               # Create note
PUT    /api/notes/{noteId}                  # Update note
DELETE /api/notes/{noteId}                  # Delete note
GET    /api/notes/{noteId}/export           # Export as PDF/Markdown
```

### Live Streaming
```
GET    /api/live-sessions                   # List live sessions
POST   /api/live-sessions                   # Schedule live session
GET    /api/live-sessions/{id}              # Get session details
PUT    /api/live-sessions/{id}              # Update session
POST   /api/live-sessions/{id}/start        # Start streaming
POST   /api/live-sessions/{id}/end          # End streaming
GET    /api/live-sessions/{id}/attendees    # List attendees
```

### WebSocket Events (Socket.io)
```
video:progress          # Student watching → server
quiz:answer            # Quiz submission
live:user-joined       # User joined live session
live:message           # Chat message
webrtc:offer           # WebRTC signaling
webrtc:answer
webrtc:candidate
```

---

## 🚀 Quick Start (After Implementation)

```bash
# 1. Install dependencies
composer install
npm install

# 2. Setup environment
cp .env.example .env
# Edit .env with MinIO credentials

# 3. Start MinIO (Docker)
docker run -d \
  -p 9000:9000 \
  -p 9001:9001 \
  -e MINIO_ROOT_USER=minioadmin \
  -e MINIO_ROOT_PASSWORD=minioadmin \
  minio/minio server /data --console-address ":9001"

# 4. Create database
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate

# 5. Start servers
symfony server:start
npm run watch

# 6. Access
# App: http://localhost:8000
# MinIO: http://localhost:9001
```

---

## 📈 Performance Targets

- Video upload: 100MB+ (resumable)
- Transcoding: 30-60 min for 1080p (async)
- Stream start time: <2s
- Live streaming latency: <500ms
- Database queries: <100ms
- Storage efficiency: 60-70% with multi-resolution

---

## 🔗 Key Resources

- [Video.js Docs](https://videojs.com/getting-started/)
- [FFmpeg Guide](https://ffmpeg.org/documentation.html)
- [MinIO Quickstart](https://min.io/docs/minio/container/index.html)
- [WebRTC Signaling](https://developer.mozilla.org/en-US/docs/Web/API/WebRTC_API)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Socket.io Documentation](https://socket.io/docs/)

---

## ✅ Success Criteria

1. ✅ 100% self-hosted, zero cloud dependencies
2. ✅ Multi-resolution video streaming
3. ✅ Real-time progress tracking
4. ✅ Live streaming capability
5. ✅ Student engagement features (notes, quizzes, transcripts)
6. ✅ Production-ready performance
7. ✅ Comprehensive API documentation
8. ✅ Easy deployment guide

---

**Next Step:** Begin Phase 1 - Create database entities and MinIO integration
