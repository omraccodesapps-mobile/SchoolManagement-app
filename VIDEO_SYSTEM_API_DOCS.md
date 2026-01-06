# 🎥 Video Learning System - API Documentation

## Overview

The Video Learning System API provides REST and WebSocket endpoints for managing video content, live streams, and student engagement. All endpoints are designed to be intuitive, well-documented, and production-ready.

**Base URL:** `http://localhost:8000/api/v1`

---

## 🔐 Authentication

All endpoints require authentication (Bearer token or session-based).

```bash
Authorization: Bearer {token}
```

---

## 📹 Video Management

### Create Video (Upload Metadata)

**Endpoint:** `POST /videos`

**Request:**
```json
{
  "title": "Introduction to PHP",
  "description": "Learn PHP basics for web development",
  "courseId": "course-uuid",
  "type": "ON_DEMAND"
}
```

**Response (201 Created):**
```json
{
  "id": "video-uuid",
  "title": "Introduction to PHP",
  "description": "Learn PHP basics for web development",
  "status": "DRAFT",
  "createdAt": "2026-01-06T16:00:00Z",
  "uploadUrl": "http://localhost:8000/api/v1/videos/video-uuid/upload"
}
```

---

### Upload Video File

**Endpoint:** `POST /videos/{videoId}/upload`

**Request:**
```
Content-Type: multipart/form-data

file: <video file>
```

**Response (202 Accepted):**
```json
{
  "id": "video-uuid",
  "status": "PROCESSING",
  "message": "Video queued for transcoding",
  "processingId": "job-uuid",
  "estimatedTime": "5-10 minutes"
}
```

**Status Updates via WebSocket:**
```javascript
// Connect to: ws://localhost:8000/ws/video/video-uuid
// Events:
// - transcoding_started
// - variant_completed (360p)
// - variant_completed (720p)
// - variant_completed (1080p)
// - thumbnail_generated
// - processing_complete
// - processing_failed
```

---

### Get Video Details

**Endpoint:** `GET /videos/{videoId}`

**Response (200 OK):**
```json
{
  "id": "video-uuid",
  "title": "Introduction to PHP",
  "description": "Learn PHP basics for web development",
  "courseId": "course-uuid",
  "teacherId": "teacher-uuid",
  "status": "READY",
  "type": "ON_DEMAND",
  "duration": 3600,
  "thumbnailUrl": "http://minio:9000/...",
  "availableQualities": [
    {
      "resolution": "360p",
      "bitrate": "500k",
      "url": "http://minio:9000/...",
      "fileSize": 150000000
    },
    {
      "resolution": "720p",
      "bitrate": "2500k",
      "url": "http://minio:9000/...",
      "fileSize": 750000000
    },
    {
      "resolution": "1080p",
      "bitrate": "5000k",
      "url": "http://minio:9000/...",
      "fileSize": 1500000000
    }
  ],
  "chapters": [
    {
      "id": "chapter-uuid",
      "title": "Getting Started",
      "startTime": 0,
      "endTime": 300,
      "description": "Introduction to PHP"
    },
    {
      "id": "chapter-uuid-2",
      "title": "Variables",
      "startTime": 300,
      "endTime": 900,
      "description": "Learn about PHP variables"
    }
  ],
  "createdAt": "2026-01-06T16:00:00Z",
  "updatedAt": "2026-01-06T16:15:00Z"
}
```

---

### List Videos by Course

**Endpoint:** `GET /courses/{courseId}/videos`

**Query Parameters:**
- `status`: DRAFT, PROCESSING, READY, ARCHIVED, FAILED
- `page`: Page number (default: 1)
- `limit`: Items per page (default: 20)
- `sort`: createdAt, title (default: createdAt)

**Response:**
```json
{
  "data": [
    { ... video object ... }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "pages": 3
  }
}
```

---

### Update Video Metadata

**Endpoint:** `PUT /videos/{videoId}`

**Request:**
```json
{
  "title": "Updated Title",
  "description": "Updated description"
}
```

**Response (200 OK):**
```json
{
  "id": "video-uuid",
  "title": "Updated Title",
  "description": "Updated description",
  "updatedAt": "2026-01-06T16:20:00Z"
}
```

---

### Delete Video

**Endpoint:** `DELETE /videos/{videoId}`

**Response (204 No Content)**

Deletes all variants, thumbnails, and transcript data from MinIO and database.

---

## ⏱️ Video Chapters (Timestamps)

### Add Chapter

**Endpoint:** `POST /videos/{videoId}/chapters`

**Request:**
```json
{
  "title": "Getting Started",
  "startTime": 0,
  "endTime": 300,
  "description": "Introduction section"
}
```

**Response (201 Created):**
```json
{
  "id": "chapter-uuid",
  "videoId": "video-uuid",
  "title": "Getting Started",
  "startTime": 0,
  "endTime": 300,
  "description": "Introduction section",
  "order": 1
}
```

---

### Get All Chapters

**Endpoint:** `GET /videos/{videoId}/chapters`

**Response:**
```json
[
  {
    "id": "chapter-uuid",
    "title": "Getting Started",
    "startTime": 0,
    "endTime": 300
  },
  {
    "id": "chapter-uuid-2",
    "title": "Variables",
    "startTime": 300,
    "endTime": 900
  }
]
```

---

### Update Chapter

**Endpoint:** `PUT /videos/{videoId}/chapters/{chapterId}`

**Request:**
```json
{
  "title": "Getting Started (Updated)",
  "description": "New description"
}
```

**Response (200 OK):**

---

### Delete Chapter

**Endpoint:** `DELETE /videos/{videoId}/chapters/{chapterId}`

**Response (204 No Content)**

---

## 📝 Notes & Bookmarks

### Create Note at Timestamp

**Endpoint:** `POST /videos/{videoId}/notes`

**Request:**
```json
{
  "content": "Important: Check the PHP documentation for more details",
  "timestamp": 150,
  "tags": ["important", "documentation"]
}
```

**Response (201 Created):**
```json
{
  "id": "note-uuid",
  "videoId": "video-uuid",
  "studentId": "student-uuid",
  "content": "Important: Check the PHP documentation for more details",
  "timestamp": 150,
  "timestampFormatted": "2:30",
  "createdAt": "2026-01-06T16:30:00Z"
}
```

---

### Get Student Notes for Video

**Endpoint:** `GET /videos/{videoId}/notes`

**Response:**
```json
[
  {
    "id": "note-uuid",
    "content": "Important: Check the PHP documentation",
    "timestamp": 150,
    "timestampFormatted": "2:30",
    "createdAt": "2026-01-06T16:30:00Z",
    "canEdit": true
  }
]
```

---

### Update Note

**Endpoint:** `PUT /notes/{noteId}`

**Request:**
```json
{
  "content": "Updated note content"
}
```

**Response (200 OK):**

---

### Delete Note

**Endpoint:** `DELETE /notes/{noteId}`

**Response (204 No Content)**

---

### Export Notes

**Endpoint:** `GET /videos/{videoId}/notes/export`

**Query Parameters:**
- `format`: pdf, markdown, json (default: markdown)

**Response:**
```
Content-Type: application/pdf  (or text/markdown)
Content-Disposition: attachment; filename="video-notes.pdf"

[file content]
```

---

## ❓ Quizzes & Questions

### Add Quiz to Video

**Endpoint:** `POST /videos/{videoId}/quizzes`

**Request:**
```json
{
  "question": "What is PHP used for?",
  "type": "MULTIPLE_CHOICE",
  "timestamp": 600,
  "options": ["Web development", "Mobile apps", "Data science", "Game development"],
  "correctAnswer": "Web development",
  "explanation": "PHP is primarily used for server-side web development",
  "order": 1
}
```

**Response (201 Created):**
```json
{
  "id": "quiz-uuid",
  "videoId": "video-uuid",
  "question": "What is PHP used for?",
  "type": "MULTIPLE_CHOICE",
  "timestamp": 600,
  "options": ["Web development", "Mobile apps", "Data science", "Game development"]
}
```

---

### Submit Quiz Answer

**Endpoint:** `POST /quizzes/{quizId}/answer`

**Request:**
```json
{
  "studentAnswer": "Web development",
  "timestamp": 605
}
```

**Response (200 OK):**
```json
{
  "correct": true,
  "points": 10,
  "explanation": "PHP is primarily used for server-side web development",
  "correctAnswer": "Web development"
}
```

---

### Get Quiz Results

**Endpoint:** `GET /videos/{videoId}/quiz-results`

**Response:**
```json
{
  "videoId": "video-uuid",
  "studentId": "student-uuid",
  "score": 85,
  "total": 100,
  "answers": [
    {
      "quizId": "quiz-uuid",
      "question": "What is PHP used for?",
      "studentAnswer": "Web development",
      "correctAnswer": "Web development",
      "correct": true,
      "points": 10
    }
  ]
}
```

---

## 📊 Progress & Bookmarks

### Update Watch Progress

**Endpoint:** `PUT /videos/{videoId}/progress`

**Request:**
```json
{
  "currentTime": 450,
  "duration": 3600
}
```

**Response (200 OK):**
```json
{
  "videoId": "video-uuid",
  "currentTime": 450,
  "duration": 3600,
  "percentageWatched": 12.5,
  "completed": false
}
```

---

### Get Watch Progress

**Endpoint:** `GET /videos/{videoId}/progress`

**Response:**
```json
{
  "videoId": "video-uuid",
  "currentTime": 450,
  "duration": 3600,
  "percentageWatched": 12.5,
  "completed": false,
  "resumeFrom": 450,
  "lastWatchedAt": "2026-01-06T17:00:00Z"
}
```

---

### Get Student's Watched Videos

**Endpoint:** `GET /my/videos`

**Query Parameters:**
- `status`: completed, in-progress, not-started
- `courseId`: Filter by course

**Response:**
```json
{
  "completed": [
    {
      "id": "video-uuid",
      "title": "Introduction to PHP",
      "courseId": "course-uuid",
      "completedAt": "2026-01-05T16:00:00Z",
      "score": 85
    }
  ],
  "inProgress": [
    {
      "id": "video-uuid-2",
      "title": "Variables and Data Types",
      "courseId": "course-uuid",
      "lastWatchedAt": "2026-01-06T17:00:00Z",
      "percentageWatched": 35,
      "resumeFrom": 1260
    }
  ]
}
```

---

## 📋 Transcripts

### Get Transcript

**Endpoint:** `GET /videos/{videoId}/transcript`

**Response:**
```json
{
  "videoId": "video-uuid",
  "language": "en",
  "status": "READY",
  "fullText": "Today we're learning PHP...",
  "segments": [
    {
      "timestamp": 0,
      "duration": 5,
      "text": "Today we're learning PHP"
    },
    {
      "timestamp": 5,
      "duration": 4,
      "text": "PHP is a server-side language"
    }
  ],
  "generatedAt": "2026-01-06T16:15:00Z"
}
```

---

### Search Transcript

**Endpoint:** `GET /videos/{videoId}/transcript/search`

**Query Parameters:**
- `q`: Search query (required)
- `limit`: Max results (default: 10)

**Response:**
```json
{
  "query": "PHP",
  "results": [
    {
      "timestamp": 0,
      "text": "Today we're learning PHP",
      "context": "...learning PHP. PHP is a server-side..."
    },
    {
      "timestamp": 5,
      "text": "PHP is a server-side language",
      "context": "...learning PHP. PHP is a server-side..."
    }
  ]
}
```

---

## 🔴 Live Streaming

### Schedule Live Session

**Endpoint:** `POST /live-sessions`

**Request:**
```json
{
  "title": "Live PHP Workshop",
  "description": "Real-time Q&A on PHP",
  "courseId": "course-uuid",
  "scheduledAt": "2026-01-07T14:00:00Z"
}
```

**Response (201 Created):**
```json
{
  "id": "session-uuid",
  "title": "Live PHP Workshop",
  "status": "SCHEDULED",
  "scheduledAt": "2026-01-07T14:00:00Z",
  "webrtcRoom": "room-xyz123",
  "joinUrl": "http://localhost:8000/live/room-xyz123"
}
```

---

### Start Live Session

**Endpoint:** `POST /live-sessions/{sessionId}/start`

**Response (200 OK):**
```json
{
  "id": "session-uuid",
  "status": "LIVE",
  "startedAt": "2026-01-07T14:00:00Z",
  "webrtcRoom": "room-xyz123"
}
```

---

### Join Live Session (WebRTC)

**Endpoint:** `POST /live-sessions/{sessionId}/join`

**Response:**
```json
{
  "sessionId": "session-uuid",
  "userId": "student-uuid",
  "webrtcRoom": "room-xyz123",
  "signalingServer": "ws://localhost:8000/ws/live/room-xyz123",
  "stunServers": [
    "stun:stun.l.google.com:19302",
    "stun:stun1.l.google.com:19302"
  ]
}
```

---

### Send Chat Message

**Endpoint:** `POST /live-sessions/{sessionId}/chat`

**Request:**
```json
{
  "message": "Great explanation! Can you clarify this point?"
}
```

**Response (201 Created):**
```json
{
  "id": "message-uuid",
  "sender": {
    "id": "student-uuid",
    "name": "John Doe"
  },
  "message": "Great explanation! Can you clarify this point?",
  "timestamp": "2026-01-07T14:05:00Z"
}
```

---

### Get Live Attendees

**Endpoint:** `GET /live-sessions/{sessionId}/attendees`

**Response:**
```json
[
  {
    "id": "student-uuid",
    "name": "John Doe",
    "joinedAt": "2026-01-07T14:00:00Z",
    "durationMinutes": 5,
    "participationScore": 85
  },
  {
    "id": "student-uuid-2",
    "name": "Jane Smith",
    "joinedAt": "2026-01-07T14:02:00Z",
    "durationMinutes": 3,
    "participationScore": 60
  }
]
```

---

### End Live Session

**Endpoint:** `POST /live-sessions/{sessionId}/end`

**Response (200 OK):**
```json
{
  "id": "session-uuid",
  "status": "ENDED",
  "endedAt": "2026-01-07T15:00:00Z",
  "recordingUrl": "http://minio:9000/...",
  "attendeesCount": 25,
  "durationMinutes": 60
}
```

---

### Get Recorded Sessions

**Endpoint:** `GET /live-sessions/recorded`

**Query Parameters:**
- `courseId`: Filter by course

**Response:**
```json
[
  {
    "id": "session-uuid",
    "title": "Live PHP Workshop",
    "teacherName": "Dr. Smith",
    "recordedAt": "2026-01-07T14:00:00Z",
    "durationMinutes": 60,
    "attendeesCount": 25,
    "recordingUrl": "http://minio:9000/..."
  }
]
```

---

## 📈 Analytics & Reports

### Get Student Progress Summary

**Endpoint:** `GET /students/{studentId}/learning-summary`

**Response:**
```json
{
  "studentId": "student-uuid",
  "totalVideosAssigned": 45,
  "videosWatched": 32,
  "completionPercentage": 71,
  "averageScore": 82,
  "hoursWatched": 45.5,
  "coursesProgress": [
    {
      "courseId": "course-uuid",
      "title": "PHP Fundamentals",
      "videosWatched": 12,
      "totalVideos": 15,
      "completionPercentage": 80,
      "averageScore": 85
    }
  ]
}
```

---

### Get Course Statistics

**Endpoint:** `GET /courses/{courseId}/statistics`

**Response:**
```json
{
  "courseId": "course-uuid",
  "totalStudents": 50,
  "studentsWatching": 38,
  "averageCompletion": 65,
  "averageScore": 78,
  "videoAnalytics": [
    {
      "videoId": "video-uuid",
      "title": "Introduction to PHP",
      "views": 48,
      "averageViewDuration": 28.5,
      "completionRate": 75,
      "averageScore": 85
    }
  ]
}
```

---

## 🔔 Real-time Notifications (WebSocket)

### Subscribe to Video Processing Status

```javascript
const socket = new WebSocket('ws://localhost:8000/ws/video/video-uuid');

socket.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log(data);
  // {
  //   "type": "processing_update",
  //   "status": "transcoding",
  //   "variant": "720p",
  //   "progress": 45,
  //   "timestamp": "2026-01-06T16:15:00Z"
  // }
};
```

---

## 🚫 Error Responses

All errors follow a consistent format:

```json
{
  "error": "Validation failed",
  "code": "VALIDATION_ERROR",
  "message": "File size exceeds maximum allowed size",
  "details": {
    "field": "file",
    "value": "oversized.mp4",
    "constraint": "max_size"
  }
}
```

### Common Status Codes

| Code | Meaning |
|------|---------|
| 200  | Success |
| 201  | Created |
| 202  | Accepted (async processing) |
| 204  | No Content (deleted) |
| 400  | Bad Request (validation error) |
| 401  | Unauthorized (not authenticated) |
| 403  | Forbidden (insufficient permissions) |
| 404  | Not Found |
| 409  | Conflict (resource exists) |
| 422  | Unprocessable Entity |
| 429  | Too Many Requests (rate limited) |
| 500  | Server Error |

---

## 📌 Rate Limiting

- **Video Upload:** 1 upload per 60 seconds per user
- **API Requests:** 100 requests per minute
- **WebSocket:** Real-time, no limit

---

## 🔒 Permissions

| Resource | Create | Read | Update | Delete |
|----------|--------|------|--------|--------|
| Video | Teacher | All | Teacher/Admin | Teacher/Admin |
| Quiz | Teacher | All | Teacher/Admin | Teacher/Admin |
| Notes | Student | Student | Student | Student |
| Progress | System | Student | Student | - |
| LiveSession | Teacher | All | Teacher/Admin | Teacher/Admin |
| ChatMessage | All | All | Self/Admin | Self/Admin |

---

**API Version:** 1.0  
**Last Updated:** January 6, 2026  
**Status:** ✅ Complete Documentation (Endpoints ready for implementation)
