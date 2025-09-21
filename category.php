<!DOCTYPE html>
<html>
<head>
    <title>Category</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <?php
    include 'db.php';
    $category_id = $_GET['id'];

    // Fetch category name
    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category_name = "Category";
    if ($result->num_rows > 0) {
        $category_name = $result->fetch_assoc()['name'];
    }
    $stmt->close();
    ?>
    <h1><?php echo htmlspecialchars($category_name); ?></h1>
    <ul>
        <?php
        // Fetch entries
        $stmt = $conn->prepare("SELECT id, title FROM entries WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<li><a href="entry.php?id=' . $row["id"] . '">' . htmlspecialchars($row["title"]) . '</a></li>';
            }
        } else {
            echo "0 results";
        }
        $stmt->close();
        $conn->close();
        ?>
    </ul>
    <a href="index.php">Back to Categories</a>
</body>
</html>
