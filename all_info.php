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
            padding: auto;
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
    <h2>All students with thier class information</h2>
    <table>
        <tr>
            <th>student_id</th>
            <th>name</th>
            <th>surname</th>
            <th>age</th>
            <th>department</th>
            <th>codes</th>
            <th>description</th>
        </tr>
        <?php
        $sql = "SELECT s.student_id,s.name,s.surname,s.age,s.department, c.codes,c.description from student s
        INNER JOIN class c on s.class_id = c.class_id 
        order by s.student_id";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['student_id'] . "</td>";
                echo "<td>" . $row['name'] . "</td>";
                echo "<td>" . $row['surname'] . "</td>";
                echo "<td>" . $row['age'] . "</td>";
                echo "<td>" . $row['department'] . "</td>";
                echo "<td>" . $row['codes'] . "</td>";
                echo "<td>" . $row['description'] . "</td>";
                echo " </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>no records found</td></tr>";
        }
        ?>
    </table>
</body>

</html>