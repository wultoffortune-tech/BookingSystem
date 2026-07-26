<?php
include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All clases</title>
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
<h2> All classes -codes and description</h2>
<table>
    <tr>

        <th>class_id</th>
        <th>codes
        </th>
        <th>description</th>
    </tr>
    <?php
    $result = mysqli_query($conn, "SELECT * FROM class");
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['class_id'] . "</td>";
        echo "<td>" . $row['codes'] . "</td>";
        echo "<td>" . $row['description'] . "</td>";
        echo " </tr>";
    }
    ?>
</table>

<body>

</body>

</html>