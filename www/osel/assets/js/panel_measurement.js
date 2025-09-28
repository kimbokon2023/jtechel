// Panel Measurement JavaScript
class PanelMeasurement {
    constructor() {
        this.selectedPanel = null;
        this.validationRules = {
            width: { min: 1, max: 3000 },
            height: { min: 1, max: 3300 },
            thickness: { min: 0.8, max: 2 }
        };
        this.init();
    }

    init() {
        this.bindEvents();
        // Auto-load functionality removed for fresh data entry
    }

    bindEvents() {
        // Panel selection
        document.querySelectorAll('.panel').forEach(panel => {
            panel.addEventListener('click', (e) => {
                this.selectPanel(e.target);
            });
        });

        // Form validation on input
        ['panelWidth', 'panelHeight', 'panelThickness'].forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', () => this.validateInput(input));
                input.addEventListener('blur', () => this.validateInput(input));
            }
        });

        // Validation button
        const validateBtn = document.getElementById('validateBtn');
        if (validateBtn) {
            validateBtn.addEventListener('click', () => this.validateAllMeasurements());
        }

        // Form submission
        const form = document.getElementById('measurementForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        // Auto-save functionality removed
    }

    selectPanel(panelElement) {
        // Remove previous selection
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('selected'));
        
        // Add selection to clicked panel
        panelElement.classList.add('selected');
        
        const panelNumber = panelElement.getAttribute('data-panel');
        this.selectedPanel = panelNumber;
        
        // Update UI
        document.getElementById('selectedPanel').value = panelNumber;
        document.getElementById('selectedPanelNumber').textContent = panelNumber;
        document.getElementById('selectedPanelInfo').style.display = 'block';
        
        // Load existing measurements for this panel if any
        this.loadPanelMeasurements(panelNumber);
        
        // Enable form inputs
        this.enableMeasurementInputs();
    }

    enableMeasurementInputs() {
        const inputs = ['panelWidth', 'panelHeight', 'panelThickness'];
        inputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.disabled = false;
            }
        });
    }

    validateInput(input) {
        const value = parseFloat(input.value);
        const inputType = input.id.replace('panel', '').toLowerCase();
        
        let isValid = true;
        let message = '';

        if (isNaN(value) || value === '') {
            isValid = true; // Empty is allowed for optional fields
            input.classList.remove('validation-error', 'validation-success');
            return isValid;
        }

        const rules = this.validationRules[inputType];
        if (rules) {
            if (value < rules.min || value > rules.max) {
                isValid = false;
                message = `값은 ${rules.min}-${rules.max}mm 범위여야 합니다.`;
            }
        }

        // Additional validation rules based on panel dimensions
        if (isValid && this.selectedPanel) {
            const panelType = parseInt(this.selectedPanel);
            const validation = this.validatePanelDimensions(panelType, inputType, value);
            isValid = validation.isValid;
            message = validation.message;
        }

        // Update input appearance
        input.classList.remove('validation-error', 'validation-success');
        if (value !== '') {
            input.classList.add(isValid ? 'validation-success' : 'validation-error');
        }

        // Show/hide validation message
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = message;
            feedback.style.display = isValid ? 'none' : 'block';
        }

        return isValid;
    }

    validatePanelDimensions(panelNumber, dimension, value) {
        const result = { isValid: true, message: '' };
        
        // 판넬별 권장 범위 (PHP와 동일하게 맞춤)
        const panelLimits = {
            1: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 하단 좌측
            2: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 좌측 하단
            3: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 좌측 중간
            4: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 좌측 상단
            5: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 상단 좌측
            6: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 상단 중앙
            7: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 상단 우측
            8: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 우측 상단
            9: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } },  // 우측 중간
            10: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } }, // 우측 하단
            11: { width: { min: 200, max: 1550 }, height: { min: 2000, max: 3200 } }  // 하단 우측
        };

        if (panelLimits[panelNumber] && panelLimits[panelNumber][dimension]) {
            const limits = panelLimits[panelNumber][dimension];
            if (value < limits.min || value > limits.max) {
                result.isValid = false;
                result.message = `판넬 ${panelNumber}의 ${dimension === 'width' ? '가로' : '세로'}는 ${limits.min}-${limits.max}mm 범위가 적절합니다.`;
            }
        }

        return result;
    }

    validateAllMeasurements() {
        if (!this.selectedPanel) {
            this.showAlert('warning', '판넬을 먼저 선택해주세요.');
            return false;
        }

        const requiredFields = ['siteName', 'measurementDate'];
        const measurementFields = ['panelWidth', 'panelHeight', 'panelThickness'];
        
        let allValid = true;
        let hasAtLeastOneMeasurement = false;

        // Validate required fields
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && !field.value.trim()) {
                field.classList.add('validation-error');
                allValid = false;
            } else if (field) {
                field.classList.remove('validation-error');
            }
        });

        // Validate measurement fields
        measurementFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && field.value.trim()) {
                hasAtLeastOneMeasurement = true;
                if (!this.validateInput(field)) {
                    allValid = false;
                }
            }
        });

        if (!hasAtLeastOneMeasurement) {
            this.showAlert('warning', '최소한 하나의 측정값을 입력해주세요.');
            return false;
        }

        if (allValid) {
            this.showAlert('success', '모든 측정값이 유효합니다. 저장할 수 있습니다.');
            document.getElementById('saveBtn').disabled = false;
            return true;
        } else {
            this.showAlert('error', '입력값을 확인해주세요. 잘못된 값이 있습니다.');
            document.getElementById('saveBtn').disabled = true;
            return false;
        }
    }

    handleSubmit(event) {
        event.preventDefault();
        
        if (!this.validateAllMeasurements()) {
            return;
        }

        const formData = new FormData(document.getElementById('measurementForm'));
        
        // Show loading state
        const submitBtn = document.getElementById('saveBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 저장 중...';
        submitBtn.disabled = true;

        fetch('save_measurement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showAlert('success', '측정 데이터가 성공적으로 저장되었습니다.');
                this.resetForm();
                // Auto-redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 2000);
            } else {
                this.showAlert('error', data.message || '저장 중 오류가 발생했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showAlert('error', '서버와의 통신 중 오류가 발생했습니다.');
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    // Auto-save functionality removed for fresh data entry

    // Auto-save functionality removed

    // Load saved data functionality removed for fresh data entry

    getFormData() {
        const form = document.getElementById('measurementForm');
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    }

    populateForm(data) {
        Object.keys(data).forEach(key => {
            const element = document.getElementById(key) || document.querySelector(`[name="${key}"]`);
            if (element && data[key]) {
                element.value = data[key];
            }
        });
    }

    resetForm() {
        document.getElementById('measurementForm').reset();
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('selected'));
        document.getElementById('selectedPanelInfo').style.display = 'none';
        document.getElementById('saveBtn').disabled = true;
        this.selectedPanel = null;
        // Auto-save localStorage removal not needed
    }

    loadPanelMeasurements(panelNumber) {
        const siteName = document.getElementById('siteName').value;
        if (!siteName) return;

        fetch(`get_panel_data.php?site_name=${encodeURIComponent(siteName)}&panel_number=${panelNumber}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.measurements) {
                const measurements = data.measurements;
                if (measurements.panel_width) document.getElementById('panelWidth').value = measurements.panel_width;
                if (measurements.panel_height) document.getElementById('panelHeight').value = measurements.panel_height;
                if (measurements.panel_thickness) document.getElementById('panelThickness').value = measurements.panel_thickness;
                if (measurements.material_type) document.getElementById('materialType').value = measurements.material_type;
                if (measurements.notes) document.getElementById('notes').value = measurements.notes;
                
                this.showAlert('info', '기존 측정 데이터를 불러왔습니다.');
            }
        })
        .catch(error => {
            console.error('Error loading panel data:', error);
        });
    }

    showAlert(type, message) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PanelMeasurement();
});