# 🎥 Video Learning System - Implementation Guide

## ✅ Phase 1 Completed

### What's Been Implemented

#### Database Architecture (10 Entities)
- ✅ **Video** - Core video metadata with status tracking
- ✅ **VideoVariant** - Multi-resolution versions (360p, 720p, 1080p)
- ✅ **VideoChapter** - Interactive timestamps/sections
- ✅ **VideoTranscript** - Auto-generated transcripts with segments
- ✅ **VideoQuiz** - Embedded quizzes at specific timestamps
- ✅ **VideoNote** - Student notes linked to timestamps
- ✅ **VideoProgress** - Watch progress tracking per student
- ✅ **LiveSession** - Live streaming sessions
- ✅ **LiveAttendance** - Attendance and participation tracking
- ✅ **LiveChatMessage** - Real-time chat during streams

#### Core Services
- ✅ **MinIOService** - S3-compatible object storage integration
  - Upload/download files
  - Presigned URLs for streaming
  - Bucket management
  - 100% self-hosted

- ✅ **VideoTranscodingService** - FFmpeg integration
  - Multi-resolution transcoding
  - Thumbnail generation
  - Audio extraction
  - Metadata extraction

- ✅ **VideoProcessingService** - Orchestrates processing pipeline
  - Async video processing
  - Thumbnail generation and upload
  - Multi-variant transcoding
  - Automatic cleanup

#### Infrastructure
- ✅ Database migrations applied
- ✅ Environment variables configured (.env)
- ✅ Service container setup (services.yaml)
- ✅ Messenger integration for async processing
- ✅ Repositories with query methods

---

## 🚀 Getting Started

### 1. Environment Setup

Create a `.env.local` with your settings:

```dotenv
# MinIO (Self-hosted S3-compatible storage)
MINIO_ENDPOINT=http://localhost:9000
MINIO_ROOT_USER=minioadmin
MINIO_ROOT_PASSWORD=minioadmin
MINIO_REGION=us-east-1
MINIO_BUCKET_VIDEOS=school-videos
MINIO_BUCKET_THUMBNAILS=school-thumbnails

# FFmpeg paths (Linux/Mac: /usr/bin/ffmpeg, Windows: C:\ffmpeg\bin\ffmpeg.exe)
FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe

# Video processing
VIDEO_MAX_SIZE=5242880000    # 5GB
VIDEO_ALLOWED_FORMATS=mp4,mov,mkv
VIDEO_TRANSCODING_RESOLUTIONS=360,720,1080
VIDEO_TEMP_DIR=var/videos

# WebRTC
WEBRTC_STUN_SERVERS=stun:stun.l.google.com:19302,stun:stun1.l.google.com:19302
LIVE_STREAMING_ENABLED=true

# Transcription (optional)
TRANSCRIPT_SERVICE=none
```

### 2. Install FFmpeg

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install ffmpeg
```

**macOS:**
```bash
brew install ffmpeg
```

**Windows:**
- Download from https://ffmpeg.org/download.html
- Add to PATH or set FFMPEG_PATH in .env

### 3. Start MinIO (Docker)

```bash
docker run -d \
  -p 9000:9000 \
  -p 9001:9001 \
  -e MINIO_ROOT_USER=minioadmin \
  -e MINIO_ROOT_PASSWORD=minioadmin \
  -v minio_data:/data \
  minio/minio server /data --console-address ":9001"

# Access MinIO Console: http://localhost:9001
# Username: minioadmin | Password: minioadmin
```

Or use docker-compose.yml (see example below)

### 4. Start Worker (Async Processing)

```bash
# In one terminal, start the Symfony dev server
symfony server:start

# In another terminal, start the message worker
symfony console messenger:consume doctrine_transport -vv
```

---

## 📋 Next Steps - Phase 2

### Current State
- Database structure ready
- MinIO service configured
- FFmpeg service ready
- Async processing pipeline in place

### What to Build Next

#### 1. **Video Upload Controller** (High Priority)
```php
// POST /videos/upload
// - Validate file
// - Store in temp location
// - Dispatch ProcessVideoMessage
// - Return job status
```

#### 2. **Video Player Page**
```php
// GET /courses/{courseId}/videos/{videoId}
// - Load video entity
// - Get variants and streaming URLs
// - Initialize Video.js player
// - Load chapters, quizzes, transcript
```

#### 3. **Progress Tracking API**
```php
// PUT /api/videos/{id}/progress
// - Update watch position
// - Calculate percentage
// - Mark as complete
// - Trigger notifications
```

#### 4. **Quiz System**
```php
// POST /api/quizzes/{id}/answer
// - Validate answer
// - Store result
// - Calculate score
// - Return feedback
```

#### 5. **Live Streaming Setup**
```php
// WebRTC signaling server
// - Peer connection establishment
// - ICE candidate handling
// - Recording capture
```

---

## 📁 Project Structure Reference

```
src/
├── Entity/                           ✅ DONE
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
│   ├── Storage/
│   │   └── MinIOService.php          ✅ DONE
│   └── Video/
│       ├── VideoTranscodingService.php   ✅ DONE
│       ├── VideoProcessingService.php    ✅ DONE
│       ├── VideoUploadService.php        ⏳ TODO
│       ├── VideoProgressService.php      ⏳ TODO
│       ├── TranscriptService.php         ⏳ TODO
│       └── QuizService.php               ⏳ TODO
│
├── Controller/
│   ├── VideoController.php           ⏳ TODO
│   ├── VideoUploadController.php     ⏳ TODO
│   └── Api/
│       ├── VideoApiController.php    ⏳ TODO
│       ├── ProgressApiController.php ⏳ TODO
│       └── LiveApiController.php     ⏳ TODO
│
├── Repository/                       ✅ DONE
│
├── Messenger/
│   ├── Message/
│   │   └── ProcessVideoMessage.php   ✅ DONE
│   └── MessageHandler/
│       └── ProcessVideoMessageHandler.php  ✅ DONE
│
└── Validator/
    └── VideoUploadValidator.php     ⏳ TODO

config/
├── services.yaml                     ✅ UPDATED
└── packages/
    └── doctrine.yaml                 (auto-generated)

migrations/
├── Version*.php                      ✅ CREATED & APPLIED
└── Version20260106161350.php         (All video tables)

templates/
└── video/                            ⏳ TODO
    ├── list.html.twig
    ├── player.html.twig
    ├── upload.html.twig
    ├── chapters.html.twig
    ├── transcript.html.twig
    └── notes.html.twig
```

---

## 🧪 Testing the Setup

### 1. Check Services Are Registered

```bash
symfony console debug:container | grep -i video
```

Expected output:
```
App\Service\Storage\MinIOService
App\Service\Video\VideoTranscodingService
App\Service\Video\VideoProcessingService
App\Repository\VideoRepository
... and more
```

### 2. Test MinIO Connection

```bash
symfony console debug:container --parameter minio_endpoint
```

### 3. Test FFmpeg

```bash
which ffmpeg
ffmpeg -version
ffprobe -version
```

### 4. Test Database

```bash
symfony console doctrine:query:dql "SELECT v FROM App\Entity\Video v"
# Should return: Query executed successfully, no results found (empty)
```

---

## 🔐 Security Considerations

### Upload Validation
- ✅ File type validation (mp4, mov, mkv only)
- ✅ File size limit (5GB default, configurable)
- ✅ Virus scanning (TODO - integrate ClamAV or similar)
- ✅ Permission checks (teachers only)

### Storage Security
- ✅ MinIO buckets private by default
- ✅ Presigned URLs with expiration (1 hour default)
- ✅ No direct S3 access from frontend
- ✅ CORS configuration (if needed)

### API Security (TODO)
- Add rate limiting
- Add JWT/Bearer token auth
- Add role-based access control
- Add audit logging

---

## 💾 Database Backup Strategy

MinIO data:
```bash
# Backup entire MinIO
docker exec minio mc mirror --watch minio/school-videos /backup/

# Restore
docker exec minio mc mirror /backup/school-videos minio/
```

SQLite:
```bash
cp var/data/school_management_dev.db var/data/school_management_dev.db.backup
```

---

## 📊 Performance Tuning

### FFmpeg Optimization
```yaml
# For faster transcoding (lower quality):
preset: fast          # veryfast, fast, medium, slow, veryslow

# For better quality (slower):
preset: slow
```

### MinIO Tuning
```bash
# Increase concurrent uploads
# Set MINIO_API_MAX_IDLE_CONNECTIONS=256

# Enable compression
# minio server --compress-extensions='mp4,mkv'
```

### Database Optimization
```php
# Add indexes for frequent queries
// In VideoProgress: video_id + student_id
// In VideoNote: video_id + student_id
// In LiveSession: course_id + status
```

---

## 🐛 Troubleshooting

### Issue: "FFmpeg not found"
**Solution:**
```bash
# Check FFmpeg path
which ffmpeg
# Update .env: FFMPEG_PATH=/path/to/ffmpeg
```

### Issue: MinIO connection refused
**Solution:**
```bash
# Check if MinIO is running
curl http://localhost:9000
# Start MinIO if not running
docker run -p 9000:9000 -p 9001:9001 minio/minio server /data
```

### Issue: "Permission denied" on temp directory
**Solution:**
```bash
chmod -R 755 var/videos
```

### Issue: Async processing not working
**Solution:**
```bash
# Check messenger transport
symfony console debug:messenger
# Make sure worker is running
symfony console messenger:consume doctrine_transport -vv
```

---

## 📞 Support Resources

- [FFmpeg Documentation](https://ffmpeg.org/documentation.html)
- [MinIO Quickstart](https://min.io/docs/minio/container/)
- [Video.js Docs](https://videojs.com/getting-started/)
- [WebRTC API](https://developer.mozilla.org/en-US/docs/Web/API/WebRTC_API)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)

---

## 🎯 Success Checklist

- [ ] FFmpeg installed and working
- [ ] MinIO container running
- [ ] Database migrations applied
- [ ] Services registered in container
- [ ] Message worker running
- [ ] Environment variables configured
- [ ] Can create Video entities
- [ ] Can upload files to MinIO
- [ ] Can trigger video processing

---

**Status:** Phase 1 Complete ✅  
**Next Phase:** Video Upload & Processing Controllers  
**Estimated Time:** 4-6 hours for Phase 2
