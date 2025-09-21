<?php
require_once 'auth_check.php';
require_once '../db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic validation
    if (!empty($_POST['title']) && !empty($_POST['category_id'])) {
        $stmt = $conn->prepare("INSERT INTO entries (title, description, poster, thumbnail, category_id, subcategory_id, country, rating, duration, year, parental_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // For simplicity, thumbnail will be the same as poster
        $thumbnail = $_POST['poster'];
        // Subcategory is not handled in this form for now
        $subcategory_id = null;

        $stmt->bind_param("ssssiisdsis",
            $_POST['title'],
            $_POST['description'],
            $_POST['poster'],
            $thumbnail,
            $_POST['category_id'],
            $subcategory_id,
            $_POST['country'],
            $_POST['rating'],
            $_POST['duration'],
            $_POST['year'],
            $_POST['parental_rating']
        );
        $stmt->execute();
        $new_entry_id = $conn->insert_id;
        $stmt->close();

        // Don't redirect, just show a success message
        $success_message = "Entry created successfully! <a href='edit_entry.php?id=$new_entry_id'>Click here to edit it.</a>";
    }
}


include '../header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Add New Entry</h2>
    <a href="manage_entries.php" class="btn btn-secondary">Back to Entries</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="add_entry.php" method="post">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="poster">Poster URL</label>
                <input type="url" name="poster" id="poster" class="form-control">
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select name="category_id" id="category_id" class="form-control" required>
                    <option value="">-- Select a Category --</option>
                    <?php
                    $sql = "SELECT id, name FROM categories ORDER BY name";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" name="country" id="country" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" name="year" id="year" class="form-control">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                     <div class="form-group">
                        <label for="rating">Rating (e.g., 8.5)</label>
                        <input type="text" name="rating" id="rating" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                     <div class="form-group">
                        <label for="duration">Duration (e.g., 1h 30m)</label>
                        <input type="text" name="duration" id="duration" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                     <div class="form-group">
                        <label for="parental_rating">Parental Rating (e.g., PG-13)</label>
                        <input type="text" name="parental_rating" id="parental_rating" class="form-control">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Create Entry and Continue to Edit</button>
        </form>
    </div>
</div>

<?php
$conn->close();
include '../footer.php';
?>
