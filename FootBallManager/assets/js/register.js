document.addEventListener('DOMContentLoaded', function() {
    // Khởi tạo bước đầu tiên
    document.getElementById('formStep1').style.display = 'block';
    document.getElementById('step1').classList.add('active');


    // Hiển thị lỗi từ server nếu có và giữ ở bước 1
    displayServerErrors();


    // Validate email khi blur
    document.getElementById('email').addEventListener('blur', validateEmailLive);


    // Tải danh sách quốc gia
    loadCountries();


    // Xử lý hiển thị mật khẩu
    setupPasswordToggles();
});


// Hiển thị lỗi từ server
function displayServerErrors() {
    const serverErrors = window.serverErrors || {};
   
    // Nếu có lỗi, đảm bảo hiển thị bước 1
    if (Object.keys(serverErrors).length > 0) {
        document.querySelectorAll('.form-step').forEach(step => step.style.display = 'none');
        document.querySelectorAll('.progress-step').forEach(step => step.classList.remove('active'));
        document.getElementById('formStep1').style.display = 'block';
        document.getElementById('step1').classList.add('active');
    }


    // Hiển thị lỗi cho từng trường
    if (serverErrors.username) showError('username', serverErrors.username);
    if (serverErrors.email) showError('email', serverErrors.email);
    if (serverErrors.password) showError('password', serverErrors.password);
    if (serverErrors.birthday) showError('birthday', serverErrors.birthday);
    if (serverErrors.country) showError('country', serverErrors.country);
    if (serverErrors.phone_number) showError('phone_number', serverErrors.phone_number);
    if (serverErrors.sex) showError('sex', serverErrors.sex);
    if (serverErrors.database) alert(serverErrors.database); // Hiển thị lỗi cơ sở dữ liệu
}


// Validate email real-time
function validateEmailLive() {
    const email = this.value;
    const emailError = document.getElementById('emailError');
   
    if (!email) {
        emailError.textContent = '';
        return;
    }
   
    if (!validateEmail(email)) {
        emailError.textContent = 'Vui lòng nhập địa chỉ email hợp lệ!';
        return;
    }
   
    // Kiểm tra email tồn tại
    checkEmailExists(email);
}


// Kiểm tra email tồn tại
function checkEmailExists(email) {
    fetch('../Controller/check_email.php?email=' + encodeURIComponent(email))
        .then(response => response.json())
        .then(data => {
            const emailError = document.getElementById('emailError');
            emailError.textContent = data.exists ? 'Email này đã được đăng ký!' : '';
        })
        .catch(error => {
            console.error('Lỗi khi kiểm tra email:', error);
            document.getElementById('emailError').textContent = 'Lỗi kiểm tra email, vui lòng thử lại.';
        });
}


// Chuyển bước
function nextStep(currentStep) {
    if (currentStep === 1 && !validateStep1()) {
        document.getElementById('step1Error').style.display = 'block';
        return;
    }
   
    if (currentStep === 2 && !validateStep2()) {
        return;
    }
   
    document.getElementById(`formStep${currentStep}`).style.display = 'none';
    document.getElementById(`step${currentStep}`).classList.remove('active');
   
    const nextStepNum = currentStep + 1;
    document.getElementById(`formStep${nextStepNum}`).style.display = 'block';
    document.getElementById(`step${nextStepNum}`).classList.add('active');
   
    if (nextStepNum === 3) {
        displaySelectedTeams();
    }
}


// Quay lại bước trước
function prevStep(currentStep) {
    document.getElementById(`formStep${currentStep}`).style.display = 'none';
    document.getElementById(`step${currentStep}`).classList.remove('active');
   
    const prevStepNum = currentStep - 1;
    document.getElementById(`formStep${prevStepNum}`).style.display = 'block';
    document.getElementById(`step${prevStepNum}`).classList.add('active');
}


// Validate bước 1
function validateStep1() {
    let isValid = true;
    const form = document.getElementById('registrationForm');
   
    // Kiểm tra các trường bắt buộc
    const requiredFields = ['username', 'email', 'password', 'confirmPassword', 'birthday', 'country', 'phone_number'];
    requiredFields.forEach(field => {
        if (!form[field].value.trim()) {
            showError(field, 'Trường này là bắt buộc');
            isValid = false;
        } else {
            clearError(field);
        }
    });


    // Kiểm tra mật khẩu khớp
    if (form['password'].value !== form['confirmPassword'].value) {
        showError('confirmPassword', 'Mật khẩu không khớp');
        isValid = false;
    }


    // Kiểm tra độ dài mật khẩu
    if (form['password'].value && form['password'].value.length < 6) {
        showError('password', 'Mật khẩu phải có ít nhất 6 ký tự');
        isValid = false;
    }


    // Kiểm tra email hợp lệ
    if (!validateEmail(form['email'].value)) {
        showError('email', 'Email không hợp lệ');
        isValid = false;
    }


    // Kiểm tra đã chọn giới tính
    if (!document.querySelector('input[name="sex"]:checked')) {
        showError('sex', 'Vui lòng chọn giới tính');
        isValid = false;
    }


    // Kiểm tra số điện thoại
    if (form['phone_number'].value && !/^[0-9]{10,15}$/.test(form['phone_number'].value)) {
        showError('phone_number', 'Số điện thoại không hợp lệ');
        isValid = false;
    }


    // Kiểm tra năm sinh
    if (form['birthday'].value) {
        const birthYear = new Date(form['birthday'].value).getFullYear();
        const currentYear = new Date().getFullYear();
        if (birthYear > currentYear) {
            showError('birthday', 'Năm sinh không thể lớn hơn năm hiện tại');
            isValid = false;
        } else if (birthYear < currentYear - 100) {
            showError('birthday', 'Tuổi không thể lớn hơn 100');
            isValid = false;
        }
    }


    return isValid;
}


// Validate bước 2
function validateStep2() {
    const selectedTeams = document.querySelectorAll('input[name="favoriteTeams[]"]:checked');
    if (selectedTeams.length === 0) {
        alert('Vui lòng chọn ít nhất 1 đội bóng');
        return false;
    }
    return true;
}


// Hiển thị đội đã chọn
function displaySelectedTeams() {
    const selectedTeams = Array.from(document.querySelectorAll('input[name="favoriteTeams[]"]:checked'));
    const container = document.getElementById('displayFavoriteTeams');
   
    container.innerHTML = selectedTeams.map(checkbox => {
        const teamId = checkbox.value;
        const team = window.teamsData.find(t => t.team_id == teamId);
        const teamName = team ? team.name : 'Unknown';
        const logoUrl = team ? team.logo_url : 'https://via.placeholder.com/100';
        return `
            <div class="selected-team">
                <img src="${logoUrl}" alt="${teamName}" width="30">
                <span>${teamName}</span>
            </div>
        `;
    }).join('');
}


// Submit form
function validateAndSubmit() {
    const termsChecked = document.getElementById('agreeTerms').checked;
    if (!termsChecked) {
        document.getElementById('termsError').textContent = 'Vui lòng đồng ý với điều khoản';
        return;
    }
   
    addTeamIdsToForm();
    document.getElementById('registrationForm').submit();
}


// Thêm team_ids vào form
function addTeamIdsToForm() {
    const selectedTeams = Array.from(document.querySelectorAll('input[name="favoriteTeams[]"]:checked'));
    const teamIds = selectedTeams.map(checkbox => checkbox.value);
   
    let hiddenInput = document.getElementById('selectedTeamIds');
    if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'selectedTeamIds';
        hiddenInput.id = 'selectedTeamIds';
        document.getElementById('registrationForm').appendChild(hiddenInput);
    }
    hiddenInput.value = teamIds.join(',');
}


// Helper functions
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}


function showError(fieldId, message) {
    const errorElement = document.getElementById(`${fieldId}Error`) || createErrorElement(fieldId);
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    document.getElementById(fieldId)?.classList.add('error');
}


function clearError(fieldId) {
    const errorElement = document.getElementById(`${fieldId}Error`);
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    document.getElementById(fieldId)?.classList.remove('error');
}


function createErrorElement(fieldId) {
    const errorElement = document.createElement('span');
    errorElement.id = `${fieldId}Error`;
    errorElement.className = 'error-message';
    document.getElementById(fieldId).after(errorElement);
    return errorElement;
}


function loadCountries() {
    const countrySelect = document.getElementById('country');
   
    const defaultCountries = [
        {code: 'VN', name: 'Việt Nam'},
        {code: 'US', name: 'United States'},
        {code: 'UK', name: 'United Kingdom'}
    ];
   
    defaultCountries.forEach(country => {
        const option = document.createElement('option');
        option.value = country.code;
        option.textContent = country.name;
        countrySelect.appendChild(option);
    });
   
    fetch('https://restcountries.com/v3.1/all')
        .then(response => response.json())
        .then(countries => {
            countries.sort((a, b) => a.name.common.localeCompare(b.name.common));
           
            countries.forEach(country => {
                if (!defaultCountries.some(c => c.code === country.cca2)) {
                    const option = document.createElement('option');
                    option.value = country.cca2;
                    option.textContent = country.name.common;
                    countrySelect.appendChild(option);
                }
            });
        })
        .catch(error => {
            console.error('Không thể tải danh sách quốc gia:', error);
        });
}


function setupPasswordToggles() {
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
           
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
}



