# Course Video Upload Integration

## Overview

Teachers can now upload introduction videos directly when creating a new course. The video upload functionality is seamlessly integrated into the course creation form and uses all the advanced video processing features of the Video Learning System.

## Features

### 1. **Integrated Video Upload**
- Upload videos directly in the course creation form
- Optional - teachers can create courses without videos
- Supports multiple video formats (MP4, WebM, OGG, MOV, AVI)
- Maximum file size: 2GB

### 2. **Drag & Drop Support**
- Drag and drop videos into the upload zone
- Click to browse and select files
- Visual feedback during drag operations
- Real-time file preview with file name and size

### 3. **Video Processing**
- Videos are automatically queued for background processing
- FFmpeg transcoding for multiple quality levels (480p, 720p, 1080p)
- Automatic thumbnail generation
- Duration calculation
- Storage in MinIO with distributed architecture

### 4. **Status Tracking**
- Courses display upload status (Processing, Ready, Error)
- Real-time progress indication
- Access to watch, edit, or delete videos
- Error handling with user-friendly messages

### 5. **Course Dashboard**
- View all uploaded videos for a course
- Track video processing status
- Access video analytics and statistics
- Manage video content

## Implementation Details

### Modified Files

#### 1. **Form: CourseType.php**
- Added `video` field with file type
- Added constraints for file size (2GB max) and mime types
- Bootstrap styling for form inputs
- Optional field - can be submitted empty

```php
->add('video', FileType::class, [
    'label' => 'Course Introduction Video (Optional)',
    'mapped' => false,
    'required' => false,
    'constraints' => [
        new File([
            'maxSize' => '2048M',
            'mimeTypes' => [
                'video/mp4',
                'video/webm',
                'video/ogg',
                'video/quicktime',
                'video/x-msvideo',
            ],
        ]),
    ],
])
```

#### 2. **Controller: CourseController.php**
- Injected `VideoUploadService` and `MessageBusInterface`
- Updated `new()` action to handle video upload
- Dispatches `ProcessVideoMessage` for background processing
- Handles video upload alongside course creation in single transaction

```php
if ($videoFile) {
    $video = $this->videoUploadService->uploadVideo(
        $videoFile,
        $course,
        $this->getUser(),
        $videoTitle,
        'Introduction video for ' . $course->getTitle()
    );
    $this->messageBus->dispatch(new ProcessVideoMessage($video->getId()));
}
```

#### 3. **Template: new.html.twig**
- Added video upload form field with drag & drop zone
- File preview with progress tracking
- Styled upload zone with icons and instructions
- Real-time file size display
- CSS animations and responsive design

#### 4. **Template: show.html.twig**
- New "Course Videos" section displaying all uploaded videos
- Video status badges (Processing, Ready, Error)
- Duration display
- Upload date tracking
- Quick actions (Watch, Delete)
- Empty state with upload button

### New Files

#### 1. **Stimulus Controller: file-upload_controller.js**
```javascript
- Handles drag & drop file selection
- Prevents default browser behavior
- Updates file preview with name and size
- Simulates upload progress
- Validates file before processing
```

## Workflow

### User Journey: Creating Course with Video

1. **Navigate to Course Creation**
   - Teacher clicks "Create Course"
   - Form displays with title, description, and optional video field

2. **Fill Course Details**
   - Enter course title
   - Enter course description
   - (Optional) Upload introduction video

3. **Upload Video (Optional)**
   - Drag video to upload zone OR click to browse
   - File preview appears with name and size
   - Progress bar shows simulated progress

4. **Submit Form**
   - Click "Create Course" button
   - Course is created in database
   - Video upload begins if file was selected
   - `ProcessVideoMessage` dispatched to message queue

5. **Background Processing**
   - Video is transcoded to multiple quality levels
   - Thumbnail is generated
   - Metadata is extracted (duration, codec, etc.)
   - Video status changes from PROCESSING to READY
   - Student can now watch the video

### Teacher Dashboard: Managing Videos

1. **View Course**
   - Navigate to course details page
   - New "Course Videos" section displays all videos

2. **Video List Display**
   - Title and description
   - Processing status (badge)
   - Duration
   - Upload date
   - Quick actions

3. **Watch Video**
   - Click "Watch" button to view
   - Opens video player with streaming quality selector

4. **Delete Video**
   - Click trash icon
   - Video is removed and storage is cleaned up

## Database Integration

### Related Entities

**Course Entity**
```
- id (integer, primary key)
- title (string)
- description (text)
- teacher (User, foreign key)
- videos (OneToMany, VideoCollection)
- Created_at (DateTimeImmutable)
```

**Video Entity**
```
- id (UUID)
- title (string)
- description (text)
- status (ENUM: PROCESSING, READY, ERROR)
- duration (integer, seconds)
- course (Course, foreign key)
- uploaded_by (User, foreign key)
- created_at (DateTimeImmutable)
- updated_at (DateTimeImmutable)
- ...
```

## API Endpoints Used

### Video Upload API
```
POST /api/videos/upload
Parameters:
  - video: UploadedFile
  - course_id: integer
  - title: string
  - description: string
Response: {success, data: {id, title, status, duration}}
```

### Video Status Check
```
GET /api/videos/{videoId}/status
Response: {success, data: {status, progress, ...}}
```

## Error Handling

### File Size Validation
- Maximum 2GB per file
- Error message: "File size exceeds 2GB limit"
- User is prompted to select a smaller file

### File Type Validation
- Only video formats allowed
- Error message: "Please upload a valid video file"
- Acceptable types: MP4, WebM, OGG, MOV, AVI

### Upload Failures
- If upload fails, error flash message is shown
- User is redirected back to course creation form
- Form data is preserved (not lost)

### Processing Failures
- If video processing fails, status shows "ERROR"
- Teacher can delete and re-upload video
- Error logs recorded for debugging

## Performance Considerations

### File Upload
- Client-side file validation
- Progress tracking during upload
- Chunked upload for large files (handled by Symfony)
- Cancel upload support

### Video Processing
- Asynchronous processing via Messenger
- Doesn't block course creation
- Multiple transcoding jobs run in parallel
- FFmpeg optimization for faster processing

### Storage
- MinIO distributed storage
- Automatic backup and replication
- CDN-friendly for streaming
- Automatic cleanup of temporary files

## Styling & UX

### Form Design
- Bootstrap 5 integration
- Responsive layout (mobile-friendly)
- Visual step indicator
- Helpful hints and descriptions
- Color-coded status badges

### Upload Zone
```css
- Dashed border indicating drop zone
- Hover effects for interactivity
- Drag-over visual feedback
- Icon and instruction text
- File preview card with remove button
- Progress bar animation
```

### Responsive Design
```
Desktop: Two-column layout (info + form)
Tablet: Single column layout with responsive form
Mobile: Full-width form with touch-friendly controls
```

## Testing

### Manual Testing Checklist
- [ ] Create course without video
- [ ] Create course with video upload
- [ ] Drag and drop video file
- [ ] Click to browse and select file
- [ ] Upload large file (>100MB)
- [ ] Upload unsupported file format
- [ ] View course with video
- [ ] Watch uploaded video
- [ ] Delete uploaded video
- [ ] Check video processing status

### Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Future Enhancements

1. **Multi-File Upload**
   - Upload multiple videos per course at once
   - Bulk operations

2. **Video Editing**
   - Trim/cut videos
   - Add subtitles
   - Create playlists

3. **Advanced Analytics**
   - Watch time tracking per student
   - Engagement metrics
   - Dropout analysis

4. **Streaming Optimization**
   - Adaptive bitrate streaming (HLS/DASH)
   - CDN integration
   - Geographic distribution

5. **Collaboration**
   - Share videos between courses
   - Video templates
   - Re-use existing videos

## Troubleshooting

### Video Not Processing
1. Check MinIO connection
2. Verify FFmpeg is installed
3. Check disk space
4. Review error logs in var/log/

### Upload Fails with 413 Error
1. Check `upload_max_filesize` in php.ini (set to ≥2G)
2. Check `post_max_size` in php.ini (set to ≥2G)
3. Restart web server after config change

### Video Status Stuck on PROCESSING
1. Check message queue: `symfony console messenger:consume async`
2. Check FFmpeg process: `ps aux | grep ffmpeg`
3. Review video processing service logs

### File Size Shows Incorrectly
1. Clear browser cache
2. Refresh page
3. Check JavaScript console for errors

## Support & Documentation

For more information:
- Video System: [docs/PDF_SERVICE_INTEGRATION.md](docs/PDF_SERVICE_INTEGRATION.md)
- API Documentation: [docs/API.md](docs/API.md)
- Installation: [INSTALLATION.md](INSTALLATION.md)
