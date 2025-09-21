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

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Fetch from TMDB</h5>
        <div class="form-row">
            <div class="form-group col-md-3">
                <label for="tmdb_id">TMDB ID</label>
                <input type="text" id="tmdb_id" class="form-control" placeholder="e.g., 550">
            </div>
            <div class="form-group col-md-3">
                <label for="tmdb_type">Content Type</label>
                <select id="tmdb_type" class="form-control">
                    <option value="movie">Movie</option>
                    <option value="tv">TV Series</option>
                </select>
            </div>
            <div class="form-group col-md-3 d-flex align-items-end">
                <button type="button" id="fetch_tmdb" class="btn btn-info">Fetch Data</button>
            </div>
        </div>
        <div id="tmdb-status" class="mt-2"></div>
    </div>
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
?>

<script>
document.getElementById('fetch_tmdb').addEventListener('click', function() {
    const tmdbId = document.getElementById('tmdb_id').value;
    const tmdbType = document.getElementById('tmdb_type').value;
    const statusDiv = document.getElementById('tmdb-status');

    if (!tmdbId) {
        statusDiv.innerHTML = '<div class="alert alert-warning">Please enter a TMDB ID.</div>';
        return;
    }

    statusDiv.innerHTML = '<div class="alert alert-info">Fetching data from TMDB...</div>';

    fetch(`../api/tmdb.php?id=${tmdbId}&type=${tmdbType}`)
        .then(response => {
            if (!response.ok) {
                // Try to get error message from TMDB response body
                return response.json().then(errorBody => {
                    throw new Error(errorBody.status_message || `Request failed with status ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success === false) {
                throw new Error(data.status_message || 'TMDB API returned an error.');
            }

            statusDiv.innerHTML = '<div class="alert alert-success">Data fetched successfully!</div>';

            // Populate form fields
            document.getElementById('title').value = data.title || data.name || '';
            document.getElementById('description').value = data.overview || '';
            if (data.poster_path) {
                document.getElementById('poster').value = 'https://image.tmdb.org/t/p/w500' + data.poster_path;
            }

            const releaseDate = data.release_date || data.first_air_date || '';
            if (releaseDate) {
                document.getElementById('year').value = releaseDate.substring(0, 4);
            }

            if (data.production_countries && data.production_countries.length > 0) {
                document.getElementById('country').value = data.production_countries.map(c => c.name).join(', ');
            }

            document.getElementById('rating').value = data.vote_average ? data.vote_average.toFixed(1) : '';

            if (tmdbType === 'movie') {
                if (data.runtime) {
                    const hours = Math.floor(data.runtime / 60);
                    const minutes = data.runtime % 60;
                    document.getElementById('duration').value = `${hours}h ${minutes}m`;
                }
                // Set category to "Movies"
                setCategory('Movies');

                // Find parental rating
                if (data.release_dates && data.release_dates.results) {
                    const usRelease = data.release_dates.results.find(r => r.iso_3166_1 === 'US');
                    if (usRelease && usRelease.release_dates[0] && usRelease.release_dates[0].certification) {
                        document.getElementById('parental_rating').value = usRelease.release_dates[0].certification;
                    }
                }

            } else if (tmdbType === 'tv') {
                if (data.episode_run_time && data.episode_run_time.length > 0) {
                    document.getElementById('duration').value = `${data.episode_run_time[0]}m`;
                }
                // Set category to "TV Series"
                setCategory('TV Series');

                // Find parental rating
                if (data.content_ratings && data.content_ratings.results) {
                    const usRating = data.content_ratings.results.find(r => r.iso_3166_1 === 'US');
                    if (usRating && usRating.rating) {
                        document.getElementById('parental_rating').value = usRating.rating;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching from TMDB:', error);
            statusDiv.innerHTML = `<div class="alert alert-danger">Error fetching data: ${error.message}</div>`;
        });
});

function setCategory(categoryName) {
    const categorySelect = document.getElementById('category_id');
    for (let i = 0; i < categorySelect.options.length; i++) {
        if (categorySelect.options[i].text.trim().toLowerCase() === categoryName.trim().toLowerCase()) {
            categorySelect.selectedIndex = i;
            break;
        }
    }
}
</script>

<?php
include '../footer.php';
?>
