import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'file', 'title', 'description', 'submitBtn', 'progress', 'progressBar', 'progressPercent', 'error', 'errorText', 'success'];

    connect() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.fileTarget.addEventListener('change', () => this.handleFileSelect());
        this.formTarget.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleFileSelect() {
        if (this.fileTarget.files.length > 0) {
            const file = this.fileTarget.files[0];
            this.submitBtnTarget.disabled = false;
        } else {
            this.submitBtnTarget.disabled = true;
        }
    }

    async handleSubmit(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('video', this.fileTarget.files[0]);
        formData.append('title', this.titleTarget.value);
        formData.append('description', this.descriptionTarget.value);
        formData.append('course_id', document.getElementById('courseId').value);

        // Hide previous messages
        this.errorTarget.classList.add('d-none');
        this.successTarget.classList.add('d-none');
        
        // Show progress
        this.progressTarget.classList.remove('d-none');
        this.submitBtnTarget.disabled = true;

        try {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    this.progressBarTarget.style.width = percentComplete + '%';
                    this.progressPercentTarget.textContent = Math.round(percentComplete) + '%';
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 201) {
                    this.progressTarget.classList.add('d-none');
                    this.successTarget.classList.remove('d-none');
                    this.formTarget.reset();
                } else {
                    const response = JSON.parse(xhr.responseText);
                    this.showError(response.message || 'Upload failed');
                    this.progressTarget.classList.add('d-none');
                    this.submitBtnTarget.disabled = false;
                }
            });

            xhr.addEventListener('error', () => {
                this.showError('Network error occurred during upload');
                this.progressTarget.classList.add('d-none');
                this.submitBtnTarget.disabled = false;
            });

            xhr.open('POST', '/api/videos/upload');
            xhr.send(formData);

        } catch (error) {
            this.showError(error.message);
            this.progressTarget.classList.add('d-none');
            this.submitBtnTarget.disabled = false;
        }
    }

    showError(message) {
        this.errorTextTarget.textContent = message;
        this.errorTarget.classList.remove('d-none');
    }
}
