--------------------------------------------------------------

-- Create the Database
CREATE DATABASE University;
USE University;

-- Create the tables

-- Faculty Table
CREATE TABLE Faculty
(
    FacultyID INT PRIMARY KEY IDENTITY(1,1),
    FacultyName NVARCHAR(255) NOT NULL,
    HeadProfessorID INT NULL,
    -- foreign key to Professor
);


-- Department Table
CREATE TABLE Department
(
    DepartmentID INT PRIMARY KEY IDENTITY(1,1),
    DepartmentName NVARCHAR(255) NOT NULL,
    FacultyID INT NOT NULL,
    HeadProfessorID INT NULL,
    -- foreign key to Professor
    FOREIGN KEY (FacultyID) REFERENCES Faculty(FacultyID)
);

-- Professor Table
CREATE TABLE Professor
(
    ProfessorID INT PRIMARY KEY IDENTITY(1,1),
    ProfessorName NVARCHAR(255) NOT NULL,
    ProfessorTitle NVARCHAR(255),
    DepartmentID INT NOT NULL,
    FOREIGN KEY (DepartmentID) REFERENCES Department(DepartmentID)
);

-- Update Faculty and Department Tables To Add Head Professor Relationship

ALTER TABLE Faculty
ADD CONSTRAINT FK_Faculty_HeadProfessor
FOREIGN KEY (HeadProfessorID) REFERENCES Professor(ProfessorID)

ALTER TABLE Department
ADD CONSTRAINT FK_Department_HeadProfessor
FOREIGN KEY (HeadProfessorID) REFERENCES Professor(ProfessorID)

-------------------------------------------------------------------------------------

-- Student Table
CREATE TABLE Student
(
    StudentID INT PRIMARY KEY IDENTITY(1,1),
    StudentName NVARCHAR(255) NOT NULL,
    StudentMajor NVARCHAR(255),
    Email VARCHAR(100),
    DepartmentID INT NOT NULL,
    FOREIGN KEY (DepartmentID) REFERENCES Department(DepartmentID)
);


-- Course Table
CREATE TABLE Course
(
    CourseID INT PRIMARY KEY IDENTITY(1,1),
    CourseName NVARCHAR(255) NOT NULL,
    CourseCredits INT,
    ProfessorID INT NOT NULL,
    FOREIGN KEY (ProfessorID) REFERENCES Professor(ProfessorID)
);


-- Enrollment Table
CREATE TABLE Enrollment
(
    EnrollmentID INT PRIMARY KEY IDENTITY(1,1),
    StudentID INT NOT NULL,
    CourseID INT NOT NULL,
    EnrollmentDate DATE,
    Grade NVARCHAR(10),
    FOREIGN KEY (StudentID) REFERENCES Student(StudentID),
    FOREIGN KEY (CourseID) REFERENCES Course(CourseID)
);


-- Insert sample data
-- Faculty
INSERT INTO Faculty
    (FacultyName)
VALUES
    ('Science'),
    ('Engineering'),
    ('Arts and Humanities');


-- Department
INSERT INTO Department
    (DepartmentName, FacultyID)
VALUES
    ('Physics', 1),
    ('Mathematics', 1),
    ('Computer Science', 2),
    ('Electrical Engineering', 2),
    ('History', 3),
    ('English', 3)

------------------------------------------------------------------------------------------

-- Professors
INSERT INTO Professor
    (ProfessorName, ProfessorTitle,DepartmentID)
VALUES
    ('John Smith', 'Professor of Physics', 1),
    ('Alice Brown', 'Associate Professor of Mathematics', 2),
    ('Robert Jones', 'Professor of Computer Science', 3),
    ('Emily Davis', 'Professor of Electrical Engineering', 4),
    ('David Wilson', 'Professor of History', 5),
    ('Sarah Green', 'Professor of English', 6),
    ('Sarah ahmed', 'Professor of linear', 2),
    ('Sarah mohamed', 'Professor of numerical', 2),
    ('Sarah ashraf', 'Professor of number theory', 2),
    ('Sarah said', 'Professor of Database', 3)

-- Set Faculty Head (John Smith for Science Faculty, Robert Jones for Engineering and David Wilson for Arts and Humanities)
UPDATE Faculty SET HeadProfessorID=1 WHERE FacultyID=1
UPDATE Faculty SET HeadProfessorID=3 WHERE FacultyID=2
UPDATE Faculty SET HeadProfessorID=5 WHERE FacultyID=3


-- Set Department Head (Alice Brown for Math , Robert Jones for CS, Emily Davis for EE, David Wilson for His, and Sarah Green for Eng)
UPDATE Department SET HeadProfessorID = 1 WHERE DepartmentID = 1
UPDATE Department SET HeadProfessorID = 2 WHERE DepartmentID = 2
UPDATE Department SET HeadProfessorID = 3 WHERE DepartmentID = 3
UPDATE Department SET HeadProfessorID = 4 WHERE DepartmentID = 4
UPDATE Department SET HeadProfessorID = 5 WHERE DepartmentID = 5
UPDATE Department SET HeadProfessorID = 6 WHERE DepartmentID = 6


-- Students
INSERT INTO Student
    (StudentName, Email, StudentMajor, DepartmentID)
VALUES
    ('Bob Miller', 'bob@example.com', 'Physics', 1),
    ('Carol White', 'carol@example.com', 'Mathematics', 2),
    ('Tom Black', 'tom@example.com', 'Computer Science', 3),
    ('Jane Doe', 'jane@example.com', 'Electrical Engineering', 4),
    ('Ahmed Ali', 'ahmed@example.com', 'History', 5),
    ('Fatima Hassan', 'fatima@example.com', 'English', 6);


-- Courses
INSERT INTO Course
    (CourseName, CourseCredits, ProfessorID)
VALUES
    ('Intro to Physics', 3, 1),
    ('Calculus I', 4, 2),
    ('Data Structures', 3, 3),
    ('Circuit Analysis', 4, 4),
    ('World History', 3, 5),
    ('American Literature', 3, 6);

-- Enrollment
INSERT INTO Enrollment
    (StudentID, CourseID, EnrollmentDate, Grade)
VALUES
    (1, 1, '2024-01-15', 'A'),
    (1, 2, '2024-01-15', 'B'),
    (2, 2, '2024-01-15', 'A'),
    (3, 3, '2024-01-15', 'C'),
    (4, 4, '2024-01-15', 'A'),
    (5, 5, '2024-01-15', 'A'),
    (6, 6, '2024-01-15', 'A')


-- Querying Data
-- 1. Retrieve All Students and Their Details
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
    LEFT JOIN Course C ON E.CourseID = C.CourseID

-- 2. Retrieve All Courses with Their Professors
SELECT
    C.CourseID,
    C.CourseName,
    C.CourseCredits,
    P.ProfessorName
FROM Course C
    JOIN Professor P ON C.ProfessorID = P.ProfessorID




-------------------------------------------------------------------------------------------


-- Select all data from all tables
SELECT *
FROM Faculty;
SELECT *
FROM Department;
SELECT *
FROM Professor;
SELECT *
FROM Student;
SELECT *
FROM Course;
SELECT *
FROM Enrollment;

----------------------------------------------------------------------------------------------------------


-- 3. List Enrolled Students and their Courses
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
    JOIN Course C ON E.CourseID = C.CourseID



-- 4. Count the Number of Students Enrolled in Each Course
SELECT
    C.CourseName,
    C.CourseID,
    COUNT(E.StudentID) AS NumberOfStudents
FROM Course C
    LEFT JOIN Enrollment E ON C.CourseID = E.CourseID
GROUP BY C.CourseID, C.CourseName



-- 5. Retrieve Faculty and Their Departments
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
GROUP BY F.FacultyID, F.FacultyName, P.ProfessorName, P.ProfessorID




----6
SELECT
    D.DepartmentID,
    D.DepartmentName,
    P.ProfessorName AS HeadProfessor,
    P.ProfessorID,
    F.FacultyName,
    F.FacultyID,
    COUNT(DISTINCT S.StudentID) AS NumberOfStudents,
    (SELECT COUNT(DISTINCT CC.CourseID)
    FROM Course CC LEFT JOIN Professor PP on CC.ProfessorID=PP.ProfessorID
    WHERE PP.DepartmentID = D.DepartmentID) AS NumberOfCourses
FROM Department D
    LEFT JOIN Student S ON D.DepartmentID = S.DepartmentID
    LEFT JOIN Faculty F ON D.FacultyID = F.FacultyID
    LEFT JOIN Professor P ON D.HeadProfessorID=P.ProfessorID
GROUP BY D.DepartmentID,D.DepartmentName,  P.ProfessorName, P.ProfessorID, F.FacultyName, F.FacultyID






-----5
SELECT
    P.ProfessorID,
    P.ProfessorName,
    P.ProfessorTitle,
    D.DepartmentID,
    D.DepartmentName,
    (SELECT COUNT(CourseID)
    FROM Course
    WHERE ProfessorID = P.ProfessorID) AS NumberOfCourses
FROM Professor P
    LEFT JOIN Department D ON P.DepartmentID = D.DepartmentID
-------------------------------------------------------------------------------------------