import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['input', 'zone', 'preview', 'progressBar', 'progressFill', 'progressPercent'];

  connect() {
    this.setupDragAndDrop();
    this.setupFileInput();
  }

  setupDragAndDrop() {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      this.zoneTarget.addEventListener(eventName, this.preventDefaults.bind(this), false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      this.zoneTarget.addEventListener(eventName, this.highlight.bind(this), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      this.zoneTarget.addEventListener(eventName, this.unhighlight.bind(this), false);
    });

    this.zoneTarget.addEventListener('drop', this.handleDrop.bind(this), false);
    this.zoneTarget.addEventListener('click', () => this.inputTarget.click());
  }

  setupFileInput() {
    this.inputTarget.addEventListener('change', this.handleChange.bind(this));
  }

  preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  highlight(e) {
    this.zoneTarget.classList.add('drag-over');
  }

  unhighlight(e) {
    this.zoneTarget.classList.remove('drag-over');
  }

  handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    this.inputTarget.files = files;
    this.processFiles(files);
  }

  handleChange(e) {
    this.processFiles(e.target.files);
  }

  processFiles(files) {
    if (files.length > 0) {
      const file = files[0];
      this.displayPreview(file);
    }
  }

  displayPreview(file) {
    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    
    if (this.hasPreviewTarget) {
      this.previewTarget.style.display = 'block';
      
      // Update file info
      const nameEl = this.previewTarget.querySelector('#previewName');
      const sizeEl = this.previewTarget.querySelector('#previewSize');
      
      if (nameEl) nameEl.textContent = file.name;
      if (sizeEl) sizeEl.textContent = sizeMB + ' MB';
      
      // Show progress and simulate upload
      if (this.hasProgressBarTarget) {
        this.progressBarTarget.style.display = 'block';
        this.simulateProgress();
      }
    }
  }

  simulateProgress() {
    let progress = 0;
    const interval = setInterval(() => {
      progress += Math.random() * 30;
      if (progress > 90) progress = 90;
      
      if (this.hasProgressFillTarget) {
        this.progressFillTarget.style.width = progress + '%';
      }
      
      if (this.hasProgressPercentTarget) {
        this.progressPercentTarget.textContent = Math.floor(progress);
      }

      if (progress >= 90) {
        clearInterval(interval);
      }
    }, 300);
  }
}
