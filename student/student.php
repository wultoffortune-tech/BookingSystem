<?php include '../config.php'; ?>
<!DOCTYPE html>
<html>

<head>
    <title>Student Management</title>
    <link rel="stylesheet" href="student.css">
</head>

<body>
    <div class="container">
        <h2>Student Management</h2>

        <!-- Navigation Buttons -->
        <div class="nav">
            <a href="../all_info.php">Back to Home</a> |
            <a href="../class/class.php">Go to Class</a>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="student.php" class="search-box">
            <input type="text" name="search" placeholder="Search name or surname" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Student Table -->
        <table>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Surname</th>
                <th>Age</th>
                <th>Department</th>
                <th>Class ID</th>
            </tr>
            <?php
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $sql = "SELECT * FROM student WHERE name LIKE '%$search%' OR surname LIKE '%$search%' ORDER BY student_id ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['student_id']}</td>
                            <td>{$row['name']}</td>
                            <td>{$row['surname']}</td>
                            <td>{$row['age']}</td>
                            <td>{$row['department']}</td>
                            <td>{$row['class_id']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No student found</td></tr>";
            }
            $conn->close();
            ?>
        </table>
    </div>
    <script src="student.js"></script>
</body>

</html>