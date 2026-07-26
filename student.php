<?php
include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All students</title>
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }

        th,
        td {
            border: 1px solid;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #007bff;
            color: white;
        }

        h2 {
            text-align: center;
        }
    </style>
</head>

<body>
    <h2>All students</h2>
    <table>
        <tr>
            <th>student_id</th>
            <th>name</th>
            <th>surname</th>
            <th>age</th>
            <th>department</th>
            <th>class_id</th>
        </tr>
        <?php
        $result = mysqli_query($conn, "SELECT*FROM student");
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['student_id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['surname'] . "</td>";
            echo "<td>" . $row['age'] . "</td>";
            echo "<td>" . $row['department'] . "</td>";
            echo "<td>" . $row['class_id'] . "</td>";
            echo " </tr>";
        }
        ?>
    </table>

</body>

</html>