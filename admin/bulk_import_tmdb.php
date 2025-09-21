<?php
require_once 'auth_check.php';
require_once '../db.php'; // for $conn
require_once '../config.php'; // for TMDB_API_KEYS

include '../header.php';

function fetchFromTmdb($endpoint) {
    // This function will handle fetching from TMDB and key rotation
    // It's similar to the logic in api/tmdb.php but for server-side use.
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['tmdb_api_key_index'])) {
        $_SESSION['tmdb_api_key_index'] = 0;
    }

    $base_url = "https://api.themoviedb.org/3/";
    $max_retries = count(TMDB_API_KEYS);

    for ($i = 0; $i < $max_retries; $i++) {
        $key_index = $_SESSION['tmdb_api_key_index'];
        $api_key = TMDB_API_KEYS[$key_index];
        $url = $base_url . $endpoint . "&api_key=" . $api_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CineMax-PHP-Bulk-Import');
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            return json_decode($response, true);
        } else {
            $_SESSION['tmdb_api_key_index'] = ($key_index + 1) % count(TMDB_API_KEYS);
            if ($http_code != 401 && $http_code != 429) {
                // For other errors, just return null
                return null;
            }
        }
    }
    return null; // All keys failed
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Bulk Import from TMDB</h2>
    <a href="index.php" class="btn btn-secondary">Back to Admin Panel</a>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Import Popular Movies</h5>
        <p>This tool will import the top 20 most popular movies for the current year from TMDB.</p>
        <form method="post">
            <button type="submit" name="import_movies" class="btn btn-primary">Import Popular Movies of <?php echo date('Y'); ?></button>
        </form>
    </div>
</div>

<?php
if (isset($_POST['import_movies'])) {
    echo '<div class="card mt-4"><div class="card-body">';
    echo '<h5>Import Log</h5>';

    $year = date('Y');
    $movies_data = fetchFromTmdb("discover/movie?sort_by=popularity.desc&primary_release_year={$year}&page=1");

    if (!$movies_data || !isset($movies_data['results'])) {
        echo '<div class="alert alert-danger">Failed to fetch popular movies from TMDB. Please check your API keys and network connection.</div>';
    } else {
        $imported_count = 0;
        $skipped_count = 0;

        // Get category ID for "Movies"
        $cat_res = $conn->query("SELECT id FROM categories WHERE name = 'Movies'");
        if ($cat_res->num_rows == 0) {
            // If "Movies" category doesn't exist, create it.
            $conn->query("INSERT INTO categories (name) VALUES ('Movies')");
            $category_id = $conn->insert_id;
        } else {
            $category_id = $cat_res->fetch_assoc()['id'];
        }

        $stmt_check = $conn->prepare("SELECT id FROM entries WHERE title = ? AND year = ?");
        $stmt_entry = $conn->prepare("INSERT INTO entries (title, description, poster, thumbnail, category_id, subcategory_id, country, rating, duration, year, parental_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_subcategory = $conn->prepare("INSERT INTO subcategories (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");

        foreach ($movies_data['results'] as $movie_summary) {
            // Check for duplicates
            $stmt_check->bind_param("si", $movie_summary['title'], $year);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows > 0) {
                echo "<p><span class='text-warning'>SKIPPED:</span> '{$movie_summary['title']}' already exists.</p>";
                $skipped_count++;
                continue;
            }

            // Fetch detailed movie info
            $movie_details = fetchFromTmdb("movie/{$movie_summary['id']}?append_to_response=release_dates");

            if (!$movie_details) {
                echo "<p><span class='text-danger'>ERROR:</span> Could not fetch details for '{$movie_summary['title']}'.</p>";
                continue;
            }

            // Prepare data for insertion
            $title = $movie_details['title'];
            $description = $movie_details['overview'];
            $poster = $movie_details['poster_path'] ? 'https://image.tmdb.org/t/p/w500' . $movie_details['poster_path'] : '';
            $thumbnail = $movie_details['backdrop_path'] ? 'https://image.tmdb.org/t/p/w500' . $movie_details['backdrop_path'] : $poster;

            $country = !empty($movie_details['production_countries']) ? $movie_details['production_countries'][0]['name'] : '';
            $rating = $movie_details['vote_average'];
            $runtime = $movie_details['runtime'];
            $duration = $runtime ? floor($runtime / 60) . 'h ' . ($runtime % 60) . 'm' : '';

            $parental_rating = '';
            if (!empty($movie_details['release_dates']['results'])) {
                $us_release_array = array_filter($movie_details['release_dates']['results'], fn($r) => $r['iso_3166_1'] == 'US');
                if (!empty($us_release_array)) {
                    $us_release = reset($us_release_array);
                    if (!empty($us_release['release_dates'][0]['certification'])) {
                        $parental_rating = $us_release['release_dates'][0]['certification'];
                    }
                }
            }

            $subcategory_id = null;
            if (!empty($movie_details['genres'])) {
                $genre_name = $movie_details['genres'][0]['name'];
                $stmt_subcategory->bind_param("s", $genre_name);
                $stmt_subcategory->execute();
                $subcat_id_res = $conn->query("SELECT id FROM subcategories WHERE name = '" . $conn->real_escape_string($genre_name) . "'");
                if ($subcat_id_res->num_rows > 0) {
                    $subcategory_id = $subcat_id_res->fetch_assoc()['id'];
                }
            }

            $stmt_entry->bind_param("ssssiisdsis", $title, $description, $poster, $thumbnail, $category_id, $subcategory_id, $country, $rating, $duration, $year, $parental_rating);

            if ($stmt_entry->execute()) {
                $entry_id = $stmt_entry->insert_id;

                // Insert embed servers
                $stmt_server = $conn->prepare("INSERT INTO servers (entry_id, name, url) VALUES (?, ?, ?)");
                $servers = [
                    ['VidSrc', "https://vidsrc.net/embed/movie/{$movie_details['id']}"],
                    ['VidJoy', "https://vidjoy.pro/embed/movie/{$movie_details['id']}"],
                    ['MultiEmbed', "https://multiembed.mov/directstream.php?video_id={$movie_details['id']}&tmdb=1"],
                    ['Embed.su', "https://embed.su/embed/movie?id={$movie_details['id']}"]
                ];

                foreach ($servers as $server) {
                    $stmt_server->bind_param("iss", $entry_id, $server[0], $server[1]);
                    $stmt_server->execute();
                }
                $stmt_server->close();

                echo "<p><span class='text-success'>IMPORTED:</span> '{$title}' with " . count($servers) . " embed servers.</p>";
                $imported_count++;
            } else {
                echo "<p><span class='text-danger'>ERROR:</span> Failed to import '{$title}'. DB Error: " . $stmt_entry->error . "</p>";
            }

            // Avoid hitting API rate limits too quickly
            usleep(250000); // 250ms delay
        }

        $stmt_check->close();
        $stmt_entry->close();
        $stmt_subcategory->close();

        echo "<hr><p><strong>Import complete.</strong> Imported: {$imported_count}, Skipped (duplicates): {$skipped_count}.</p>";
    }

    echo '</div></div>';
}

include '../footer.php';
?>
