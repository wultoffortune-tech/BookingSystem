<?php include '../config.php'; ?>
<!DOCTYPE html>
<html>

<head>
    <title>Class Management</title>
    <link rel="stylesheet" href="class.css">
</head>

<body>
    <div class="container">
        <h2>Class Management</h2>

        <!-- Navigation Buttons -->
        <div class="nav">
            <a href="../all_info.php">Back to Home</a> |
            <a href="../student/student.php">Go to Student</a>
        </div>

        <!-- Search Bar -->
        <form method="GET" action="class.php" class="search-box">
            <input type="text" name="search" placeholder="Search code or description" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Class Table -->
        <table>
            <tr>
                <th>Class ID</th>
                <th>Code</th>
                <th>Description</th>
            </tr>
            <?php
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            $sql = "SELECT * FROM class WHERE codes LIKE '%$search%' OR description LIKE '%$search%' ORDER BY class_id ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['class_id']}</td>
                            <td>{$row['codes']}</td>
                            <td>{$row['description']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='3'>No class found</td></tr>";
            }
            $conn->close();
            ?>
        </table>
    </div>
    <script src="class.js"></script>
</body>

</html>