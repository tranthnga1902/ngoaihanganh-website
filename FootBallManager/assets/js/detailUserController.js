document.addEventListener('DOMContentLoaded', function() {
    // Điền danh sách quốc gia
    const countrySelect = document.getElementById('country');
    const countries = [
        { code: 'VN', name: 'Việt Nam' },
        { code: 'US', name: 'Hoa Kỳ' },
        { code: 'GB', name: 'Anh' },
        { code: 'AW', name: 'Aruba' },
        // Thêm các quốc gia khác nếu cần
    ];


    countries.forEach(country => {
        const option = document.createElement('option');
        option.value = country.code;
        option.textContent = country.name;
        if (country.code === window.userData.country) {
            option.selected = true;
        }
        countrySelect.appendChild(option);
    });


    // Validate form trước khi gửi
    const form = document.getElementById('updateProfileForm');
    form.addEventListener('submit', function(e) {
        let hasError = false;
        clearErrors();


        // Validate username
        const username = document.getElementById('username').value;
        if (!username || username.length < 4) {
            displayError('usernameError', 'Tên đăng nhập phải có ít nhất 4 ký tự');
            hasError = true;
        }


        // Validate email
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email)) {
            displayError('emailError', 'Email không hợp lệ');
            hasError = true;
        }


        // Validate password (nếu có)
        const password = document.getElementById('password').value;
        if (password && password.length < 6) {
            displayError('passwordError', 'Mật khẩu phải có ít nhất 6 ký tự');
            hasError = true;
        }


        // Validate birthday
        const birthday = document.getElementById('birthday').value;
        const dob = new Date(birthday);
        const now = new Date();
        const age = now.getFullYear() - dob.getFullYear();
        if (!birthday || dob > now || age < 13 || age > 100) {
            displayError('birthdayError', 'Ngày sinh không hợp lệ (phải từ 13 đến 100 tuổi)');
            hasError = true;
        }


        // Validate country
        const country = document.getElementById('country').value;
        if (!country) {
            displayError('countryError', 'Vui lòng chọn quốc gia');
            hasError = true;
        }


        // Validate phone number
        const phoneNumber = document.getElementById('phone_number').value;
        const phoneRegex = /^[0-9]{10,15}$/;
        if (!phoneNumber || !phoneRegex.test(phoneNumber)) {
            displayError('phone_numberError', 'Số điện thoại không hợp lệ');
            hasError = true;
        }


        if (hasError) {
            e.preventDefault();
        }
    });


    // Loại bỏ toggle vì form hiển thị trực tiếp
    // function toggleManageForm() { ... } không cần thiết nữa
});


function displayError(elementId, message) {
    document.getElementById(elementId).textContent = message;
}


function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => element.textContent = '');
}

