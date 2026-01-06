# Course Video Upload - Visual Integration Guide

## 🎬 Complete Feature Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    TEACHER WORKFLOW                             │
└─────────────────────────────────────────────────────────────────┘

                    ┌─ Visit /teacher/courses/new
                    │
                    ▼
        ┌──────────────────────────────┐
        │   Course Creation Form       │
        │  (Enhanced with Video Field) │
        └──────────────┬───────────────┘
                       │
        ┌──────────────┴─────────────┐
        │                            │
    Enter Course Info          (Optional) Upload Video
    ├─ Title ✓                      │
    ├─ Description ✓          ┌─────▼──────────┐
    └─ Video (optional)       │  File Upload   │
                              │  • Drag & Drop │
                              │  • Click Browse│
                              │  • Validation  │
                              └─────┬──────────┘
                                    │
                            ┌───────▼────────┐
                            │  File Preview  │
                            │ • Name Display │
                            │ • Size Display │
                            │ • Remove Btn   │
                            └────────┬───────┘
                                     │
                            ┌────────▼─────────┐
                            │  Progress Bar    │
                            │  (Simulated UX)  │
                            └────────┬─────────┘
                                     │
                            ┌────────▼──────────┐
                            │   Submit Form      │
                            └────────┬───────────┘
                                     │
        ┌────────────────────────────┼────────────────────────────┐
        │                            │                            │
        ▼                            ▼                            │
   ┌────────────┐          ┌──────────────────┐                 │
   │   Course   │          │   VideoUpload    │                 │
   │  Created   │          │    Service       │                 │
   │  in DB     │          │  (handles file)  │                 │
   └────────────┘          └────────┬─────────┘                 │
        │                           │                            │
        │                   ┌───────▼────────┐                   │
        │                   │   Video Entity │                   │
        │                   │   Created      │                   │
        │                   └───────┬────────┘                   │
        │                           │                            │
        │                   ┌───────▼────────────────────┐       │
        │                   │  ProcessVideoMessage       │       │
        │                   │  Dispatched to Queue       │       │
        │                   └───────┬────────────────────┘       │
        │                           │                            │
        │                   ┌───────▼────────────────┐            │
        │                   │  Background Processing  │            │
        │                   │  • Extract Metadata     │            │
        │                   │  • Transcode to 480p    │            │
        │                   │  • Transcode to 720p    │            │
        │                   │  • Transcode to 1080p   │            │
        │                   │  • Generate Thumbnail   │            │
        │                   │  • Update Status→READY  │            │
        │                   └───────┬────────────────┘             │
        │                           │                             │
        └───────────┬───────────────┘                             │
                    │                                             │
                    ▼                                             │
        ┌────────────────────────────┐                            │
        │  Course Show Page          │                            │
        │  (Updated with Videos)     │                            │
        │  ┌──────────────────────┐  │                            │
        │  │ Course Videos        │  │                            │
        │  ├──────────────────────┤  │                            │
        │  │ Title | Status | ... │  │                            │
        │  ├──────────────────────┤  │                            │
        │  │ Video1│Processing│...│  │                            │
        │  │ Video2│Ready    │Watch│  │                            │
        │  └──────────────────────┘  │                            │
        └────────────────────────────┘                            │
                    │                                             │
                    └─────────────────────────────────────────────┘
```

## 📁 File Structure Changes

```
SchoolManagement-app/
│
├── src/
│   ├── Controller/
│   │   ├── Teacher/
│   │   │   └── CourseController.php ⭐ MODIFIED
│   │   │       • Added VideoUploadService
│   │   │       • Added MessageBusInterface
│   │   │       • Enhanced new() method
│   │   │       • Added video upload handling
│   │   │
│   │   └── VideoUploadController.php ✓ EXISTING
│   │
│   ├── Form/
│   │   └── CourseType.php ⭐ MODIFIED
│   │       • Added video FileType field
│   │       • Added file constraints
│   │       • Added styling attributes
│   │
│   ├── Entity/
│   │   ├── Course.php ✓ EXISTING
│   │   └── Video.php ✓ EXISTING
│   │
│   └── Service/
│       └── Video/
│           └── VideoUploadService.php ✓ EXISTING
│
├── templates/
│   └── teacher/
│       └── course/
│           ├── new.html.twig ⭐ MODIFIED
│           │   • Added upload zone
│           │   • Added file preview
│           │   • Added progress bar
│           │   • Added CSS styling
│           │   • Added JavaScript handlers
│           │
│           └── show.html.twig ⭐ MODIFIED
│               • Added Course Videos section
│               • Added video list table
│               • Added status badges
│               • Added quick actions
│
├── assets/
│   └── controllers/
│       └── file-upload_controller.js ✨ NEW
│           • Stimulus controller
│           • Drag & drop handling
│           • File preview logic
│
├── COURSE_VIDEO_INTEGRATION.md ✨ NEW
│   └── Complete documentation (500+ lines)
│
├── COURSE_VIDEO_UPLOAD_SUMMARY.md ✨ NEW
│   └── Implementation summary
│
└── COURSE_VIDEO_QUICK_REFERENCE.md ✨ NEW
    └── Quick reference guide
```

## 🔄 Data Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                        USER INTERFACE LAYER                      │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │         Twig Templates (new.html.twig)                    │  │
│  │  • Form fields                                            │  │
│  │  • File upload zone                                       │  │
│  │  • Real-time preview                                      │  │
│  │  • Progress indication                                    │  │
│  └────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                              ▲
                              │ HTTP POST
                              │
┌──────────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                             │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  CourseController::new()                                  │  │
│  │  • Form validation                                        │  │
│  │  • Course entity creation                                 │  │
│  │  • Video file handling                                    │  │
│  │  • Message dispatch                                       │  │
│  └────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                              │
                ┌─────────────┼─────────────┐
                │             │             │
                ▼             ▼             ▼
        ┌──────────────┐ ┌────────────┐ ┌──────────────┐
        │ Database     │ │ Messenger  │ │ FileSystem   │
        │ Transaction  │ │ Bus        │ │ (Temp Dir)   │
        │              │ │            │ │              │
        │ ✓ Course     │ │ ✓ Queue    │ │ ✓ Uploaded   │
        │   saved      │ │   message  │ │   file       │
        └──────────────┘ └────────────┘ └──────────────┘
                │             │             │
                └─────────────┼─────────────┘
                              │
        ┌─────────────────────▼─────────────────────┐
        │    Background Processing (Async)          │
        │  ProcessVideoMessage Handler               │
        │  • Extract metadata                        │
        │  • Transcode to qualities                  │
        │  • Generate thumbnail                      │
        │  • Update video status                     │
        └─────────────────────┬─────────────────────┘
                              │
                    ┌─────────▼────────┐
                    │ Storage Layer    │
                    │                  │
                    │ ✓ MinIO Buckets  │
                    │   • Original     │
                    │   • Thumbnails   │
                    │   • Streams      │
                    │     (480p)       │
                    │     (720p)       │
                    │     (1080p)      │
                    └──────────────────┘
```

## 🎯 User Interactions

### Desktop Flow
```
┌─────────────────────────────────────────────┐
│  Course Creation Form (Two-Column Layout)   │
├────────────────────┬────────────────────────┤
│   Information      │  Form Fields           │
│   Section          │  • Course Title        │
│   • What you get   │  • Description        │
│   • Benefits       │  • Video Upload (NEW) │
│   • Progress steps │    - Drag zone        │
│                    │    - Click to browse  │
│                    │    - File preview     │
│                    │    - Progress bar     │
│                    │  • Submit button      │
└────────────────────┴────────────────────────┘
```

### Mobile Flow
```
┌──────────────────────────┐
│  Course Creation Form    │
│  (Full Width, Single)    │
├──────────────────────────┤
│                          │
│  Course Title            │
│  [_____________________] │
│                          │
│  Description             │
│  [___________________    │
│  ___________________]    │
│                          │
│  Video Upload ✨ NEW     │
│  ┌────────────────────┐  │
│  │  Drop or tap to    │  │
│  │  browse            │  │
│  │  🎬                │  │
│  └────────────────────┘  │
│                          │
│  [Create Course] [Cancel]│
│                          │
└──────────────────────────┘
```

## 🔌 Component Relationships

```
CourseType Form
    │
    ├─→ TextType (title)
    ├─→ TextareaType (description)
    └─→ FileType (video) ⭐ NEW
        └─ Constraints
            ├─ File size (2GB)
            └─ MIME types (MP4, WebM, etc.)

    │
    ▼

CourseController
    │
    ├─→ CourseRepository
    ├─→ VideoUploadService
    │   └─→ MinIOService
    │       └─→ S3Client
    ├─→ MessageBusInterface
    │   └─→ ProcessVideoMessage
    │       └─→ VideoProcessor
    │           └─→ FFmpeg
    └─→ EntityManagerInterface
        └─→ Database

    │
    ▼

Templates
    │
    ├─→ new.html.twig ⭐ MODIFIED
    │   └─→ file-upload_controller.js ⭐ NEW
    │       └─→ Stimulus JS
    │
    └─→ show.html.twig ⭐ MODIFIED
        └─→ Video list display
            ├─ Status badges
            ├─ Watch button
            └─ Delete action
```

## 📊 State Transitions

```
COURSE STATE:
NOT_CREATED → CREATED (when form submitted with title)
    │
    └─→ (Video optional)

VIDEO STATE (if uploaded):
UPLOADING → QUEUED → PROCESSING → READY (success)
                              │
                              └─→ ERROR (failure)

COMBINED STATE:
┌────────────────────────────────────────┐
│  Course: CREATED                       │
│  Video: Processing                     │
│  Display: Show course page with        │
│           video status badge           │
└────────────────────────────────────────┘
        │
        ▼ (after processing)
┌────────────────────────────────────────┐
│  Course: CREATED                       │
│  Video: READY                          │
│  Display: Show course page with        │
│           "Watch" button available     │
└────────────────────────────────────────┘
```

## 🎨 UI Components

### Upload Zone Component
```html
<div class="file-upload-zone">
  <input type="file" class="file-input" accept="video/*">
  <div class="upload-content">
    <span class="upload-icon">🎬</span>
    <h4>Drop video here or click to browse</h4>
    <p>Supported: MP4, WebM, OGG, MOV, AVI (Max 2GB)</p>
  </div>
</div>

CSS Effects:
• Border: 2px dashed #007bff
• Hover: Background changes to light blue
• Drag-over: Border highlights, scale effect
• Responsive: Adapts to mobile screens
```

### File Preview Component
```html
<div class="file-preview">
  <div class="preview-item">
    <span class="preview-icon">🎥</span>
    <div class="preview-info">
      <div class="preview-name">video.mp4</div>
      <div class="preview-size">256.5 MB</div>
    </div>
    <button class="preview-remove">×</button>
  </div>
  <div class="preview-progress">
    <div class="progress-bar">
      <div class="progress-fill" style="width: 45%"></div>
    </div>
    <div class="progress-text">45% uploading...</div>
  </div>
</div>
```

### Course Videos Section
```html
<div class="card mb-3">
  <div class="card-body">
    <h5>📹 Course Videos (2)</h5>
    <table class="table table-hover">
      <thead>
        <tr>
          <th>Title</th>
          <th>Status</th>
          <th>Duration</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Course Intro</td>
          <td><span class="badge bg-success">Ready</span></td>
          <td>12m</td>
          <td>Jan 6, 2025</td>
          <td>
            <button class="btn btn-sm btn-primary">Watch</button>
            <button class="btn btn-sm btn-danger">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
```

## 🚀 Performance Optimization

```
Upload Performance:
• Chunked upload (automatic)
• Progress feedback
• Cancel support
• Connection resilience

Processing Performance:
• Asynchronous (non-blocking)
• Parallel transcoding
• Optimized FFmpeg settings
• Queue management

Storage Performance:
• MinIO clustering
• Auto-replication
• CDN integration ready
• Distributed caching
```

---

**Visual Guide Version**: 1.0  
**Last Updated**: January 6, 2026  
**Status**: ✅ Complete and Ready
