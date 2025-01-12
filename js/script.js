document.addEventListener('DOMContentLoaded', () => {
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);

    const checkbox = document.getElementById('checkbox');
    if (checkbox) {
        checkbox.checked = currentTheme === 'dark';
        checkbox.addEventListener('change', (e) => {
            const theme = e.target.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        });
    }

    // Initialize dashboard features
    initializeDashboard();
});

const showTabContent = (tabId, element, contentClass) => {
    $(`.${contentClass}`).hide();
    $(`#${tabId}`).show();
    $('.nav-link').removeClass('active');
    $(element).addClass('active');
};

const showTab = (tabId, element) => showTabContent(tabId, element, 'tab-content');
const showCourseTab = (tabId, element) => showTabContent(tabId, element, 'course-tab-content');
const showDepartmentTab = (tabId, element) => showTabContent(tabId, element, 'department-tab-content');
const showFacultyTab = (tabId, element) => showTabContent(tabId, element, 'faculty-tab-content');
const showStudentTab = (tabId, element) => showTabContent(tabId, element, 'student-tab-content');
const showProfessorTab = (tabId, element) => showTabContent(tabId, element, 'professor-tab-content');

const openModal = (modalId, inputId, value) => {
    document.getElementById(inputId).value = value;
    document.getElementById(modalId).style.display = 'block';
};

const closeModal = (modalId) => {
    document.getElementById(modalId).style.display = 'none';
};

const openDeleteModal = (studentId) => openModal('deleteModal', 'delete_student_id', studentId);
const closeDeleteModal = () => closeModal('deleteModal');
const openDeleteDepartmentModal = (departmentId) => openModal('deleteDepartmentModal', 'delete_department_id', departmentId);
const closeDeleteDepartmentModal = () => closeModal('deleteDepartmentModal');
const openViewDepartmentModal = async (departmentId) => {
    openModal('viewDepartmentModal', 'department_id_enroll', departmentId);
    document.getElementById('set_department_id').value = departmentId;

    const fetchStudents = async (url, data) => {
        try {
            const response = await $.ajax({ type: "POST", url, data });
            return JSON.parse(response);
        } catch (error) {
            handleAjaxError(error);
        }
    };

    const students = await fetchStudents("admin.php", { fetch_department_students: true, department_id: departmentId });
    const tbody = document.querySelector('#viewDepartmentModal tbody');
    tbody.innerHTML = '';
    students.forEach(student => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${student.StudentID}</td><td>${student.StudentName}</td>`;
        tbody.appendChild(row);
    });

    const remainingStudents = await fetchStudents("admin.php", { fetch_remaining_students: true, department_id: departmentId });
    const select = document.getElementById('student_id_department');
    select.innerHTML = '<option value="">Select Student</option>';
    remainingStudents.forEach(student => {
        const option = document.createElement('option');
        option.value = student.StudentID;
        option.textContent = student.StudentName;
        select.appendChild(option);
    });
};

const closeViewDepartmentModal = () => closeModal('viewDepartmentModal');
const openSetDepartmentHeadModal = async (departmentId) => {
    if (!departmentId) {
        showMessage('Invalid department ID', 'error');
        return;
    }

    openModal('setDepartmentHeadModal', 'set_department_id', departmentId);

    try {
        const response = await $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: {
                fetch_professors: true,
                department_id: departmentId
            }
        });

        const professors = JSON.parse(response);
        const select = document.getElementById('head_professor_id_dep');
        select.innerHTML = '<option value="">Select Professor</option>';

        if (professors && professors.length > 0) {
            professors.forEach(professor => {
                const option = document.createElement('option');
                option.value = professor.ProfessorID;
                option.textContent = professor.ProfessorName;
                select.appendChild(option);
            });
        } else {
            showMessage('No professors found for this department', 'warning');
        }
    } catch (error) {
        console.error('Error fetching professors:', error);
        showMessage('Failed to load professors', 'error');
    }
};
const closeSetDepartmentHeadModal = () => closeModal('setDepartmentHeadModal');
const openDeleteCourseModal = (courseId) => openModal('deleteCourseModal', 'delete_course_id', courseId);
const closeDeleteCourseModal = () => closeModal('deleteCourseModal');
const openViewCourseModal = async (courseId) => {
    openModal('viewCourseModal', 'course_id_enroll', courseId);

    const fetchStudents = async (url, data) => {
        try {
            const response = await $.ajax({ type: "POST", url, data });
            return JSON.parse(response);
        } catch (error) {
            handleAjaxError(error);
        }
    };

    const students = await fetchStudents("admin.php", { fetch_course_students: true, course_id: courseId });
    const tbody = document.querySelector('#viewCourseModal tbody');
    tbody.innerHTML = '';
    students.forEach(student => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${student.StudentID}</td><td>${student.StudentName}</td><td>${student.EnrollmentDate}</td><td>${student.Grade}</td>`;
        tbody.appendChild(row);
    });

    const remainingStudents = await fetchStudents("admin.php", { fetch_remaining_course_students: true, course_id: courseId });
    const select = document.getElementById('student_id_course');
    select.innerHTML = '<option value="">Select Student</option>';
    remainingStudents.forEach(student => {
        const option = document.createElement('option');
        option.value = student.StudentID;
        option.textContent = student.StudentName;
        select.appendChild(option);
    });
};

const closeViewCourseModal = () => closeModal('viewCourseModal');
const openDeleteFacultyModal = (facultyId) => openModal('deleteFacultyModal', 'delete_faculty_id', facultyId);
const closeDeleteFacultyModal = () => closeModal('deleteFacultyModal');
const openViewFacultyModal = async (facultyId) => {
    openModal('viewFacultyModal', 'faculty_id', facultyId);
    document.getElementById('set_faculty_id').value = facultyId;

    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { fetch_faculty_departments: true, faculty_id: facultyId }
        });
        const departments = JSON.parse(response);
        const tbody = document.querySelector('#viewFacultyModal tbody');
        tbody.innerHTML = '';
        departments.forEach(department => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${department.DepartmentID}</td><td>${department.DepartmentName}</td>`;
            tbody.appendChild(row);
        });
    } catch (error) {
        handleAjaxError(error);
    }
};

const closeViewFacultyModal = () => closeModal('viewFacultyModal');
const openSetFacultyHeadModal = (facultyId) => openModal('setFacultyHeadModal', 'set_faculty_id', facultyId);
const closeSetFacultyHeadModal = () => closeModal('setFacultyHeadModal');
const openDeleteProfessorModal = (professorId) => openModal('deleteProfessorModal', 'delete_professor_id', professorId);
const closeDeleteProfessorModal = () => closeModal('deleteProfessorModal');

const showMessage = (message, type = 'success') => {
    const messageContainer = document.getElementById('message-container');
    const messageBox = document.createElement('div');
    messageBox.classList.add('message-box', 'show');
    if (type === 'error') {
        messageBox.style.borderLeftColor = 'red';
    }
    const closeButton = document.createElement('span');
    closeButton.innerHTML = '×';
    closeButton.classList.add('close-message');
    closeButton.addEventListener('click', () => messageBox.remove());
    const timestamp = document.createElement('span');
    timestamp.classList.add('timestamp');
    timestamp.innerText = new Date().toLocaleTimeString();

    messageBox.innerHTML = `<div>${message}</div>`;
    messageBox.appendChild(closeButton);
    messageBox.appendChild(timestamp);
    messageContainer.appendChild(messageBox);

    setTimeout(() => messageBox.remove(), 5000);
};

const showJsonResponse = (message, type = 'success') => {
    const responseContainer = document.createElement('div');
    responseContainer.classList.add('json-response', type === 'error' ? 'error' : '');
    responseContainer.innerText = message;
    document.body.appendChild(responseContainer);
    responseContainer.style.display = 'block';

    setTimeout(() => {
        responseContainer.style.display = 'none';
        document.body.removeChild(responseContainer);
    }, 5000);
};

const handleAjaxResponse = (response) => {
    const jsonResponse = JSON.parse(response);
    showJsonResponse(jsonResponse.message, jsonResponse.status === 'success' ? 'success' : 'error');
    refreshData();
};

const handleAjaxError = (xhr, status, error) => {
    showJsonResponse('An error occurred: ' + error, 'error');
    console.error("Error:", error);
};

const refreshCounters = async () => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { fetch_counts: true }
        });
        const counts = JSON.parse(response);
        document.querySelector('.card-title.faculty-count').textContent = counts.faculty_count;
        document.querySelector('.card-title.department-count').textContent = counts.department_count;
        document.querySelector('.card-title.course-count').textContent = counts.course_count;
        document.querySelector('.card-title.professor-count').textContent = counts.professor_count;
    } catch (error) {
        handleAjaxError(error);
    }
};

const refreshData = async () => {
    const fetchData = async (url, data) => {
        try {
            const response = await $.ajax({ type: "POST", url, data });
            return JSON.parse(response);
        } catch (error) {
            handleAjaxError(error);
        }
    };

    const students = await fetchData("admin.php", { fetch_students_with_details: true });
    const studentTbody = document.querySelector('#studentListTab tbody');
    studentTbody.innerHTML = '';
    students.forEach(student => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${student.StudentID}</td><td>${student.StudentName}</td><td>${student.Email}</td><td>${student.DepartmentName}</td><td>${student.CourseName || 'Not Enrolled'}</td><td>${student.Grade || 'N/A'}</td>`;
        studentTbody.appendChild(row);
    });

    const courses = await fetchData("admin.php", { fetch_courses_with_number_of_students: true });
    const courseTbody = document.querySelector('#currentCoursesTab tbody');
    courseTbody.innerHTML = '';
    courses.forEach(course => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${course.CourseID}</td><td>${course.CourseName}</td><td>${course.CourseCredits}</td><td>${course.ProfessorName}</td><td>${course.NumberOfStudents}</td>`;
        courseTbody.appendChild(row);
    });

    const departments = await fetchData("admin.php", { fetch_departments_with_details: true });
    const departmentTbody = document.querySelector('#currentDepartmentsTab tbody');
    departmentTbody.innerHTML = '';
    departments.forEach(department => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${department.DepartmentID}</td><td>${department.DepartmentName}</td><td>${department.HeadProfessor}</td><td>${department.NumberOfStudents}</td><td>${department.FacultyName}</td><td>${department.NumberOfCourses}</td>`;
        departmentTbody.appendChild(row);
    });

    const faculties = await fetchData("admin.php", { fetch_faculties_with_departments: true });
    const facultyTbody = document.querySelector('#currentFacultiesTab tbody');
    facultyTbody.innerHTML = '';
    faculties.forEach(faculty => {
        const row = document.createElement('tr');
        row.innerHTML = `<td>${faculty.FacultyID}</td><td>${faculty.FacultyName}</td><td>${faculty.ProfessorName}</td><td>${faculty.NumberOfDepartment}</td><td>${faculty.NumberOfStudent}</td>`;
        facultyTbody.appendChild(row);
    });

    refreshCounters();
    await updateChartData();
};

const enrollStudentToDepartment = async (studentId, departmentId) => {
    if (!studentId || !departmentId) {
        showMessage('Please select a student and a department.', 'error');
        return;
    }

    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { enroll_student_department: true, student_id_department: studentId, department_id_enroll: departmentId }
        });
        const jsonResponse = JSON.parse(response);
        showMessage(jsonResponse.message, jsonResponse.status === 'success' ? 'success' : 'error');
        if (jsonResponse.status === 'success') refreshData();
    } catch (error) {
        showMessage('An error occurred: ' + error, 'error');
    }
};

const addCourse = async (courseName, courseCredits, departmentId) => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { add_course: true, course_name: courseName, course_code: courseCredits, department_id: departmentId }
        });
        handleAjaxResponse(response);
    } catch (error) {
        handleAjaxError(error);
    }
};

const addDepartment = async (departmentName, facultyId) => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { add_department: true, department_name: departmentName, faculty_id: facultyId }
        });
        handleAjaxResponse(response);
    } catch (error) {
        handleAjaxError(error);
    }
};

const addFaculty = async (facultyName, headProfessorId) => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { add_faculty: true, faculty_name: facultyName, head_professor_id: headProfessorId }
        });
        handleAjaxResponse(response);
    } catch (error) {
        handleAjaxError(error);
    }
};

const addStudent = async (firstName, lastName, email, departmentId) => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { add_student: true, first_name: firstName, last_name: lastName, email: email, department_id: departmentId }
        });
        handleAjaxResponse(response);
    } catch (error) {
        handleAjaxError(error);
    }
};

$(document).on('submit', 'form', async function (event) {
    event.preventDefault();
    const formData = $(this).serialize();

    // Special handling for set department head form
    if (this.querySelector('[name="set_department_head"]')) {
        try {
            const response = await $.ajax({
                type: "POST",
                url: "admin.php",
                data: formData
            });
            const result = JSON.parse(response);
            showMessage(result.message, result.status);
            if (result.status === 'success') {
                closeSetDepartmentHeadModal();
                refreshData();
            }
        } catch (error) {
            handleAjaxError(error);
        }
        return;
    }

    // Handle other forms
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: formData
        });
        handleAjaxResponse(response);
    } catch (error) {
        handleAjaxError(error);
    }
});

$(document).on('submit', 'form.update-grade-form', async function (event) {
    event.preventDefault();
    const formData = $(this).serialize();
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: formData
        });
        console.log("Response from server:", response);
        const jsonResponse = JSON.parse(response);
        showMessage(jsonResponse.message, jsonResponse.status === 'success' ? 'success' : 'error');
        if (jsonResponse.status === 'success') refreshData();
    } catch (error) {
        showMessage('An error occurred: ' + error, 'error');
    }
});

$(document).on('click', '#enroll-student-department-button', () => {
    const studentId = $('#student_id_department').val();
    const departmentId = $('#department_id_enroll').val();
    enrollStudentToDepartment(studentId, departmentId);
});
const fetchRemainingProfessors = async (departmentId) => {
    try {
        const response = await $.ajax({
            url: 'admin.php',
            type: 'POST',
            data: { fetch_remaining_professors: true, department_id: departmentId }
        });
        const professors = JSON.parse(response);
        const select = document.getElementById('head_professor_id_dep');
        select.innerHTML = '<option value="">Select Professor</option>';
        professors.forEach(professor => {
            const option = document.createElement('option');
            option.value = professor.ProfessorID;
            option.textContent = professor.ProfessorName;
            select.appendChild(option);
        });
    } catch (error) {
        handleAjaxError(error);
    }
};

// Dashboard Search
document.getElementById('dashboardSearch').addEventListener('input', function (e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.dashboard-card').forEach(card => {
        const type = card.dataset.type;
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(searchTerm) ? 'block' : 'none';
    });
});

// Show Detailed Statistics
const showDetailedStats = async (type) => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { fetch_detailed_stats: true, stats_type: type }
        });
        const modal = document.getElementById('detailedStatsModal');
        const content = document.getElementById('detailedStatsContent');
        content.innerHTML = response;
        modal.style.display = 'block';
        initializeCharts();
    } catch (error) {
        handleAjaxError(error);
    }
};

const closeDetailedStats = () => {
    document.getElementById('detailedStatsModal').style.display = 'none';
};

// Initialize Charts
const initializeCharts = () => {
    const ctx = document.getElementById('statisticsChart').getContext('2d');
    if (window.dashboardChart) {
        window.dashboardChart.destroy();
    }

    const gradientStudents = ctx.createLinearGradient(0, 0, 0, 400);
    gradientStudents.addColorStop(0, 'rgba(75, 192, 192, 0.5)');
    gradientStudents.addColorStop(1, 'rgba(75, 192, 192, 0)');

    const gradientCourses = ctx.createLinearGradient(0, 0, 0, 400);
    gradientCourses.addColorStop(0, 'rgba(255, 99, 132, 0.5)');
    gradientCourses.addColorStop(1, 'rgba(255, 99, 132, 0)');

    const gradientProfessors = ctx.createLinearGradient(0, 0, 0, 400);
    gradientProfessors.addColorStop(0, 'rgba(153, 102, 255, 0.5)');
    gradientProfessors.addColorStop(1, 'rgba(153, 102, 255, 0)');

    const gradientDepartments = ctx.createLinearGradient(0, 0, 0, 400);
    gradientDepartments.addColorStop(0, 'rgba(255, 206, 86, 0.5)');
    gradientDepartments.addColorStop(1, 'rgba(255, 206, 86, 0)');

    const gradientFaculties = ctx.createLinearGradient(0, 0, 0, 400);
    gradientFaculties.addColorStop(0, 'rgba(54, 162, 235, 0.5)');
    gradientFaculties.addColorStop(1, 'rgba(54, 162, 235, 0)');

    window.dashboardChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Students',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: gradientStudents,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(75, 192, 192)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(75, 192, 192)'
                },
                {
                    label: 'Courses',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: gradientCourses,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(255, 99, 132)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(255, 99, 132)'
                },
                {
                    label: 'Professors',
                    data: [],
                    borderColor: 'rgb(153, 102, 255)',
                    backgroundColor: gradientProfessors,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(153, 102, 255)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(153, 102, 255)'
                },
                {
                    label: 'Departments',
                    data: [],
                    borderColor: 'rgb(255, 206, 86)',
                    backgroundColor: gradientDepartments,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(255, 206, 86)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(255, 206, 86)'
                },
                {
                    label: 'Faculties',
                    data: [],
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: gradientFaculties,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(54, 162, 235)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(54, 162, 235)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Academic Growth Trends',
                    font: {
                        size: 18,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.7)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#fff',
                    borderWidth: 1,
                    padding: 12,
                    boxPadding: 6,
                    usePointStyle: true,
                    callbacks: {
                        label: function (context) {
                            return `${context.dataset.label}: ${context.parsed.y}`;
                        },
                        afterLabel: function (context) {
                            const month = context.label;
                            const value = context.parsed.y;
                            return `In ${month}, there were ${value} ${context.dataset.label.toLowerCase()}.`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        callback: function (value) {
                            return value.toFixed(0);
                        }
                    }
                }
            },
            animations: {
                tension: {
                    duration: 1000,
                    easing: 'linear'
                }
            }
        }
    });

    // Fetch and update chart data
    updateChartData();
};

const updateChartData = async () => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { fetch_chart_data: true }
        });
        const data = JSON.parse(response);

        if (window.dashboardChart) {
            window.dashboardChart.data.datasets[0].data = data.students;
            window.dashboardChart.data.datasets[1].data = data.courses;
            window.dashboardChart.data.datasets[2].data = data.professors;
            window.dashboardChart.data.datasets[3].data = data.departments;
            window.dashboardChart.data.datasets[4].data = data.faculties;
            window.dashboardChart.update();
        }
    } catch (error) {
        handleAjaxError(error);
    }
};

// Load Recent Activities
const loadRecentActivities = async () => {
    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: { fetch_recent_activities: true }
        });
        document.getElementById('recentActivities').innerHTML = response;
    } catch (error) {
        handleAjaxError(error);
    }
};

// Enhanced Dashboard Functions
const initializeDashboard = async () => {
    await Promise.all([
        initializeCharts(),
        initializeDonutCharts(),
        loadRecentActivities(),
        setupDashboardFilters(),
        initializeDataTables(),
        setupRealTimeUpdates()
    ]);
};

const setupRealTimeUpdates = () => {
    const updateInterval = 30000; // 30 seconds
    setInterval(async () => {
        await Promise.all([
            refreshCounters(),
            updateCharts(),
            loadRecentActivities()
        ]);
    }, updateInterval);
};

const initializeDataTables = () => {
    $('.dashboard-table').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    });
};

const setupDashboardFilters = () => {
    const filterForm = document.createElement('form');
    filterForm.className = 'dashboard-filters mb-4';
    filterForm.innerHTML = `
        <div class="row">
            <div class="col-md-3">
                <select class="form-select" id="dateFilter">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="departmentFilter">
                    <option value="all">All Departments</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="typeFilter">
                    <option value="all">All Types</option>
                    <option value="students">Students</option>
                    <option value="courses">Courses</option>
                    <option value="professors">Professors</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
            </div>
        </div>
    `;
    document.querySelector('#statistics').prepend(filterForm);
};

const applyFilters = async () => {
    const dateFilter = document.getElementById('dateFilter').value;
    const departmentFilter = document.getElementById('departmentFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;

    try {
        const response = await $.ajax({
            type: "POST",
            url: "admin.php",
            data: {
                apply_filters: true,
                date_filter: dateFilter,
                department_filter: departmentFilter,
                type_filter: typeFilter
            }
        });
        updateDashboardData(JSON.parse(response));
    } catch (error) {
        handleAjaxError(error);
    }
};

const updateDashboardData = (data) => {
    updateCharts(data.chartData);
    updateCounters(data.counts);
    updateTables(data.tableData);
};

const updateCharts = (chartData = null) => {
    const ctx = document.getElementById('statisticsChart').getContext('2d');
    if (!window.dashboardChart) {
        window.dashboardChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Students',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                },
                {
                    label: 'Courses',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Growth Trends' },
                    legend: { position: 'bottom' }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    if (chartData) {
        window.dashboardChart.data = chartData;
        window.dashboardChart.update();
    }
};

const initializeDonutCharts = () => {
    // Department Distribution
    const deptCtx = document.getElementById('departmentDistribution').getContext('2d');
    new Chart(deptCtx, {
        type: 'doughnut',
        data: {
            labels: ['Science', 'Engineering', 'Arts', 'Business'],
            datasets: [{
                data: [30, 25, 20, 25],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Student Performance
    const perfCtx = document.getElementById('studentPerformance').getContext('2d');
    new Chart(perfCtx, {
        type: 'doughnut',
        data: {
            labels: ['A', 'B', 'C', 'D', 'F'],
            datasets: [{
                data: [35, 30, 20, 10, 5],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(255, 159, 64, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Enrollment Trends
    const trendCtx = document.getElementById('enrollmentTrends').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'New Enrollments',
                data: [65, 59, 80, 81, 56, 55],
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.4,
                fill: true,
                backgroundColor: 'rgba(75, 192, 192, 0.2)'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
};
