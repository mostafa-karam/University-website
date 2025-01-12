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



-- Student Table
CREATE TABLE Student
(
    StudentID INT PRIMARY KEY IDENTITY(1,1),
    StudentName NVARCHAR(255) NOT NULL,
    StudentMajor NVARCHAR(255),
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
    (StudentName, StudentMajor, DepartmentID)
VALUES
    ('Bob Miller', 'Physics', 1),
    ('Carol White', 'Mathematics', 2),
    ('Tom Black', 'Computer Science', 3),
    ('Jane Doe', 'Electrical Engineering', 4),
    ('Ahmed Ali', 'History', 5),
    ('Fatima Hassan', 'English', 6);



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
