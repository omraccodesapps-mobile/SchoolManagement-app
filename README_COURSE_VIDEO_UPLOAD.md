# 🎬 Course Video Upload Feature

> **Seamlessly upload and manage course introduction videos during course creation**

## 🌟 Overview

The Course Video Upload feature enables teachers to upload introduction videos directly when creating courses. Videos are automatically processed with FFmpeg, transcoded to multiple quality levels, and stored in MinIO for efficient streaming to students.

## ✨ Key Features

### 🎥 Video Upload Integration
- Upload videos directly in course creation form
- Optional - courses can be created without videos
- Supports multiple formats (MP4, WebM, OGG, MOV, AVI)
- Maximum file size: 2GB per video

### 🖱️ Intuitive User Experience
- **Drag & Drop** - Simply drag videos into the upload zone
- **File Preview** - See filename and file size before uploading
- **Progress Indication** - Visual progress bar during upload
- **Real-time Validation** - Instant feedback on file selection

### ⚙️ Automatic Processing
- **Background Processing** - Videos process asynchronously without blocking course creation
- **Multi-Quality Transcoding** - Automatic transcoding to 480p, 720p, and 1080p
- **Thumbnail Generation** - Automatic thumbnail for video preview
- **Metadata Extraction** - Duration, codec, and resolution captured automatically

### 📊 Status Tracking
- **Live Status Updates** - Track video processing status in real-time
- **Status Badges** - Clear visual indicators (Processing, Ready, Error)
- **Course Dashboard** - Manage all course videos in one place
- **Quick Actions** - Watch, delete, or manage videos easily

### 🎓 Student Experience
- **Quality Streaming** - Students can choose streaming quality (480p, 720p, 1080p)
- **Progress Tracking** - Watch progress automatically saved
- **Mobile Friendly** - Responsive video player on all devices
- **Accessibility** - Full keyboard and screen reader support

## 🚀 Quick Start

### For Teachers

#### Creating a Course with Video

1. **Navigate to Course Creation**
   ```
   Go to: /teacher/courses/new
   ```

2. **Fill in Course Details**
   - Enter course title (required)
   - Enter course description (optional)
   - Select introduction video (optional)

3. **Upload Video**
   - **Option A (Drag & Drop)**: Drag video file from computer to upload zone
   - **Option B (Click Browse)**: Click upload zone to open file browser
   - Confirm file appears in preview

4. **Submit Form**
   - Click "Create Course" button
   - Course is created immediately
   - Video begins processing in background

5. **Monitor Progress**
   - Navigate to course details page
   - View "Course Videos" section
   - Watch status change from PROCESSING to READY
   - Once READY, click "Watch" button to preview

#### Managing Course Videos

```
Course Details Page
    ↓
"Course Videos" Section
    ├─ Video List Table
    │  ├─ Title
    │  ├─ Status (badge)
    │  ├─ Duration
    │  ├─ Upload Date
    │  └─ Actions (Watch, Delete)
    ↓
Watch Video
    └─ Opens video player with quality selector
```

### For Developers

#### Installation

```bash
# All components already integrated, no additional setup needed
# Just ensure:
1. FFmpeg is installed: ffmpeg -version
2. MinIO is running: curl http://localhost:9000
3. Message queue running: symfony console messenger:consume async
```

#### Key Files

| File | Purpose |
|------|---------|
| `src/Form/CourseType.php` | Form definition with video field |
| `src/Controller/Teacher/CourseController.php` | Course creation logic with video handling |
| `templates/teacher/course/new.html.twig` | Course creation form template |
| `templates/teacher/course/show.html.twig` | Course details with video section |
| `assets/controllers/file-upload_controller.js` | Stimulus controller for upload UI |

#### Creating a Course Programmatically

```php
use App\Entity\Course;
use App\Service\Video\VideoUploadService;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Messenger\Message\ProcessVideoMessage;

$course = new Course();
$course->setTitle('My Course');
$course->setDescription('Course description');
$course->setTeacher($user);

$entityManager->persist($course);
$entityManager->flush();

// If uploading video
$video = $videoUploadService->uploadVideo(
    $uploadedFile,        // UploadedFile instance
    $course,              // Course entity
    $user,                // User uploading
    'Video Title',        // Title
    'Video description'   // Description
);

$messageBus->dispatch(new ProcessVideoMessage($video->getId()));
```

## 📋 API Endpoints

### Video Upload Endpoint
```
POST /api/videos/upload

Parameters:
  - video (file): Video file to upload
  - course_id (integer): Course ID
  - title (string): Video title
  - description (string): Video description

Response:
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Course Introduction",
    "status": "PROCESSING",
    "duration": 3600,
    "courseId": 1
  }
}
```

### Check Video Status
```
GET /api/videos/{videoId}/status

Response:
{
  "success": true,
  "data": {
    "status": "READY",
    "progress": 100,
    "duration": 3600,
    "thumbnail_url": "https://cdn.example.com/thumb.jpg",
    "streams": [
      { "quality": "480p", "url": "https://cdn.example.com/480p.m3u8" },
      { "quality": "720p", "url": "https://cdn.example.com/720p.m3u8" },
      { "quality": "1080p", "url": "https://cdn.example.com/1080p.m3u8" }
    ]
  }
}
```

## 🎯 Supported Video Formats

| Format | Extension | Codec | Max Size |
|--------|-----------|-------|----------|
| MP4    | .mp4      | H.264 | 2GB      |
| WebM   | .webm     | VP9   | 2GB      |
| OGG    | .ogg      | Theora| 2GB      |
| MOV    | .mov      | H.264 | 2GB      |
| AVI    | .avi      | MPEG-4| 2GB      |

## 📊 Video Processing Pipeline

```
Upload → Validation → Queue Message → Background Processing
                                      ├─ Extract Metadata
                                      │  ├─ Duration
                                      │  ├─ Resolution
                                      │  └─ Codec
                                      ├─ Generate Thumbnail
                                      ├─ Transcode to 480p
                                      ├─ Transcode to 720p
                                      ├─ Transcode to 1080p
                                      └─ Update Status→READY
                                           ↓
                                      Ready for Streaming
```

**Approximate Processing Times:**
- Duration depends on file size and server resources
- 100MB file: ~2-5 minutes
- 500MB file: ~10-20 minutes
- 1GB+ file: ~30-60 minutes

## 🔒 Security Features

✅ **File Type Validation**
- MIME type checking
- Extension validation
- Magic number verification

✅ **Size Limits**
- Maximum 2GB per file
- Disk space monitoring
- Upload timeout protection

✅ **Permission Checks**
- Teacher-only course creation
- Owner verification for modifications
- Admin override capability

✅ **Data Protection**
- CSRF token validation
- XSS prevention via Twig escaping
- SQL injection prevention via ORM

✅ **Storage Security**
- Secure file storage outside web root
- Unique filename generation
- Access control via authentication

## 🧪 Testing

### Quick Test

```bash
# 1. Start servers
docker-compose up -d
symfony serve

# 2. Navigate to course creation
# Visit: http://localhost:8000/teacher/courses/new

# 3. Create test course
# - Title: "Test Course"
# - Description: "Test description"
# - Upload: sample_video.mp4

# 4. Verify processing
# - Check status on course page
# - Should show "PROCESSING"
# - Wait 2-5 minutes
# - Refresh page
# - Status should change to "READY"
```

### Full Testing

For comprehensive testing guide, see [COURSE_VIDEO_TESTING_GUIDE.md](COURSE_VIDEO_TESTING_GUIDE.md)

## 📚 Documentation

- **[COURSE_VIDEO_INTEGRATION.md](COURSE_VIDEO_INTEGRATION.md)** - Complete feature documentation (500+ lines)
- **[COURSE_VIDEO_TESTING_GUIDE.md](COURSE_VIDEO_TESTING_GUIDE.md)** - Comprehensive testing guide
- **[COURSE_VIDEO_VISUAL_GUIDE.md](COURSE_VIDEO_VISUAL_GUIDE.md)** - Visual flows and diagrams
- **[COURSE_VIDEO_QUICK_REFERENCE.md](COURSE_VIDEO_QUICK_REFERENCE.md)** - Quick reference
- **[COURSE_VIDEO_UPLOAD_SUMMARY.md](COURSE_VIDEO_UPLOAD_SUMMARY.md)** - Implementation summary

## 🛠️ Troubleshooting

### Upload Not Working

**Problem**: File upload zone not responding to clicks or drag-drop
**Solution**:
1. Check browser console for JavaScript errors
2. Verify CSS loaded: DevTools → Network tab
3. Try different browser
4. Clear cache and reload

### Video Processing Stuck

**Problem**: Video status remains "PROCESSING" for hours
**Solution**:
1. Check message queue: `symfony console messenger:consume async -vv`
2. Verify FFmpeg installed: `ffmpeg -version`
3. Check MinIO running: `curl http://localhost:9000`
4. Review logs: `tail -f var/log/dev.log`
5. Check disk space: `df -h`

### Video Shows ERROR Status

**Problem**: Video processing failed with error status
**Solution**:
1. Check error details in application logs
2. Try re-uploading the video
3. Verify video format is correct
4. Try a different video file
5. Check MinIO connectivity

### Upload Timeout on Large Files

**Problem**: Large file uploads time out or fail
**Solution**:
1. Increase PHP timeout: `php.ini`
   ```ini
   upload_max_filesize = 2048M
   post_max_size = 2048M
   max_execution_time = 3600
   ```
2. Restart web server
3. Try uploading from same network (for local development)
4. Monitor network speed during upload

## 🎓 Learning Resources

### Understanding the Architecture

1. **Form Handling** - How CourseType form processes file input
2. **File Validation** - Server-side and client-side validation
3. **Async Processing** - Symfony Messenger for background jobs
4. **Video Transcoding** - FFmpeg integration and quality levels
5. **Storage** - MinIO bucket organization and access

### Example Use Cases

**Use Case 1: Teacher Creates Course**
```
1. Teacher creates "Advanced JavaScript" course
2. Uploads 45-minute tutorial video
3. System automatically:
   - Extracts metadata (45min duration)
   - Generates thumbnail
   - Creates 3 quality streams (480p, 720p, 1080p)
4. Students can now watch with quality selector
```

**Use Case 2: Multiple Videos in Single Course**
```
1. Teacher creates "Python Basics" course
2. Uploads 3 introduction videos
3. Each processes independently
4. Course page lists all videos with status
5. Students can watch any video
```

## 📈 Performance Metrics

### Expected Performance

| Metric | Expected |
|--------|----------|
| Upload Speed | Depends on network (typically 10-100 Mbps) |
| Processing (100MB) | 2-5 minutes |
| Processing (500MB) | 10-20 minutes |
| Streaming Latency | <1 second (first frame) |
| Concurrent Uploads | 10+ simultaneously |
| Concurrent Streams | 100+ simultaneously |

### Optimization Tips

1. **Upload during off-peak hours** - Less server load
2. **Use H.264 codec** - Better compatibility and compression
3. **Keep videos under 500MB** - Faster processing
4. **Use optimal resolution** - 1080p recommended
5. **Monitor disk space** - Ensure sufficient MinIO storage

## 🔄 Integration with Other Systems

### Video System Integration
- Uses VideoUploadService
- Leverages FFmpeg for transcoding
- Stores in MinIO buckets
- Integrates with video player

### Course System Integration
- Extends Course entity relationships
- Stores videos in course context
- Maintains teacher-course associations
- Tracks upload history

### Student System Integration
- Students can view course videos
- Progress tracking available
- Analytics integration possible
- Accessibility features supported

## 🚀 Future Enhancements

### Planned Features

1. **📹 Multi-File Upload** - Upload multiple videos at once
2. **✂️ Video Editing** - Trim, cut, and edit videos
3. **📝 Subtitles** - Auto-generate or upload subtitles
4. **📊 Analytics** - Track viewing statistics per student
5. **🎬 Playlists** - Organize videos in sequences

### Under Consideration

- Live streaming integration
- Interactive video features (quizzes, clickable elements)
- Automatic transcription with search
- Video watermarking
- DRM protection
- Adaptive bitrate streaming (HLS/DASH)

## 📞 Support

### Getting Help

1. **Documentation** - See [COURSE_VIDEO_INTEGRATION.md](COURSE_VIDEO_INTEGRATION.md)
2. **Testing Guide** - See [COURSE_VIDEO_TESTING_GUIDE.md](COURSE_VIDEO_TESTING_GUIDE.md)
3. **Quick Reference** - See [COURSE_VIDEO_QUICK_REFERENCE.md](COURSE_VIDEO_QUICK_REFERENCE.md)
4. **Logs** - Check `var/log/dev.log` for detailed errors
5. **CLI Debugging** - Use `symfony console` commands

### Reporting Issues

When reporting issues, include:
1. Video file size and format
2. Error message displayed
3. Browser and version
4. Steps to reproduce
5. Server logs (if available)

## 📄 License

This feature is part of the School Management Application.

## ✅ Status

**Version**: 1.0  
**Status**: ✅ Production Ready  
**Last Updated**: January 6, 2026  
**Tested**: ✅ Comprehensive test suite included  
**Documentation**: ✅ Complete (500+ lines)

---

**Ready to enhance your courses with video content! 🎉**
