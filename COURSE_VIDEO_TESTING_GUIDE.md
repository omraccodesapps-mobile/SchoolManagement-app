# Course Video Upload - Testing Guide

## Prerequisites

### System Requirements
- ✅ PHP 8.1+
- ✅ Symfony 6.4+
- ✅ FFmpeg installed and configured
- ✅ MinIO running and configured
- ✅ MySQL/PostgreSQL database
- ✅ Message queue (Messenger component)

### Environment Setup
```bash
# Check FFmpeg
ffmpeg -version
ffprobe -version

# Check MinIO
curl http://localhost:9000/minio/health/live

# Check Symfony
symfony version

# Check Database
symfony console doctrine:database:create --if-not-exists

# Run migrations
symfony console doctrine:migrations:migrate
```

## Test Scenarios

### 1. Basic Course Creation (Without Video)

**Steps:**
1. Navigate to `/teacher/courses/new`
2. Fill in:
   - Title: "Test Course 1"
   - Description: "Test description"
   - Video: (leave empty)
3. Click "Create Course"

**Expected Results:**
- ✅ Course created successfully
- ✅ Flash message: "Course created successfully!"
- ✅ Redirected to course show page
- ✅ "No videos uploaded yet" message shown

**Files to Check:**
- `templates/teacher/course/show.html.twig` - No videos section
- Database - Course exists with no videos

---

### 2. Course Creation with Video Upload

**Prepare:**
- Get a sample video file (MP4 recommended)
- Video file size: 10-100MB (or any size < 2GB)
- Example: `sample.mp4`

**Steps:**
1. Navigate to `/teacher/courses/new`
2. Fill in course details:
   - Title: "Web Development Basics"
   - Description: "Learn web development fundamentals"
3. Upload video:
   - Method A: Click upload zone, select file
   - Method B: Drag file into upload zone
4. Verify file preview appears:
   - ✅ Filename displays
   - ✅ File size displays
   - ✅ Progress bar visible
5. Click "Create Course"

**Expected Results:**
- ✅ Course created in database
- ✅ Video file uploaded
- ✅ Flash message: "Course created successfully! Video is being processed."
- ✅ Redirected to course show page
- ✅ "Course Videos" section visible
- ✅ Video status: "PROCESSING" (badge)
- ✅ ProcessVideoMessage dispatched

**Files to Check:**
- Database - Course created with relationship to Video
- Database - Video entity created with status='PROCESSING'
- MinIO - Original video file uploaded
- Message queue - ProcessVideoMessage in queue

---

### 3. File Validation Tests

#### Test 3.1: Supported Formats

**Test Files:** Create with different formats
```bash
# MP4 (H.264)
ffmpeg -f lavfi -i color=c=blue:s=320x240:d=5 test.mp4

# WebM
ffmpeg -f lavfi -i color=c=green:s=320x240:d=5 test.webm

# OGG
ffmpeg -f lavfi -i color=c=red:s=320x240:d=5 test.ogg
```

**Steps:**
1. Create course with MP4 file
2. Verify upload succeeds
3. Repeat with WebM and OGG

**Expected Results:**
- ✅ MP4 uploads successfully
- ✅ WebM uploads successfully
- ✅ OGG uploads successfully
- ✅ All appear in "Course Videos" section

#### Test 3.2: Invalid Format

**Steps:**
1. Navigate to `/teacher/courses/new`
2. Try uploading a non-video file:
   - PDF file
   - Image file (JPG, PNG)
   - Text file
3. Observe form behavior

**Expected Results:**
- ✅ File input validation prevents selection (accept attribute)
- OR
- ✅ Server-side validation error shown
- ✅ Error message: "Please upload a valid video file"
- ✅ Form can be resubmitted

#### Test 3.3: File Size Limit (2GB)

**Steps:**
1. Create a very large video file:
   ```bash
   # Create fake 2.5GB file
   dd if=/dev/zero of=large.mp4 bs=1M count=2560
   ```
2. Try uploading to course
3. Observe validation

**Expected Results:**
- ✅ JavaScript validation prevents upload
- OR
- ✅ Server-side validation rejects
- ✅ Error message: "File size exceeds 2GB limit"

---

### 4. UI/UX Tests

#### Test 4.1: Drag & Drop

**Steps:**
1. Navigate to `/teacher/courses/new`
2. Prepare video file on desktop
3. Drag file directly to upload zone
4. Release mouse over upload zone

**Expected Results:**
- ✅ Upload zone highlights when dragging over
- ✅ File selected after drop
- ✅ File preview displays
- ✅ No page reload

#### Test 4.2: Click to Browse

**Steps:**
1. Navigate to `/teacher/courses/new`
2. Click inside upload zone
3. File browser dialog opens
4. Select video file
5. Click "Open"

**Expected Results:**
- ✅ File browser opens
- ✅ File selection works
- ✅ File preview updates
- ✅ Form can be submitted

#### Test 4.3: File Removal

**Steps:**
1. Upload a video file (preview shows)
2. Click "Remove" button (×)
3. Try submitting form

**Expected Results:**
- ✅ File preview disappears
- ✅ Progress bar hidden
- ✅ Form submits without file
- ✅ Course created without video

#### Test 4.4: Progress Display

**Steps:**
1. Upload a large video file (100MB+)
2. Watch progress bar
3. Monitor percentage display

**Expected Results:**
- ✅ Progress bar animates
- ✅ Percentage increases
- ✅ File size displayed correctly
- ✅ Upload completes

---

### 5. Database Tests

#### Test 5.1: Course-Video Relationship

**Using Database Query:**
```sql
-- Check courses with videos
SELECT c.id, c.title, COUNT(v.id) as video_count
FROM course c
LEFT JOIN video v ON c.id = v.course_id
GROUP BY c.id, c.title;

-- Check video details
SELECT v.id, v.title, v.status, v.duration, v.course_id
FROM video v
ORDER BY v.created_at DESC
LIMIT 5;
```

**Expected Results:**
- ✅ Video records created
- ✅ course_id foreign key populated
- ✅ status field set correctly
- ✅ created_at timestamp set
- ✅ uploaded_by_id populated (current user)

#### Test 5.2: Cascade Operations

**Steps:**
1. Create course with video
2. Delete course from database
3. Check videos table

**Expected Results:**
- ✅ Video record is also deleted (cascade)
- ✅ Video files removed from storage
- ✅ No orphaned video records

---

### 6. Processing Tests

#### Test 6.1: Video Processing Status

**Steps:**
1. Create course with video
2. Navigate to course show page
3. Check video status badge

**Timeline:**
```
T+0s:   Status = PROCESSING (badge: yellow)
T+30s:  Status = PROCESSING (continues)
T+60s:  Status = PROCESSING (continues)
T+300s: Status = READY (badge: green) - streaming available
```

**Expected Results:**
- ✅ Status begins as PROCESSING
- ✅ Status eventually changes to READY
- ✅ Badge color changes (yellow → green)
- ✅ "Watch" button becomes clickable

**CLI Commands to Monitor:**
```bash
# Check message queue
symfony console messenger:consume async -vv

# Check video processing
symfony console debug:config liip_imagine  # or your video config

# Check MinIO buckets
aws s3 ls s3://school-videos --endpoint-url http://localhost:9000
aws s3 ls s3://school-thumbnails --endpoint-url http://localhost:9000
```

#### Test 6.2: Video Watch Functionality

**Steps:**
1. Wait for video to process (status = READY)
2. Click "Watch" button on course page
3. Verify video player appears
4. Try playing video

**Expected Results:**
- ✅ Video player loads
- ✅ Stream quality selector available
- ✅ Video plays correctly
- ✅ Progress bar works
- ✅ Volume control works

---

### 7. Error Handling Tests

#### Test 7.1: Upload Failure Handling

**Steps:**
1. Stop MinIO service
2. Try creating course with video
3. Observe error behavior

**Expected Results:**
- ✅ Error caught gracefully
- ✅ Error message displayed: "Error uploading video: ..."
- ✅ Course still created (optional video)
- ✅ No fatal exception thrown

**To Resume:**
```bash
docker-compose up -d minio  # or restart MinIO
```

#### Test 7.2: Processing Failure

**Steps:**
1. Corrupt a video file (e.g., change first bytes)
2. Upload corrupted video
3. Wait for processing

**Expected Results:**
- ✅ Video status eventually changes to ERROR
- ✅ Error badge appears (red)
- ✅ Error message available in logs
- ✅ Teacher can delete and re-upload

---

### 8. Performance Tests

#### Test 8.1: Large File Upload

**Steps:**
1. Create large video file (500MB+)
2. Upload to course
3. Monitor upload speed
4. Check memory usage

**Expected Results:**
- ✅ Upload completes without timeout
- ✅ No memory exhaustion
- ✅ Progress bar updates smoothly
- ✅ No connection drops

#### Test 8.2: Concurrent Uploads

**Steps:**
1. Open course creation form in 3 tabs
2. Upload different videos simultaneously
3. Monitor all upload progress

**Expected Results:**
- ✅ All uploads proceed independently
- ✅ No interference between uploads
- ✅ All videos processed successfully
- ✅ No database conflicts

---

### 9. Integration Tests

#### Test 9.1: Full User Journey

**Complete Workflow:**
```
1. Teacher logs in
2. Navigate to /teacher/courses
3. Click "New Course"
4. Fill form:
   - Title: "Full Stack Development"
   - Description: "Complete MERN stack course"
   - Video: Upload course_intro.mp4
5. Submit form
6. See "Course created successfully!"
7. Course show page displays
8. Video appears in "Course Videos"
9. Status shows "Processing"
10. Wait 2-5 minutes
11. Refresh page
12. Status changes to "Ready"
13. Click "Watch" button
14. Video player opens
15. Play video successfully
```

**Expected Results:**
- ✅ All steps complete without errors
- ✅ Video processes and becomes available
- ✅ Students can view video
- ✅ Analytics track video views

#### Test 9.2: Mobile Experience

**Setup:**
- Open on iPhone/Android
- Or use Chrome DevTools device emulation

**Steps:**
1. Navigate to course creation on mobile
2. Upload video using device camera/files
3. Submit form
4. View on course page
5. Try watching video

**Expected Results:**
- ✅ Responsive layout adapts to mobile
- ✅ Upload zone touch-friendly
- ✅ Form controls easily clickable
- ✅ Video player responsive
- ✅ No layout breaks

---

### 10. Security Tests

#### Test 10.1: Permission Check

**Steps:**
1. Log in as student
2. Try accessing `/teacher/courses/new`

**Expected Results:**
- ✅ Access denied (403 Forbidden)
- ✅ Redirected to appropriate page
- ✅ Only teachers can create courses

#### Test 10.2: CSRF Token

**Steps:**
1. Fill course form
2. Check HTML for CSRF token
3. Submit form

**Expected Results:**
- ✅ Form includes CSRF token
- ✅ Server validates token
- ✅ Invalid token rejected
- ✅ Prevents CSRF attacks

#### Test 10.3: File Upload Security

**Steps:**
1. Attempt uploading executable file renamed as .mp4
2. Attempt uploading PHP file
3. Monitor for execution

**Expected Results:**
- ✅ Server validates actual file type (not just extension)
- ✅ File stored in non-executable directory
- ✅ MIME type verified
- ✅ No code execution

---

## Browser Compatibility Testing

### Desktop Browsers
```
✅ Chrome 90+
✅ Firefox 88+
✅ Safari 14+
✅ Edge 90+

Test Cases:
□ Upload works
□ Drag & drop works
□ Progress displays
□ Video plays
□ Responsive design
```

### Mobile Browsers
```
✅ Chrome Mobile
✅ Safari iOS
✅ Firefox Mobile
✅ Samsung Internet

Test Cases:
□ Touch upload works
□ File picker opens
□ Progress visible
□ Video player responsive
□ All buttons clickable
```

---

## Test Checklist

### Pre-Testing
- [ ] FFmpeg installed and working
- [ ] MinIO running and healthy
- [ ] Database migrations applied
- [ ] Message queue ready
- [ ] Symfony server running
- [ ] Sample video files prepared

### Functionality Tests
- [ ] Create course without video
- [ ] Create course with video
- [ ] Upload MP4 file
- [ ] Upload WebM file
- [ ] Upload OGG file
- [ ] Drag & drop upload
- [ ] Click to browse upload
- [ ] File preview displays
- [ ] Video processes (PROCESSING state)
- [ ] Video ready (READY state)
- [ ] Watch video button works
- [ ] Video plays in player
- [ ] Delete video works

### Error Tests
- [ ] Invalid file format rejected
- [ ] File too large rejected
- [ ] Upload failure handled
- [ ] Processing failure handled
- [ ] Network error resilience

### UI/UX Tests
- [ ] Responsive on desktop
- [ ] Responsive on tablet
- [ ] Responsive on mobile
- [ ] Drag-over visual feedback
- [ ] Progress bar smooth
- [ ] Status badges clear
- [ ] Error messages helpful

### Performance Tests
- [ ] Large file upload (500MB+)
- [ ] Multiple concurrent uploads
- [ ] Video processing speed
- [ ] No memory leaks
- [ ] No timeout issues

### Security Tests
- [ ] Permission checks work
- [ ] CSRF token validated
- [ ] File type validation
- [ ] XSS prevention
- [ ] SQL injection prevention

### Integration Tests
- [ ] Full user journey works
- [ ] Database relationships correct
- [ ] MinIO storage correct
- [ ] Message queue working
- [ ] Async processing works

---

## Running Tests

### Unit Tests
```bash
symfony console make:test VideoUploadControllerTest
symfony console make:test CourseTypeTest

# Run tests
./bin/phpunit tests/Controller/Teacher/CourseControllerTest.php
./bin/phpunit tests/Form/CourseTypeTest.php
```

### Integration Tests
```bash
# Test form submission
./bin/phpunit tests/Integration/CourseCreationWithVideoTest.php

# Test video processing
./bin/phpunit tests/Integration/VideoProcessingTest.php
```

### Manual Testing Commands
```bash
# Check course created
symfony console doctrine:query:sql "SELECT * FROM course ORDER BY created_at DESC LIMIT 1"

# Check video created
symfony console doctrine:query:sql "SELECT * FROM video ORDER BY created_at DESC LIMIT 1"

# Check message queue
symfony console messenger:consume async -vvv

# Check MinIO files
aws s3 ls s3://school-videos --endpoint-url http://localhost:9000 --recursive
```

---

## Troubleshooting Common Issues

### Issue: Upload Zone Not Clickable
**Fix:**
1. Check CSS is loaded: DevTools → Elements → Inspect
2. Check z-index in CSS
3. Verify input element not hidden

### Issue: File Preview Not Showing
**Fix:**
1. Check JavaScript errors: DevTools → Console
2. Verify file-upload_controller.js loaded
3. Check Stimulus controller connection

### Issue: Video Processing Stuck
**Fix:**
1. Check message queue: `symfony console messenger:consume async`
2. Verify FFmpeg working: `ffmpeg -version`
3. Check MinIO connection: `curl http://localhost:9000`
4. Check logs: `tail -f var/log/dev.log`

### Issue: Video Status Never Changes
**Fix:**
1. Run message queue consumer manually
2. Check database for Video record
3. Review error logs for FFmpeg errors
4. Verify disk space available

---

**Test Guide Version**: 1.0  
**Last Updated**: January 6, 2026  
**Status**: ✅ Complete
