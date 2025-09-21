<!DOCTYPE html>
<html>
<head>
    <title>Playlist</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h1>Categories</h1>
    <ul>
        <?php
        include 'db.php';
        $sql = "SELECT id, name FROM categories";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<li><a href="category.php?id=' . $row["id"] . '">' . $row["name"] . '</a></li>';
            }
        } else {
            echo "0 results";
        }
        $conn->close();
        ?>
    </ul>
</body>
</html>
