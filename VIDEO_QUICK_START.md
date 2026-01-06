# 🎬 Video Learning System - Quick Start Guide

**Phase 2 Complete** ✅ | 22 API Endpoints | 3 Frontend Templates | Ready to Use

---

## 🚀 Quick Start

### 1. Start the Application

```bash
# Terminal 1: Start Symfony server
cd SchoolManagement-app
symfony server:start

# Terminal 2: Start Messenger worker (processes video transcoding)
symfony console messenger:consume doctrine_transport -vv

# Terminal 3 (Optional): Start MinIO server
docker-compose -f docker-compose.video.yml up -d
```

### 2. Access the System

**Upload Videos:**
```
http://localhost:8000/videos/upload?course_id=YOUR_COURSE_ID
```

**Browse Course Videos:**
```
http://localhost:8000/videos/course?course_id=YOUR_COURSE_ID
```

**Watch Video:**
```
http://localhost:8000/videos/player?id=YOUR_VIDEO_ID
```

---

## 📋 API Endpoints Reference

### Video Upload & Management (6 endpoints)
```
POST   /api/videos/upload                    Upload video file
GET    /api/videos/course/{courseId}         List videos by course
GET    /api/videos/{videoId}                 Get video details
DELETE /api/videos/{videoId}                 Delete video
GET    /api/videos/{videoId}/status          Get processing status
GET    /api/videos/search?q=query            Search videos
```

### Video Details (5 endpoints)
```
POST   /api/video-details/{videoId}/notes              Add note at timestamp
GET    /api/video-details/{videoId}/notes              Get all notes
PUT    /api/video-details/notes/{noteId}               Update note
DELETE /api/video-details/notes/{noteId}               Delete note
GET    /api/video-details/{videoId}/metadata           Get video metadata
```

### Progress Tracking (5 endpoints)
```
PUT    /api/video-progress/{videoId}                   Update watch progress
GET    /api/video-progress/{videoId}                   Get current progress
GET    /api/video-progress/completed-videos            List completed videos
GET    /api/video-progress/in-progress-videos          List in-progress videos
GET    /api/video-progress/course/{courseId}           Get course progress stats
```

### Course Videos (3 endpoints)
```
GET    /api/courses/{courseId}/videos                  Get ready videos
GET    /api/courses/{courseId}/videos/all              Get all videos (teacher only)
GET    /api/courses/{courseId}/summary                 Get course statistics
```

### Health & Status (3 endpoints)
```
GET    /api/health/check                              Basic health check
GET    /api/health/minio                              MinIO connectivity check
GET    /api/health/system                             Full system status
```

---

## 👨‍🏫 Teacher: Upload Videos

### Step-by-Step

**1. Navigate to Upload**
```
/videos/upload?course_id=COURSE_ID
```

**2. Fill Form**
- Title: "Introduction to PHP" (required)
- Description: "Learn PHP basics..." (optional)
- Video file: Select MP4/MOV/MKV (max 5GB)

**3. Upload**
- Click "Upload Video"
- File uploads to backend
- Progress bar shows upload status
- Video enters PROCESSING queue

**4. Processing (Automatic)**
- Messenger worker transcodes video
- Creates 360p, 720p, 1080p versions
- Generates thumbnail
- Uploads to MinIO storage
- Status changes to READY

**5. Time to Complete**
- ~30 min for 1-hour video (varies by file size)
- You'll see status updates in course videos page

---

## 👨‍🎓 Student: Watch Videos

### Step-by-Step

**1. Browse Videos**
```
/videos/course?course_id=COURSE_ID
```

**2. Select Quality**
- 360p (lower bandwidth, mobile-friendly)
- 720p (balanced quality/bandwidth)
- 1080p (best quality, higher bandwidth)

**3. Watch**
- Player auto-resumes from last watched position
- Progress bar shows overall progress
- Playback speed controls (0.5x to 2x)
- Picture-in-Picture (PiP) mode

**4. Take Notes**
- Type note in sidebar
- Click "Add"
- Note saved with timestamp
- Can jump to note timestamp later
- Notes stored in database

**5. Track Progress**
- Sidebar shows watch percentage
- Auto-marked complete at 95%
- Last watched time stored
- Can resume anytime

---

## 📊 API Usage Examples

### Upload Video (cURL)
```bash
curl -X POST http://localhost:8000/api/videos/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "video=@video.mp4" \
  -F "title=My Video" \
  -F "description=Video description" \
  -F "course_id=123"
```

### Get Course Videos
```bash
curl http://localhost:8000/api/courses/123/videos
```

### Update Watch Progress
```bash
curl -X PUT http://localhost:8000/api/video-progress/VIDEO_ID \
  -H "Content-Type: application/json" \
  -d '{
    "lastWatchedAt": 120.5,
    "totalWatched": 120.5,
    "percentageWatched": 25.5,
    "completed": false
  }'
```

### Add Note
```bash
curl -X POST http://localhost:8000/api/video-details/VIDEO_ID/notes \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Important concept here",
    "timestamp": 45.0
  }'
```

### Get Course Summary
```bash
curl http://localhost:8000/api/courses/123/summary
```

---

## 🔧 Configuration

### Environment Variables
Edit `.env`:
```dotenv
# MinIO Storage
MINIO_ENDPOINT=http://localhost:9000
MINIO_ROOT_USER=minioadmin
MINIO_ROOT_PASSWORD=minioadmin
MINIO_REGION=us-east-1
MINIO_BUCKET_VIDEOS=school-videos
MINIO_BUCKET_THUMBNAILS=school-thumbnails

# FFmpeg Processing
FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe

# Video Upload
VIDEO_MAX_SIZE=5242880000  # 5GB in bytes
VIDEO_ALLOWED_FORMATS=mp4,mov,mkv
VIDEO_TEMP_DIR=var/videos

# Transcoding Resolutions
VIDEO_TRANSCODING_RESOLUTIONS=360,720,1080
```

---

## 📁 File Structure

### Backend Controllers
```
src/Controller/
├── VideoUploadController.php       # Upload & management
├── VideoDetailsController.php      # Notes & metadata
├── VideoProgressController.php     # Progress tracking
├── CourseVideosController.php      # Course-level management
├── HealthCheckController.php       # System health checks
└── TestVideoSystemCommand.php      # Testing utility
```

### Frontend Templates
```
templates/video/
├── upload.html.twig                # Upload form
├── player.html.twig                # Video player page
└── course-videos.html.twig         # Browse videos
```

### Database Entities
```
src/Entity/
├── Video.php                       # Main video entity
├── VideoVariant.php                # 360p/720p/1080p versions
├── VideoChapter.php                # Chapter timestamps
├── VideoTranscript.php             # Auto-generated transcripts
├── VideoQuiz.php                   # Quiz questions
├── VideoNote.php                   # Student notes
├── VideoProgress.php               # Watch progress
├── LiveSession.php                 # Live streaming sessions
├── LiveAttendance.php              # Attendance tracking
└── LiveChatMessage.php             # Live chat messages
```

### Services
```
src/Service/Video/
├── VideoUploadService.php          # File upload handling
├── VideoUploadValidator.php        # File validation
├── VideoTranscodingService.php     # FFmpeg transcoding
├── VideoProcessingService.php      # Processing orchestration
└── Storage/MinIOService.php        # MinIO client

src/Messenger/
├── ProcessVideoMessage.php         # Queue message DTO
└── ProcessVideoMessageHandler.php  # Message consumer
```

---

## ✅ System Health Checks

### Check if Everything is Working
```bash
# 1. Basic health check
curl http://localhost:8000/api/health/check

# 2. MinIO connectivity
curl http://localhost:8000/api/health/minio

# 3. Full system status
curl http://localhost:8000/api/health/system
```

### Check Database
```bash
symfony console doctrine:schema:validate
```

### List All Routes
```bash
symfony console debug:router | grep api_
```

### Check Logs
```bash
tail -f var/log/dev.log
```

---

## 🐛 Troubleshooting

### Videos Not Uploading
**Problem:** Upload button disabled or file not accepted

**Solution:**
1. Check file format (must be MP4, MOV, or MKV)
2. Check file size (max 5GB)
3. Verify browser console for errors (F12)
4. Clear browser cache

### Videos Not Processing
**Problem:** Video stays in PROCESSING status

**Solution:**
1. Start Messenger worker: `symfony console messenger:consume doctrine_transport -vv`
2. Check FFmpeg is installed: `which ffmpeg`
3. Check temp directory exists: `mkdir -p var/videos`
4. Check logs: `tail -f var/log/dev.log`

### MinIO Connection Failed
**Problem:** "Failed to connect to MinIO" error

**Solution:**
1. Start MinIO: `docker-compose -f docker-compose.video.yml up -d`
2. Check credentials in `.env`
3. Verify port 9000 is accessible

### Progress Not Saving
**Problem:** Watch progress not updating

**Solution:**
1. Check user is logged in
2. Check browser console for JavaScript errors
3. Verify API endpoint is accessible
4. Check user has proper permissions

### No Qualities Available
**Problem:** Video won't play, no qualities showing

**Solution:**
1. Check video status is READY (not PROCESSING or DRAFT)
2. Check MinIO buckets have video files
3. Check presigned URLs are working

---

## 🎯 Common Tasks

### As a Teacher

**Upload Multiple Videos**
```
1. Go to /videos/upload?course_id=ID
2. Repeat for each video
3. Monitor course-videos page to see status
```

**Delete a Video**
```
1. Go to /videos/course?course_id=ID
2. Click delete button on video card
3. Confirm deletion
```

**Monitor Upload Status**
```
1. Go to /videos/course?course_id=ID (with ?course_id=ID)
2. View statistics cards (Total, Ready, Processing)
3. Page auto-refreshes every 30 seconds
```

### As a Student

**Continue Watching**
```
1. Go to /videos/course?course_id=ID
2. Page shows in-progress videos at top
3. Click to resume from last position
```

**View Completed Videos**
```
API Endpoint: /api/video-progress/completed-videos
Shows all videos you've watched to completion
```

**Find Video Notes**
```
In player, check sidebar for all your notes
Filter by timestamp or search
```

---

## 📈 Performance Tips

### For Teachers
- Upload videos during off-peak hours (large files take time)
- Use 720p for most courses (good balance)
- Add chapters to help students navigate
- Keep descriptions concise

### For Students
- Select quality based on your internet speed
- Use PiP mode to watch while working
- Take notes for important concepts
- Watch at 1.5x speed to save time (if comfortable)

---

## 🔐 Security Notes

- Only teachers can upload videos to their courses
- Only teachers can delete their own videos
- Students can only see and take notes on videos
- Progress is per-student and private
- All API endpoints require authentication (except health checks)

---

## 📞 Need Help?

### Documentation
- 📄 [Phase 2 Integration Guide](PHASE2_VIDEO_INTEGRATION_COMPLETE.md)
- 📄 [API Documentation](docs/API.md)

### Test the System
```bash
# Run test command
symfony console app:test-video-system

# Check routes
symfony console debug:router | grep api_video

# Check database
symfony console doctrine:schema:validate
```

---

## 🎉 You're All Set!

Your video learning system is ready to use. Start uploading videos and students can begin watching!

**Next Features Coming:**
- 🔴 Live streaming (Phase 3)
- 📝 Quizzes (Phase 3)
- 🔤 Auto-transcription (Phase 3)
- 📊 Analytics dashboard (Phase 3)

---

**Last Updated:** January 6, 2026  
**Status:** ✅ Phase 2 Complete and Ready
