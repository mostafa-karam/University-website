<?php
ob_start();
session_start();
include("admin_conect.php");

// Function to safely get POST data
function getPostValue($key, $default = null)
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

// Function to handle errors (now with more detail)
function handleException(Exception $e)
{
    $errorDetails = "[" . date('Y-m-d H:i:s') . "] Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . ". Trace: " . $e->getTraceAsString();
    error_log($errorDetails);
    return "An unexpected error occurred. Please contact support. Error Logged.";
}

// Function to execute SQL queries and handle results
function executeQuery($conn, $query, $params = [], $fetchMode = PDO::FETCH_ASSOC)
{
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll($fetchMode);
    } catch (PDOException $e) {
        handleException($e);
        return [];
    }
}

// Function to handle SQL operations and return JSON response
function handleOperationJson($conn, $query, $params, $successMessage, $errorMessage)
{
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => $successMessage]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $errorMessage]);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => handleException($e)]);
    }
    exit;
}

// Function to fetch count of tables
function fetchCounts($conn)
{
    $queryCounts = "SELECT 
        (SELECT COUNT(*) FROM Faculty) AS faculty_count,
        (SELECT COUNT(*) FROM Department) AS department_count,
        (SELECT COUNT(*) FROM Course) AS course_count,
        (SELECT COUNT(*) FROM Professor) AS professor_count,
        (SELECT COUNT(*) FROM Student) AS student_count";
    return executeQuery($conn, $queryCounts)[0];
}

// Function to fetch details of students
function fetchStudentsWithDetails($conn)
{
    $query = "
        SELECT 
            S.StudentID,
            S.StudentName,
            S.Email,
            S.DepartmentID,
            D.DepartmentName,
            C.CourseName,
            COALESCE(E.Grade, NULL) AS Grade,
            COALESCE(E.EnrollmentID, NULL) AS EnrollmentID
        FROM Student S
        JOIN Department D ON S.DepartmentID = D.DepartmentID
        LEFT JOIN Enrollment E ON S.StudentID = E.StudentID
        LEFT JOIN Course C ON E.CourseID = C.CourseID";
    return executeQuery($conn, $query);
}

// Function to get courses and professors
function fetchCoursesWithProfessor($conn)
{
    $query = "
        SELECT 
            C.CourseID,
            C.CourseName,
            C.CourseCredits,
            P.ProfessorName
        FROM Course C
        JOIN Professor P ON C.ProfessorID = P.ProfessorID";
    return executeQuery($conn, $query);
}

// Function to get enrolled students and courses
function fetchEnrolledStudentsAndCourses($conn)
{
    $query = "
        SELECT 
            S.StudentID,
            S.StudentName,
            C.CourseName,
            C.CourseID,
            E.EnrollmentDate,
            E.EnrollmentID,
            COALESCE(E.Grade, NULL) AS Grade
        FROM Enrollment E
        JOIN Student S ON E.StudentID = S.StudentID
        JOIN Course C ON E.CourseID = C.CourseID";
    return executeQuery($conn, $query);
}

// Function to get courses with number of students
function fetchCoursesWithNumberOfStudents($conn)
{
    $query = "
        SELECT 
            C.CourseName,
            C.CourseID,
            COUNT(E.StudentID) AS NumberOfStudents
        FROM Course C
        LEFT JOIN Enrollment E ON C.CourseID = E.CourseID
        GROUP BY C.CourseID, C.CourseName";
    return executeQuery($conn, $query);
}

// Function to get faculty and departments
function fetchFacultiesWithDepartments($conn)
{
    $query = "
        SELECT 
            F.FacultyID,
            F.FacultyName,
            P.ProfessorName,
            P.ProfessorID,
            COUNT(DISTINCT D.DepartmentID) AS NumberOfDepartment,
            (SELECT COUNT(DISTINCT S.StudentID) 
            FROM Student S 
            JOIN Department DD ON S.DepartmentID = DD.DepartmentID 
            WHERE DD.FacultyID = F.FacultyID) AS NumberOfStudent
        FROM Faculty F
        LEFT JOIN Department D ON F.FacultyID = D.FacultyID
        LEFT JOIN Professor P ON F.HeadProfessorID = P.ProfessorID
        GROUP BY F.FacultyID, F.FacultyName, P.ProfessorName, P.ProfessorID";
    return executeQuery($conn, $query);
}

// Function to get department details
function fetchDepartmentsWithDetails($conn)
{
    $query = "
        SELECT 
            D.DepartmentID,
            D.DepartmentName,
            P.ProfessorName AS HeadProfessor,
            P.ProfessorID,
            F.FacultyName,
            F.FacultyID,
            COUNT(DISTINCT S.StudentID) AS NumberOfStudents,
            (SELECT COUNT(DISTINCT CC.CourseID) 
            FROM Course CC 
            LEFT JOIN Professor PP ON CC.ProfessorID = PP.ProfessorID 
            WHERE PP.DepartmentID = D.DepartmentID) AS NumberOfCourses
        FROM Department D
        LEFT JOIN Student S ON D.DepartmentID = S.DepartmentID
        LEFT JOIN Faculty F ON D.FacultyID = F.FacultyID
        LEFT JOIN Professor P ON D.HeadProfessorID = P.ProfessorID
        GROUP BY D.DepartmentID, D.DepartmentName, P.ProfessorName, P.ProfessorID, F.FacultyName, F.FacultyID";
    return executeQuery($conn, $query);
}

// Function to get professors details
function fetchProfessorsDetails($conn)
{
    $query = "
        SELECT 
            P.ProfessorID,
            P.ProfessorName,
            P.ProfessorTitle,
            D.DepartmentID,
            D.DepartmentName,
            (SELECT COUNT(CourseID) FROM Course WHERE ProfessorID = P.ProfessorID) AS NumberOfCourses
        FROM Professor P
        LEFT JOIN Department D ON P.DepartmentID = D.DepartmentID";
    return executeQuery($conn, $query);
}

// Function to get options for select elements
function fetchOptions($conn, $query, $params = [])
{
    return executeQuery($conn, $query, $params);
}

// Initialize messages
$errorMessage = "";
$successMessage = "";

// --- Fetch Data ---
try {
    $countsResult = fetchCounts($conn);
    $facultyCount = $countsResult['faculty_count'] ?? 0;
    $departmentCount = $countsResult['department_count'] ?? 0;
    $courseCount = $countsResult['course_count'] ?? 0;
    $professorCount = $countsResult['professor_count'] ?? 0;
    $studentCount = $countsResult['student_count'] ?? 0;

    $studentsWithDetails = fetchStudentsWithDetails($conn);
    $coursesWithProfessor = fetchCoursesWithProfessor($conn);
    $facultiesWithDepartments = fetchFacultiesWithDepartments($conn);
    $departmentsWithDetails = fetchDepartmentsWithDetails($conn);
    $professorsDetails = fetchProfessorsDetails($conn);

    $departmentOptions = fetchOptions($conn, "SELECT DepartmentID, DepartmentName FROM Department");
    $allCoursesResult = fetchOptions($conn, "SELECT CourseID, CourseName FROM Course");
    $allStudentsResult = fetchOptions($conn, "SELECT StudentID, StudentName FROM Student");
    $allProfessorsResult = fetchOptions($conn, "SELECT ProfessorID, ProfessorName FROM Professor");
} catch (PDOException $e) {
    $errorMessage = handleException($e);
}

// --- Handle POST Operations ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Function to handle SQL operations and show messages
    function handleOperation($conn, $query, $params, $successMessage, $errorMessage)
    {
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            if ($stmt->rowCount() > 0) {
                echo "<script>window.onload = function() { showMessage('$successMessage', 'success'); window.location.href = 'admin.php'; }</script>";
            } else {
                echo "<script>window.onload = function() { showMessage('$errorMessage', 'error'); }</script>";
            }
        } catch (PDOException $e) {
            echo "<script>window.onload = function() { showMessage('" . handleException($e) . "', 'error'); }</script>";
        }
    }

    // Updated Grade
    if (isset($_POST['update_grade'])) {
        $enrollmentId = getPostValue('enrollment_id', 0);
        $grade = getPostValue('grade');
        if (is_numeric($enrollmentId) && $enrollmentId > 0 && $grade !== null) {
            $updateGradeQuery = "UPDATE Grades SET Grade = :grade WHERE EnrollmentID = :enrollmentId";
            handleOperation($conn, $updateGradeQuery, ['grade' => $grade, 'enrollmentId' => $enrollmentId], 'Grade updated successfully.', 'Failed to update grade or no changes were made.');
        } else {
            echo "<script>window.onload = function() { showMessage('Enrollment ID and Grade are required.', 'error'); }</script>";
        }
    }

    // Add a course
    if (isset($_POST['add_course'])) {
        $courseName = getPostValue('course_name');
        $courseCredits = getPostValue('course_code');
        $departmentId = getPostValue('department_id', 0);
        $headProfessorId = getPostValue('head_professor_id_dep', 0);

        // Validate that the professor belongs to the department
        $queryValidateProfessor = "SELECT COUNT(*) FROM Professor WHERE ProfessorID = :headProfessorId AND DepartmentID = :departmentId";
        $validationResult = executeQuery($conn, $queryValidateProfessor, ['headProfessorId' => $headProfessorId, 'departmentId' => $departmentId]);

        if ($validationResult[0]['COUNT(*)'] == 0) {
            echo "<script>window.onload = function() { showMessage('Invalid professor for the selected department.', 'error'); }</script>";
            exit;
        }

        if ($courseName && is_numeric($courseCredits) && $courseCredits > 0 && is_numeric($departmentId) && $departmentId > 0) {
            $addCourseQuery = "INSERT INTO Course (CourseName, CourseCredits, DepartmentID) VALUES (:courseName, :courseCredits, :departmentId)";
            handleOperation($conn, $addCourseQuery, ['courseName' => $courseName, 'courseCredits' => $courseCredits, 'departmentId' => $departmentId], 'Course added successfully.', 'Failed to add course.');
        } else {
            echo "<script>window.onload = function() { showMessage('Invalid input. Please check all fields. Ensure course credits are a number greater than 0.', 'error'); }</script>";
        }
    }

    // Delete a course
    if (isset($_POST['delete_course'])) {
        $courseId = getPostValue('course_id', 0);
        if (is_numeric($courseId) && $courseId > 0) {
            $deleteCourseQuery = "DELETE FROM Course WHERE CourseID = :courseId";
            handleOperation($conn, $deleteCourseQuery, ['courseId' => $courseId], 'Course deleted successfully.', 'Failed to delete course or no course with that ID.');
        } else {
            echo "<script>window.onload = function() { showMessage('Invalid Course ID provided.', 'error'); }</script>";
        }
    }

    // Add a department
    if (isset($_POST['add_department'])) {
        $departmentName = getPostValue('department_name');
        $facultyId = getPostValue('faculty_id', 0);

        if ($departmentName && is_numeric($facultyId) && $facultyId > 0) {
            $addDepartmentQuery = "INSERT INTO Department (DepartmentName, FacultyID) VALUES (:departmentName, :facultyId)";
            handleOperation($conn, $addDepartmentQuery, ['departmentName' => $departmentName, 'facultyId' => $facultyId], 'Department added successfully.', 'Failed to add department.');
        } else {
            echo "<script>window.onload = function() { showMessage('Department name and faculty ID are required.', 'error'); }</script>";
        }
    }

    // Delete a department
    if (isset($_POST['delete_department'])) {
        $departmentId = getPostValue('department_id', 0);
        if (is_numeric($departmentId) && $departmentId > 0) {
            $deleteDepartmentQuery = "DELETE FROM Department WHERE DepartmentID = :departmentId";
            handleOperation($conn, $deleteDepartmentQuery, ['departmentId' => $departmentId], 'Department deleted successfully.', 'Failed to delete department or no department with that ID.');
        } else {
            echo "<script>window.onload = function() { showMessage('Invalid Department ID provided.', 'error'); }</script>";
        }
    }

    // Add a faculty
    if (isset($_POST['add_faculty'])) {
        $facultyName = getPostValue('faculty_name');
        $headProfessorId = getPostValue('head_professor_id', 0);

        if ($facultyName && is_numeric($headProfessorId) && $headProfessorId > 0) {
            $addFacultyQuery = "INSERT INTO Faculty (FacultyName, HeadProfessorID) VALUES (:facultyName, :headProfessorId)";
            handleOperation($conn, $addFacultyQuery, ['facultyName' => $facultyName, 'headProfessorId' => $headProfessorId], 'Faculty added successfully.', 'Failed to add faculty.');
        } else {
            echo "<script>window.onload = function() { showMessage('Invalid input. Please check all fields.', 'error'); }</script>";
        }
    }

    // Delete a faculty
    if (isset($_POST['delete_faculty'])) {
        $facultyId = getPostValue('faculty_id', 0);
        if (is_numeric($facultyId) && $facultyId > 0) {
            $deleteFacultyQuery = "DELETE FROM Faculty WHERE FacultyID = :facultyId";
            handleOperation($conn, $deleteFacultyQuery, ['facultyId' => $facultyId], 'Faculty deleted successfully.', 'Failed to delete faculty or no faculty with that ID.');
        } else {
            echo "<script>window.onload = function() { showMessage('Invalid faculty ID provided.', 'error'); }</script>";
        }
    }

    // Add a student
    if (isset($_POST['add_student'])) {
        $firstName = getPostValue('first_name');
        $lastName = getPostValue('last_name');
        $email = getPostValue('email');
        $departmentId = getPostValue('department_id', 0);
        if ($firstName && $lastName && filter_var($email, FILTER_VALIDATE_EMAIL) && is_numeric($departmentId) && $departmentId > 0) {
            $addStudentQuery = "INSERT INTO Student (StudentName, Email, DepartmentID) VALUES (:studentName, :email, :departmentId)";
            handleOperation($conn, $addStudentQuery, ['studentName' => $firstName . ' ' . $lastName, 'email' => $email, 'departmentId' => $departmentId], 'Student added successfully.', 'Failed to add student.');
        } else {
            echo "<script>window.onload = function() { showMessage('All fields are required and valid.', 'error'); }</script>";
        }
    }

    // Delete a student
    if (isset($_POST['delete_student'])) {
        $studentId = getPostValue('student_id', 0);
        if (is_numeric($studentId) && $studentId > 0) {
            try {
                // Delete child records first
                $deleteEnrollmentQuery = "DELETE FROM Enrollment WHERE StudentID = :studentId";
                $conn->prepare($deleteEnrollmentQuery)->execute(['studentId' => $studentId]);
                $deleteStudentQuery = "DELETE FROM Student WHERE StudentID = :studentId";
                $stmt = $conn->prepare($deleteStudentQuery);
                $stmt->execute(['studentId' => $studentId]);
                if ($stmt->rowCount() > 0) {
                    echo "<script>window.onload = function() { showMessage('Student deleted successfully.', 'success'); window.location.href = 'admin.php'; }</script>";
                } else {
                    echo "<script>window.onload = function() { showMessage('Failed to delete student or no student with that ID.', 'error'); }</script>";
                }
            } catch (PDOException $e) {
                echo "<script>window.onload = function() { showMessage('" . handleException($e) . "', 'error'); }</script>";
            }
        } else {
            echo "<script>window.onload = function() { showMessage('Invalid Student ID provided.', 'error'); }</script>";
        }
    }

    // Enroll a student
    if (isset($_POST['enroll_student'])) {
        $studentId = getPostValue('student_id', 0);
        $courseId = getPostValue('course_id', 0);
        $enrollmentDate = date('Y-m-d H:i:s');
        if (is_numeric($studentId) && $studentId > 0 && is_numeric($courseId) && $courseId > 0) {
            $enrollStudentQuery = "INSERT INTO Enrollment (StudentID, CourseID, EnrollmentDate) VALUES (:studentId, :courseId, :enrollmentDate)";
            handleOperation($conn, $enrollStudentQuery, ['studentId' => $studentId, 'courseId' => $courseId, 'enrollmentDate' => $enrollmentDate], 'Student enrolled successfully.', 'Failed to enroll student.');
        } else {
            echo "<script>window.onload = function() { showMessage('Student ID and Course ID are required.', 'error'); }</script>";
        }
    }

    // Enroll a student to department
    if (isset($_POST['enroll_student_department'])) {
        $studentId = getPostValue('student_id_department', 0);
        $departmentId = getPostValue('department_id_enroll', 0);
        if (is_numeric($studentId) && $departmentId > 0) {
            $enrollStudentQuery = "UPDATE Student SET DepartmentID = :departmentId WHERE StudentID = :studentId";
            handleOperationJson($conn, $enrollStudentQuery, ['studentId' => $studentId, 'departmentId' => $departmentId], 'Student enrolled to department successfully.', 'Failed to enroll student to department.');
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Student ID and Department ID are required.']);
            exit;
        }
    }

    // Set department head
    if (isset($_POST['set_department_head'])) {
        $departmentId = getPostValue('department_id_head', 0);
        $headProfessorId = getPostValue('head_professor_id_dep', 0);

        if (!$departmentId || !$headProfessorId) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Department ID and Head Professor ID are required.'
            ]);
            exit;
        }

        try {
            $conn->beginTransaction();

            // Update the department head
            $setDepartmentHeadQuery = "
                UPDATE Department 
                SET HeadProfessorID = :headProfessorId 
                WHERE DepartmentID = :departmentId";

            $stmt = $conn->prepare($setDepartmentHeadQuery);
            $success = $stmt->execute([
                'headProfessorId' => $headProfessorId,
                'departmentId' => $departmentId
            ]);

            if ($success) {
                $conn->commit();
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Department head updated successfully.'
                ]);
            } else {
                $conn->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to update department head.'
                ]);
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Set faculty head
    if (isset($_POST['set_faculty_head'])) {
        $facultyId = getPostValue('faculty_id_head', 0);
        $headProfessorId = getPostValue('head_professor_id_fac', 0);
        if (is_numeric($headProfessorId) && $headProfessorId > 0 && is_numeric($facultyId) && $facultyId > 0) {
            $setFacultyHeadQuery = "UPDATE Faculty SET HeadProfessorID = :headProfessorId WHERE FacultyID = :facultyId";
            handleOperation($conn, $setFacultyHeadQuery, ['headProfessorId' => $headProfessorId, 'facultyId' => $facultyId], 'Faculty head added successfully.', 'Failed to add head of faculty.');
        } else {
            echo "<script>window.onload = function() { showMessage('Faculty ID and Head Professor ID are required.', 'error'); }</script>";
        }
    }

    // Add professor
    if (isset($_POST['add_professor'])) {
        $professorName = getPostValue('professor_name');
        $professorTitle = getPostValue('professor_title');
        $departmentId = getPostValue('department_id', 0);
        if ($professorName && $professorTitle && is_numeric($departmentId) && $departmentId > 0) {
            $addProfessorQuery = "INSERT INTO Professor (ProfessorName, ProfessorTitle, DepartmentID) VALUES (:professorName, :professorTitle, :departmentId)";
            handleOperation($conn, $addProfessorQuery, ['professorName' => $professorName, 'professorTitle' => $professorTitle, 'departmentId' => $departmentId], 'Professor added successfully.', 'Failed to add Professor.');
        } else {
            echo "<script>window.onload = function() { showMessage('Professor Name and Professor Title are required.', 'error'); }</script>";
        }
    }

    // Delete professor
    if (isset($_POST['delete_professor'])) {
        $professorId = getPostValue('professor_id', 0);
        if (is_numeric($professorId) && $professorId > 0) {
            $deleteProfessorQuery = "DELETE FROM Professor WHERE ProfessorID = :professorId";
            handleOperation($conn, $deleteProfessorQuery, ['professorId' => $professorId], 'Professor deleted successfully.', 'Failed to delete professor.');
        } else {
            echo "<script>window.onload = function() { showMessage('Invalid Professor ID provided.', 'error'); }</script>";
        }
    }

    // Fetch students in a department
    if (isset($_POST['fetch_department_students'])) {
        $departmentId = getPostValue('department_id', 0);
        if (is_numeric($departmentId) && $departmentId > 0) {
            $queryDepartmentStudents = "SELECT StudentID, StudentName FROM Student WHERE DepartmentID = :departmentId";
            $students = executeQuery($conn, $queryDepartmentStudents, ['departmentId' => $departmentId]);
            echo json_encode($students);
            exit;
        }
    }

    // Fetch remaining students not in the department
    if (isset($_POST['fetch_remaining_students'])) {
        $departmentId = getPostValue('department_id', 0);
        if (is_numeric($departmentId) && $departmentId > 0) {
            $queryRemainingStudents = "SELECT StudentID, StudentName FROM Student WHERE DepartmentID != :departmentId OR DepartmentID IS NULL";
            $students = executeQuery($conn, $queryRemainingStudents, ['departmentId' => $departmentId]);
            echo json_encode($students);
            exit;
        }
    }

    // Fetch departments in a faculty
    if (isset($_POST['fetch_faculty_departments'])) {
        $facultyId = getPostValue('faculty_id', 0);
        if (is_numeric($facultyId) && $facultyId > 0) {
            $queryFacultyDepartments = "SELECT DepartmentID, DepartmentName FROM Department WHERE FacultyID = :facultyId";
            $departments = executeQuery($conn, $queryFacultyDepartments, ['facultyId' => $facultyId]);
            echo json_encode($departments);
            exit;
        }
    }

    // Fetch students in a course
    if (isset($_POST['fetch_course_students'])) {
        $courseId = getPostValue('course_id', 0);
        if (is_numeric($courseId) && $courseId > 0) {
            $queryCourseStudents = "SELECT S.StudentID, S.StudentName, E.EnrollmentDate, COALESCE(E.Grade, NULL) AS Grade FROM Enrollment E JOIN Student S ON E.StudentID = S.StudentID WHERE E.CourseID = :courseId";
            $students = executeQuery($conn, $queryCourseStudents, ['courseId' => $courseId]);
            echo json_encode($students);
            exit;
        }
    }

    // Fetch remaining students not in the course
    if (isset($_POST['fetch_remaining_course_students'])) {
        $courseId = getPostValue('course_id', 0);
        if (is_numeric($courseId) && $courseId > 0) {
            $queryRemainingCourseStudents = "SELECT StudentID, StudentName FROM Student WHERE StudentID NOT IN (SELECT StudentID FROM Enrollment WHERE CourseID = :courseId)";
            $students = executeQuery($conn, $queryRemainingCourseStudents, ['courseId' => $courseId]);
            echo json_encode($students);
            exit;
        }
    }

    // Fetch professors in a department
    if (isset($_POST['fetch_professors'])) {
        $departmentId = getPostValue('department_id', 0);
        if (is_numeric($departmentId) && $departmentId > 0) {
            $queryProfessors = "
                SELECT DISTINCT P.ProfessorID, P.ProfessorName 
                FROM Professor P 
                WHERE P.DepartmentID = :departmentId";

            $professors = executeQuery($conn, $queryProfessors, ['departmentId' => $departmentId]);
            echo json_encode($professors);
            exit;
        }
    }

    // Fetch remaining professors in a department
    if (isset($_POST['fetch_remaining_professors'])) {
        $departmentId = getPostValue('department_id', 0);
        if (is_numeric($departmentId) && $departmentId > 0) {
            // Modified query to get all professors in the department
            $queryRemainingProfessors = "
                SELECT DISTINCT P.ProfessorID, P.ProfessorName 
                FROM Professor P 
                WHERE P.DepartmentID = :departmentId";

            $professors = executeQuery($conn, $queryRemainingProfessors, ['departmentId' => $departmentId]);
            echo json_encode($professors);
            exit;
        }
    }

    // Add this inside your POST handler section
    if (isset($_POST['fetch_chart_data'])) {
        try {
            // Get monthly counts for the current year
            $year = date('Y');
            $query = "
                SELECT 
                    MONTH(created_at) as month,
                    COUNT(*) as count,
                    'students' as type
                FROM Student
                WHERE YEAR(created_at) = :year
                GROUP BY MONTH(created_at)
                UNION ALL
                SELECT 
                    MONTH(created_at) as month,
                    COUNT(*) as count,
                    'courses' as type
                FROM Course
                WHERE YEAR(created_at) = :year
                GROUP BY MONTH(created_at)
                UNION ALL
                SELECT 
                    MONTH(created_at) as month,
                    COUNT(*) as count,
                    'professors' as type
                FROM Professor
                WHERE YEAR(created_at) = :year
                GROUP BY MONTH(created_at)
                UNION ALL
                SELECT 
                    MONTH(created_at) as month,
                    COUNT(*) as count,
                    'departments' as type
                FROM Department
                WHERE YEAR(created_at) = :year
                GROUP BY MONTH(created_at)
                UNION ALL
                SELECT 
                    MONTH(created_at) as month,
                    COUNT(*) as count,
                    'faculties' as type
                FROM Faculty
                WHERE YEAR(created_at) = :year
                GROUP BY MONTH(created_at)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute(['year' => $year]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Initialize arrays with zeros for all months
            $chartData = [
                'students' => array_fill(0, 12, 0),
                'courses' => array_fill(0, 12, 0),
                'professors' => array_fill(0, 12, 0),
                'departments' => array_fill(0, 12, 0),
                'faculties' => array_fill(0, 12, 0)
            ];

            // Fill in actual values
            foreach ($results as $row) {
                $month = intval($row['month']) - 1; // Convert to 0-based index
                $chartData[$row['type']][$month] = intval($row['count']);
            }

            echo json_encode($chartData);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['error' => handleException($e)]);
            exit;
        }
    }
}
ob_flush();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/stylesadmin.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
</head>

<body>
    <div id="message-container">
    </div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 d-md-block bg-dark sidebar"> <!-- Adjusted width to col-md-3 -->
                <div class="position-sticky">
                    <h4>Admin Dashboard</h4>
                    <!-- Dark Mode Toggle -->
                    <div class="theme-switch-wrapper">
                        <label class="theme-switch" for="checkbox">
                            <input type="checkbox" id="checkbox" />
                            <div class="slider-round"></div>
                        </label>
                        <em>Enable Dark Mode!</em>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="#statistics" onclick="showTab('statistics', this)" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="#professors" onclick="showTab('professors', this)"><i class="fas fa-user"></i> View Professors</a></li>
                        <li class="nav-item"><a class="nav-link" href="#students" onclick="showTab('students', this)"><i class="fas fa-user-graduate"></i> View Students</a></li>
                        <li class="nav-item"><a class="nav-link" href="#departments" onclick="showTab('departments', this)"><i class="fas fa-building"></i> Manage Departments</a></li>
                        <li class="nav-item"><a class="nav-link" href="#faculties" onclick="showTab('faculties', this)"><i class="fas fa-building"></i> Manage Faculties</a></li>
                        <li class="nav-item"><a class="nav-link" href="#courses" onclick="showTab('courses', this)"><i class="fas fa-book"></i> Manage Courses</a></li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="main-content">
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-9 px-md-4 main-content"> <!-- Adjusted width to col-md-9 -->
                <div id="statistics" class="tab-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Dashboard</h1>
                    </div>
                    <div id="counter-container">
                        <div class="row">
                            <!-- Updated counter cards with added functionality -->
                            <div class="col-md-3">
                                <div class="card mb-3 dashboard-card" data-type="faculty">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        Faculties <i class="fas fa-chalkboard-teacher"></i>
                                        <button class="btn btn-sm btn-link" onclick="showDetailedStats('faculty')">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title faculty-count"><?php echo $facultyCount; ?></h5>
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo ($facultyCount / 100) * 100; ?>%" aria-valuenow="<?php echo $facultyCount; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="card-text">Active Faculties</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card mb-3 dashboard-card" data-type="department">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        Departments <i class="fas fa-building"></i>
                                        <button class="btn btn-sm btn-link" onclick="showDetailedStats('department')">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title department-count"><?php echo $departmentCount; ?></h5>
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo ($departmentCount / 100) * 100; ?>%" aria-valuenow="<?php echo $departmentCount; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="card-text">Active Departments</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card mb-3 dashboard-card" data-type="course">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        Courses <i class="fas fa-book"></i>
                                        <button class="btn btn-sm btn-link" onclick="showDetailedStats('course')">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title course-count"><?php echo $courseCount; ?></h5>
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo ($courseCount / 100) * 100; ?>%" aria-valuenow="<?php echo $courseCount; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="card-text">Active Courses</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card mb-3 dashboard-card" data-type="professor">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        Professors <i class="fas fa-user"></i>
                                        <button class="btn btn-sm btn-link" onclick="showDetailedStats('#professor')">
                                            <i class="fas fa-expand"></i>
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title professor-count"><?php echo $professorCount; ?></h5>
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo ($professorCount / 100) * 100; ?>%" aria-valuenow="<?php echo $professorCount; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <p class="card-text">Active Professors</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="statistics-grid">
                        <!-- Enrollment Trends -->
                        <div class="chart-container">
                            <h3>Enrollment Trends</h3>
                            <canvas id="enrollmentTrends"></canvas>
                        </div>

                        <div class="donut-chart-container">
                            <!-- Department Distribution -->
                            <div class="donut-chart">
                                <h3>Department Distribution</h3>
                                <canvas id="departmentDistribution"></canvas>
                            </div>

                            <!-- Student Performance -->
                            <div class="donut-chart">
                                <h3>Grade Distribution</h3>
                                <canvas id="studentPerformance"></canvas>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Fetch chart data
                            fetch('admin.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'fetch_chart_data=true'
                                })
                                .then(response => response.json())
                                .then(data => {
                                    // Enrollment Trends Chart
                                    const ctxEnrollment = document.getElementById('enrollmentTrends').getContext('2d');
                                    new Chart(ctxEnrollment, {
                                        type: 'line',
                                        data: {
                                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                            datasets: [{
                                                    label: 'Students',
                                                    data: data.students,
                                                    borderColor: 'rgba(75, 192, 192, 1)',
                                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                                    fill: true
                                                },
                                                {
                                                    label: 'Courses',
                                                    data: data.courses,
                                                    borderColor: 'rgba(153, 102, 255, 1)',
                                                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                                                    fill: true
                                                },
                                                {
                                                    label: 'Professors',
                                                    data: data.professors,
                                                    borderColor: 'rgba(255, 159, 64, 1)',
                                                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                                                    fill: true
                                                },
                                                {
                                                    label: 'Departments',
                                                    data: data.departments,
                                                    borderColor: 'rgba(54, 162, 235, 1)',
                                                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                                    fill: true
                                                },
                                                {
                                                    label: 'Faculties',
                                                    data: data.faculties,
                                                    borderColor: 'rgba(255, 99, 132, 1)',
                                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                                    fill: true
                                                }
                                            ]
                                        },
                                        options: {
                                            responsive: true,
                                            scales: {
                                                x: {
                                                    beginAtZero: true
                                                },
                                                y: {
                                                    beginAtZero: true
                                                }
                                            }
                                        }
                                    });

                                    // Department Distribution Chart
                                    const ctxDepartment = document.getElementById('departmentDistribution').getContext('2d');
                                    new Chart(ctxDepartment, {
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

                                    // Student Performance Chart
                                    const ctxPerformance = document.getElementById('studentPerformance').getContext('2d');
                                    new Chart(ctxPerformance, {
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
                                })
                                .catch(error => console.error('Error fetching chart data:', error));
                        });
                    </script>

                    <div class="row mt-4">
                        <!-- Activity Timeline -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3>Recent Activities</h3>
                                </div>
                                <div class="card-body">
                                    <div class="activity-timeline">
                                        <div id="recentActivities">
                                            <!-- Activities will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3>Performance Metrics</h3>
                                </div>
                                <div class="card-body">
                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>Course Completion Rate</span>
                                            <span>75%</span>
                                        </div>
                                        <div class="progress-modern">
                                            <div class="progress-bar" style="width: 75%"></div>
                                        </div>
                                    </div>

                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>Student Satisfaction</span>
                                            <span>88%</span>
                                        </div>
                                        <div class="progress-modern">
                                            <div class="progress-bar" style="width: 88%"></div>
                                        </div>
                                    </div>

                                    <div class="progress-container">
                                        <div class="progress-label">
                                            <span>Faculty Engagement</span>
                                            <span>92%</span>
                                        </div>
                                        <div class="progress-modern">
                                            <div class="progress-bar" style="width: 92%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <!-- Professors Section -->
                <div id="professors" class="tab-content" style="display:none;">
                    <header class="mb-4">
                        <h1>Manage Professors</h1>
                    </header>
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#addProfessorTab" onclick="showTabContent('addProfessorTab', this, 'professor-tab-content')">Add Professor</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#currentProfessorsTab" onclick="showTabContent('currentProfessorsTab', this, 'professor-tab-content')">Current Professors</a>
                        </li>
                    </ul>

                    <!-- Add professor Tab Content -->
                    <div id="addProfessorTab" class="professor-tab-content active">
                        <form method="post">
                            <h3>Add Professor</h3>
                            <label for="professor_name">Professor Name:</label>
                            <input type="text" name="professor_name" id="professor_name" placeholder="Professor Name" required>
                            <label for="professor_title">Professor Title:</label>
                            <input type="text" name="professor_title" id="professor_title" placeholder="Professor Title" required>
                            <label for="department_id_pro">Department:</label>
                            <select name="department_id" id="department_id_pro" required>
                                <option value="">Select Department</option>
                                <?php
                                if ($departmentOptions) {
                                    foreach ($departmentOptions as $department) {
                                        echo '<option value="' . htmlspecialchars($department['DepartmentID']) . '">' . htmlspecialchars($department['DepartmentName']) . '</option>';
                                    }
                                }
                                ?>
                                <?php
                                if ($allProfessorsResult) {
                                    foreach ($allProfessorsResult as $professor) {
                                        echo '<option value="' . htmlspecialchars($professor['ProfessorID']) . '">' . htmlspecialchars($professor['ProfessorName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="submit" name="add_professor" class="btn btn-primary">Add Professor</button>
                        </form>
                    </div>

                    <!-- Current professor Tab Content -->
                    <div id="currentProfessorsTab" class="professor-tab-content" style="display:none;">
                        <h3>Current Professors</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Professor ID</th>
                                        <th>Professor Name</th>
                                        <th>Professor Title</th>
                                        <th>Department Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($professorsDetails) {
                                        foreach ($professorsDetails as $professor) {
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($professor['ProfessorID']); ?></td>
                                                <td><?php echo htmlspecialchars($professor['ProfessorName']); ?></td>
                                                <td><?php echo htmlspecialchars($professor['ProfessorTitle']); ?></td>
                                                <td><?php echo htmlspecialchars($professor['DepartmentName']); ?></td>
                                                <td>
                                                    <div class="action-icons">
                                                        <button class="btn btn-sm btn-danger" onclick="openDeleteProfessorModal(<?php echo htmlspecialchars($professor['ProfessorID']); ?>)" title="Delete Department"><i class="fas fa-trash-alt"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Delete Professor Modal -->
                    <div id="deleteProfessorModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeDeleteProfessorModal()">×</span>
                            <h3>Delete Professor</h3>
                            <p>Are you sure you want to delete this professor?</p>
                            <form method="post">
                                <input type="hidden" name="professor_id" id="delete_professor_id">
                                <button type="submit" name="delete_professor" class="btn btn-danger">Yes, Delete</button>
                                <button type="button" class="btn btn-secondary" onclick="closeDeleteProfessorModal()">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Student Section -->
                <div id="students" class="tab-content" style="display:none;">
                    <h2>Student List</h2>
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#addStudentTab" onclick="showTabContent('addStudentTab', this, 'student-tab-content')">Add Student</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " href="#studentListTab" onclick="showTabContent('studentListTab', this, 'student-tab-content')">Student List</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#enrollStudentTab" onclick="showTabContent('enrollStudentTab', this, 'student-tab-content')">Enroll Student</a>
                        </li>
                    </ul>

                    <!-- Add Student Tab Content -->
                    <div id="addStudentTab" class="student-tab-content active">
                        <form method="post">
                            <h3>Add Student</h3>
                            <label for="first_name">Name:</label>
                            <input type="text" name="first_name" id="first_name" placeholder="First Name" required>
                            <label for="last_name">Last Name:</label>
                            <input type="text" name="last_name" id="last_name" placeholder="Last Name" required>
                            <label for="email">Email:</label>
                            <input type="email" name="email" id="email" placeholder="Email" required>
                            <label for="department_id_student">Department:</label>
                            <select name="department_id" id="department_id_student" required>
                                <option value="">Select Department</option>
                                <?php
                                if ($departmentOptions) {
                                    foreach ($departmentOptions as $department) {
                                        echo '<option value="' . htmlspecialchars($department['DepartmentID']) . '">' . htmlspecialchars($department['DepartmentName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                        </form>
                    </div>

                    <!-- Student List Tab Content -->
                    <div id="studentListTab" class="student-tab-content " style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Course</th>
                                        <th>Grade</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $currentStudentId = null;
                                    if ($studentsWithDetails) {
                                        foreach ($studentsWithDetails as $row) {
                                            if ($row['StudentID'] != $currentStudentId) {
                                                // Display student information if it's a new student
                                                if ($currentStudentId !== null) {
                                                    echo '</tr>'; // Close the previous row
                                                }
                                                echo '<tr>';
                                                echo '<td>' . htmlspecialchars($row['StudentID']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['StudentName']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['Email']) . '</td>';
                                                echo '<td>' . htmlspecialchars($row['DepartmentName']) . '</td>';
                                                $currentStudentId = $row['StudentID'];
                                                $deleteButtonDisplayed = false;
                                            }
                                            if ($row['CourseName']) {
                                                // Display course and grade
                                                echo '<td>' . htmlspecialchars($row['CourseName']) . '</td>';
                                                echo '<td class="grade-cell">';
                                                if ($row['Grade'] !== null) {
                                                    // Grade Update Form
                                                    echo '<form method="post" class="update-grade-form">';
                                                    echo '<input type="hidden" name="enrollment_id" value="' . htmlspecialchars($row['EnrollmentID']) . '">';
                                                    echo '<input type="text" name="grade" value="' . htmlspecialchars($row['Grade']) . '">';
                                                    echo '<button type="submit" name="update_grade" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>';
                                                    echo '</form>';
                                                } else {
                                                    echo "No Grade";
                                                }
                                                echo '</td>';
                                            } else {
                                                echo '<td>Not Enrolled</td>';
                                                echo '<td>N/A</td>';
                                            }
                                            if (!$deleteButtonDisplayed) {
                                                // Display delete button once per student
                                                echo '<td><div class="action-icons">
                                            <button  class="btn btn-sm btn-danger" onclick="openDeleteModal(' . htmlspecialchars($row['StudentID']) . ')"><i class="fas fa-trash"></i> Delete</button>
                                        </div></td>';
                                                $deleteButtonDisplayed = true;
                                            }
                                        }
                                        if ($currentStudentId !== null) {
                                            echo '</tr>';
                                        }
                                    }
                                    if ($currentStudentId !== null) {
                                        echo '</tr>'; // Ensure the last row is closed
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Enroll Student Tab Content -->
                    <div id="enrollStudentTab" class="student-tab-content" style="display:none;">
                        <form method="post">
                            <h3>Enroll Student</h3>
                            <label for="student_id">Student:</label>
                            <select name="student_id" id="student_id" required>
                                <option value="">Select Student</option>
                                <?php
                                if ($allStudentsResult) {
                                    foreach ($allStudentsResult as $student) {
                                        echo '<option value="' . htmlspecialchars($student['StudentID']) . '">' . htmlspecialchars($student['StudentName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <label for="course_id">Course:</label>
                            <select name="course_id" id="course_id" required>
                                <option value="">Select Course</option>
                                <?php
                                if ($allCoursesResult) {
                                    foreach ($allCoursesResult as $course) {
                                        echo '<option value="' . htmlspecialchars($course['CourseID']) . '">' . htmlspecialchars($course['CourseName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="submit" name="enroll_student" class="btn btn-primary">Enroll Student</button>
                        </form>
                    </div>

                    <!-- Delete Student Modal -->
                    <div id="deleteModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeDeleteModal()">×</span>
                            <h3>Delete Student</h3>
                            <p>Are you sure you want to delete this student?</p>
                            <form method="post">
                                <input type="hidden" name="student_id" id="delete_student_id">
                                <button type="submit" name="delete_student" class="btn btn-danger">Yes, Delete</button>
                                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Faculties Section -->
                <div id="faculties" class="tab-content" style="display:none;">
                    <header class="mb-4">
                        <h1>Manage Faculties</h1>
                    </header>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#addFacultyTab" onclick="showTabContent('addFacultyTab', this, 'faculty-tab-content')">Add Faculty</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#currentFacultiesTab" onclick="showTabContent('currentFacultiesTab', this, 'faculty-tab-content')">Current Faculties</a>
                        </li>
                    </ul>

                    <!-- Add Faculty Tab Content -->
                    <div id="addFacultyTab" class="faculty-tab-content active">
                        <form method="post">
                            <h3>Add Faculty</h3>
                            <label for="faculty_name">Faculty Name:</label>
                            <input type="text" name="faculty_name" id="faculty_name" placeholder="Faculty Name" required>
                            <label for="head_professor_id_fac">Head Professor:</label>
                            <select name="head_professor_id_fac" id="head_professor_id_fac" required>
                                <option value="">Select Professor</option>
                                <?php
                                if ($allProfessorsResult) {
                                    foreach ($allProfessorsResult as $professor) {
                                        echo '<option value="' . htmlspecialchars($professor['ProfessorID']) . '">' . htmlspecialchars($professor['ProfessorName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="submit" name="add_faculty" class="btn btn-primary">Add Faculty</button>
                        </form>
                    </div>

                    <!-- Current Faculties Tab Content -->
                    <div id="currentFacultiesTab" class="faculty-tab-content" style="display:none;">
                        <h3>Current Faculties</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Faculty ID</th>
                                        <th>Faculty Name</th>
                                        <th>Head Professor</th>
                                        <th># Departements</th>
                                        <th># Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($facultiesWithDepartments) {
                                        foreach ($facultiesWithDepartments as $faculty) {
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($faculty['FacultyID']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['FacultyName']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['ProfessorName']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['NumberOfDepartment']); ?></td>
                                                <td><?php echo htmlspecialchars($faculty['NumberOfStudent']); ?></td>
                                                <td>
                                                    <div class="action-icons">
                                                        <button class="btn btn-sm btn-info" onclick="openViewFacultyModal(<?php echo htmlspecialchars($faculty['FacultyID']); ?>)" title="View Details"><i class="fas fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-danger" onclick="openDeleteFacultyModal(<?php echo htmlspecialchars($faculty['FacultyID']); ?>)" title="Delete Faculty"><i class="fas fa-trash-alt"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Delete Faculty Modal -->
                    <div id="deleteFacultyModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeDeleteFacultyModal()">×</span>
                            <h3>Delete Faculty</h3>
                            <p>Are you sure you want to delete this faculty member?</p>
                            <form method="post">
                                <input type="hidden" name="faculty_id" id="delete_faculty_id">
                                <button type="submit" name="delete_faculty" class="btn btn-danger">Yes, Delete</button>
                                <button type="button" class="btn btn-secondary" onclick="closeDeleteFacultyModal()">Cancel</button>
                            </form>
                        </div>
                    </div>
                    <!-- View Faculty Modal -->
                    <div id="viewFacultyModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeViewFacultyModal()">×</span>
                            <h3>Departments in This Faculty</h3>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Department ID</th>
                                            <th>Department Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $queryFacultyDepartmentsDisplay = "SELECT * FROM Department WHERE FacultyID = :facultyId";
                                        if (isset($_POST['faculty_id'])) {
                                            $facultyId = getPostValue('faculty_id', 0);
                                        } else {
                                            $facultyId = 0;
                                        }
                                        if (is_numeric($facultyId) && $facultyId > 0) {
                                            $facultyDepartmentResultDisplay = executeQuery($conn, $queryFacultyDepartmentsDisplay, ['facultyId' => $facultyId]);
                                            if ($facultyDepartmentResultDisplay) {
                                                foreach ($facultyDepartmentResultDisplay as $departmentDisplay) {
                                        ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($departmentDisplay['DepartmentID']); ?></td>
                                                        <td><?php echo htmlspecialchars($departmentDisplay['DepartmentName']); ?></td>
                                                    </tr>
                                        <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="closeViewFacultyModal()">Close</button>
                        </div>
                    </div>

                    <!-- set Faculty head Modal -->
                    <div id="setFacultyHeadModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeSetFacultyHeadModal()">×</span>
                            <h3>set Faculty Head</h3>
                            <form method="post">
                                <input type="hidden" name="faculty_id_head" id="set_faculty_id">
                                <label for="head_professor_id_fac">Head Professor:</label>
                                <select name="head_professor_id_fac" id="head_professor_id_fac" required>
                                    <option value="">Select Professor</option>
                                    <?php
                                    if (isset($_POST['faculty_id']) && is_numeric($_POST['faculty_id']) && $_POST['faculty_id'] > 0) {
                                        $facultyId = getPostValue('faculty_id', 0);
                                        $queryAllProfessors = "SELECT ProfessorID, ProfessorName FROM Professor WHERE DepartmentID IN ( SELECT DepartmentID FROM Department where FacultyID = :facultyId)";
                                        $allProfessorsResult = executeQuery($conn, $queryAllProfessors, ['facultyId' => $facultyId]);
                                        if ($allProfessorsResult) {
                                            foreach ($allProfessorsResult as $professor) {
                                                echo '<option value="' . htmlspecialchars($professor['ProfessorID']) . '">' . htmlspecialchars($professor['ProfessorName']) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <button type="submit" name="set_faculty_head" class="btn btn-primary">Yes, set</button>
                                <button type="button" class="btn btn-secondary" onclick="closeSetFacultyHeadModal()">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- departemnts Section -->
                <div id="departments" class="tab-content" style="display:none;">
                    <header class="mb-4">
                        <h1>Manage Departments</h1>
                    </header>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#addDepartmentTab" onclick="showTabContent('addDepartmentTab', this, 'department-tab-content')">Add Department</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#currentDepartmentsTab" onclick="showTabContent('currentDepartmentsTab', this, 'department-tab-content')">Current Departments</a>
                        </li>
                    </ul>

                    <!-- Add Department Tab Content -->
                    <div id="addDepartmentTab" class="department-tab-content active">
                        <form method="post">
                            <h3>Add Department</h3>
                            <label for="department_name">Department Name:</label>
                            <input type="text" name="department_name" id="department_name" placeholder="Department Name" required>
                            <label for="faculty_id">Faculty:</label>
                            <select name="faculty_id" id="faculty_id" required>
                                <option value="">Select Faculty</option>
                                <?php
                                $queryAllFaculties = "SELECT FacultyID, FacultyName FROM Faculty";
                                $allFacultiesResult =   fetchOptions($conn, $queryAllFaculties);
                                if ($allFacultiesResult) {
                                    foreach ($allFacultiesResult as $faculty) {
                                        echo '<option value="' .  htmlspecialchars($faculty['FacultyID']) . '">' . htmlspecialchars($faculty['FacultyName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="submit" name="add_department" class="btn btn-primary">Add Department</button>
                        </form>
                    </div>

                    <!-- Current Departments Tab Content -->
                    <div id="currentDepartmentsTab" class="department-tab-content" style="display:none;">
                        <h3>Current Departments</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Department ID</th>
                                        <th>Department Name</th>
                                        <th>Department Head</th>
                                        <th># Students</th>
                                        <th>Faculty</th>
                                        <th># Courses</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($departmentsWithDetails) {
                                        foreach ($departmentsWithDetails as $department) {
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($department['DepartmentID']); ?></td>
                                                <td><?php echo htmlspecialchars($department['DepartmentName']); ?></td>
                                                <td><?php echo htmlspecialchars($department['HeadProfessor']); ?></td>
                                                <td><?php echo htmlspecialchars($department['NumberOfStudents']); ?></td>
                                                <td><?php echo htmlspecialchars($department['FacultyName']); ?></td>
                                                <td><?php echo htmlspecialchars($department['NumberOfCourses']); ?></td>
                                                <td>
                                                    <div class="action-icons">
                                                        <button class="btn btn-sm btn-info" onclick="openViewDepartmentModal(<?php echo htmlspecialchars($department['DepartmentID']); ?>)" title="View Details"><i class="fas fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-warning" onclick="openSetDepartmentHeadModal('<?php echo htmlspecialchars($department['DepartmentID']); ?>')" title="Set Department Head"><i class="fas fa-user-tie"></i></button>
                                                        <button class="btn btn-sm btn-danger" onclick="openDeleteDepartmentModal(<?php echo htmlspecialchars($department['DepartmentID']); ?>)" title="Delete Department"><i class="fas fa-trash-alt"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Delete Department Modal -->
                    <div id="deleteDepartmentModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeDeleteDepartmentModal()">×</span>
                            <h3>Delete Department</h3>
                            <p>Are you sure you want to delete this department?</p>
                            <form method="post">
                                <input type="hidden" name="department_id" id="delete_department_id">
                                <button type="submit" name="delete_department" class="btn btn-danger">Yes, Delete</button>
                                <button type="button" class="btn btn-secondary" onclick="closeDeleteDepartmentModal()">Cancel</button>
                            </form>
                        </div>
                    </div>

                    <!-- View Department Modal -->
                    <div id="viewDepartmentModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeViewDepartmentModal()">×</span>
                            <h3>Students in This Department</h3>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $queryDepartmentStudentsDisplay = "SELECT * FROM Student WHERE DepartmentID = :departmentId";
                                        if (isset($_POST['department_id'])) {
                                            $departmentId = getPostValue('department_id', 0);
                                            error_log("Department ID from POST: " . $departmentId);
                                        } else {
                                            $departmentId = 0;
                                            error_log("Department ID not found in POST, using 0.");
                                        }
                                        if (is_numeric($departmentId) && $departmentId > 0) {
                                            $departmentStudentResultDisplay = executeQuery($conn, $queryDepartmentStudentsDisplay, ['departmentId' => $departmentId]);
                                            var_dump($departmentStudentResultDisplay);
                                            if ($departmentStudentResultDisplay) {
                                                foreach ($departmentStudentResultDisplay as $studentDisplay) {
                                        ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($studentDisplay['StudentID']); ?></td>
                                                        <td><?php echo htmlspecialchars($studentDisplay['StudentName']); ?></td>
                                                    </tr>
                                        <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <form id="enrollDepartmentForm" class="mt-4" method="post">
                                <input type="hidden" name="department_id_enroll" id="department_id_enroll">
                                <div class="form-group">
                                    <label for="student_id_department">Enroll Student:</label>
                                    <select name="student_id_department" id="student_id_department" class="form-control" required>
                                        <option value="">Select Student</option>
                                        <?php
                                        if ($allStudentsResult) {
                                            foreach ($allStudentsResult as $student) {
                                                echo '<option value="' . htmlspecialchars($student['StudentID']) . '">' . htmlspecialchars($student['StudentName']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" name="enroll_student_department" class="btn btn-primary mt-3">
                                    <i class="fas fa-user-plus"></i> Enroll Student to Department
                                </button>
                            </form>
                            <button type="button" class="btn btn-secondary" onclick="closeViewDepartmentModal()">Close</button>
                        </div>
                    </div>

                    <!-- Set Department Head Modal -->
                    <div id="setDepartmentHeadModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeSetDepartmentHeadModal()">×</span>
                            <h3>Set Department Head</h3>
                            <form method="post">
                                <input type="hidden" name="department_id_head" id="set_department_id">
                                <label for="head_professor_id_dep">Head Professor:</label>
                                <select name="head_professor_id_dep" id="head_professor_id_dep" required>
                                    <option value="">Select Professor</option>
                                    <?php
                                    if (isset($_POST['department_id']) && is_numeric($_POST['department_id']) && $_POST['department_id'] > 0) {
                                        $departmentId = getPostValue('department_id', 0);
                                        $queryProfessors = "SELECT ProfessorID, ProfessorName FROM Professor WHERE DepartmentID = :departmentId AND ProfessorID NOT IN (SELECT HeadProfessorID FROM Department WHERE DepartmentID = :departmentId)";
                                        $professorsResult = executeQuery($conn, $queryProfessors, ['departmentId' => $departmentId]);
                                        if ($professorsResult) {
                                            foreach ($professorsResult as $professor) {
                                                echo '<option value="' . htmlspecialchars($professor['ProfessorID']) . '">' . htmlspecialchars($professor['ProfessorName']) . '</option>';
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                                <button type="submit" name="set_department_head" class="btn btn-primary">Yes, Set</button>
                                <button type="button" class="btn btn-secondary" onclick="closeSetDepartmentHeadModal()">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Courses Section -->
                <div id="courses" class="tab-content" style="display:none;">
                    <header class="mb-4">
                        <h1>Manage Courses</h1>
                    </header>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#addCourseTab" onclick="showTabContent('addCourseTab', this, 'course-tab-content')">Add Course</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#currentCoursesTab" onclick="showTabContent('currentCoursesTab', this, 'course-tab-content')">Current Courses</a>
                        </li>
                    </ul>

                    <!-- Add Course Tab Content -->
                    <div id="addCourseTab" class="course-tab-content active">
                        <form method="post">
                            <h3>Add Course</h3>
                            <label for="course_name">Course Name:</label>
                            <input type="text" name="course_name" id="course_name" placeholder="Course Name" required>
                            <label for="course_code">Course Credits:</label>
                            <input type="text" name="course_code" id="course_code" placeholder="Course Credits" required>
                            <label for="department_id">Department:</label>
                            <select name="department_id" id="department_id" required>
                                <option value="">Select Department</option>
                                <?php
                                if ($departmentOptions) {
                                    foreach ($departmentOptions as $department) {
                                        echo '<option value="' . htmlspecialchars($department['DepartmentID']) . '">' . htmlspecialchars($department['DepartmentName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <label for="professor_id">Professor:</label>
                            <select name="professor_id" id="professor_id" required>
                                <option value="">Select Professor</option>
                                <?php
                                if ($allProfessorsResult) {
                                    foreach ($allProfessorsResult as $professor) {
                                        echo '<option value="' . htmlspecialchars($professor['ProfessorID']) . '">' . htmlspecialchars($professor['ProfessorName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="submit" name="add_course" class="btn btn-primary">Add Course</button>
                        </form>
                    </div>

                    <!-- Current Courses Tab Content -->
                    <div id="currentCoursesTab" class="course-tab-content" style="display:none;">
                        <h3>Current Courses</h3>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Course ID</th>
                                        <th>Course Name</th>
                                        <th>Course Credits</th>
                                        <th>Professor</th>
                                        <th># Students</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($coursesWithNumberOfStudents) {
                                        foreach ($coursesWithNumberOfStudents as $course) {
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['CourseID']); ?></td>
                                                <td><?php echo htmlspecialchars($course['CourseName']); ?></td>
                                                <td> <?php
                                                        if ($coursesWithProfessor) {
                                                            foreach ($coursesWithProfessor as $courseProff) {
                                                                if ($courseProff['CourseID'] == $course['CourseID']) {
                                                                    echo htmlspecialchars($courseProff['CourseCredits']);
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                </td>
                                                <td><?php
                                                    if ($coursesWithProfessor) {
                                                        foreach ($coursesWithProfessor as $courseProff) {
                                                            if ($courseProff['CourseID'] == $course['CourseID']) {
                                                                echo htmlspecialchars($courseProff['ProfessorName']);
                                                            }
                                                        }
                                                    }
                                                    ?></td>
                                                <td><?php echo htmlspecialchars($course['NumberOfStudents'] ?? 0); ?></td>
                                                <td>
                                                    <div class="action-icons">
                                                        <button class="btn btn-sm btn-info" onclick="openViewCourseModal(<?php echo htmlspecialchars($course['CourseID']); ?>)" title="View Details"><i class="fas fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-danger" onclick="openDeleteCourseModal(<?php echo htmlspecialchars($course['CourseID']); ?>)" title="Delete Course"><i class="fas fa-trash-alt"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Delete Course Modal -->
                    <div id="deleteCourseModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeDeleteCourseModal()">×</span>
                            <h3>Delete Course</h3>
                            <p>Are you sure you want to delete this course?</p>
                            <form method="post">
                                <input type="hidden" name="course_id" id="delete_course_id">
                                <button type="submit" name="delete_course" class="btn btn-danger">Yes, Delete</button>
                                <button type="button" class="btn btn-secondary" onclick="closeDeleteCourseModal()">Cancel</button>
                            </form>
                        </div>
                    </div>

                    <!-- View Course Modal -->
                    <div id="viewCourseModal" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeViewCourseModal()">×</span>
                            <h3>Students in This Course</h3>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Enrollment Date</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $queryCourseStudentsDisplay = "SELECT S.StudentID,S.StudentName, E.EnrollmentDate ,COALESCE(E.Grade, NULL) AS Grade  FROM Enrollment E  JOIN Student S ON E.StudentID = S.StudentID
                                            WHERE E.CourseID = :courseId";
                                        if (isset($_POST['course_id'])) {
                                            $courseId = getPostValue('course_id', 0);
                                        } else {
                                            $courseId = 0;
                                        }
                                        if (is_numeric($courseId) && $courseId > 0) {
                                            $courseStudentResultDisplay = executeQuery($conn, $queryCourseStudentsDisplay, ['courseId' => $courseId]);
                                            if ($courseStudentResultDisplay) {
                                                foreach ($courseStudentResultDisplay as $studentDisplay) {
                                        ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($studentDisplay['StudentID']); ?></td>
                                                        <td><?php echo htmlspecialchars($studentDisplay['StudentName']); ?></td>
                                                        <td><?php echo htmlspecialchars($studentDisplay['EnrollmentDate']); ?></td>
                                                        <td><?php echo htmlspecialchars($studentDisplay['Grade']); ?></td>
                                                    </tr>
                                        <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <form method="post">
                                <input type="hidden" name="course_id" id="course_id_enroll">
                                <label for="student_id">Student:</label>
                                <select name="student_id" id="student_id_course" required>
                                    <option value="">Select Student</option>
                                    <?php
                                    if ($allStudentsResult) {
                                        foreach ($allStudentsResult as $student) {
                                            echo '<option value="' . htmlspecialchars($student['StudentID']) . '">' . htmlspecialchars($student['StudentName']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                                <button type="submit" name="enroll_student" class="btn btn-primary">Enroll Student to Course</button>
                            </form>
                            <button type="button" class="btn btn-secondary" onclick="closeViewCourseModal()">Close</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="../js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</body>

</html>