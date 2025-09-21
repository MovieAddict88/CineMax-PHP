<?php
require_once 'config.php';

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read and decode JSON file
$json_data = file_get_contents('playlist-2025-09-21.json');
$data = json_decode($json_data, true);

// Prepare statements
$stmt_category = $conn->prepare("INSERT INTO categories (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
$stmt_subcategory = $conn->prepare("INSERT INTO subcategories (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
$stmt_entry = $conn->prepare("INSERT INTO entries (category_id, subcategory_id, title, country, description, poster, thumbnail, rating, duration, year, parental_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt_server = $conn->prepare("INSERT INTO servers (entry_id, episode_id, name, url, license, drm) VALUES (?, ?, ?, ?, ?, ?)");
$stmt_season = $conn->prepare("INSERT INTO seasons (entry_id, season_number, poster) VALUES (?, ?, ?)");
$stmt_episode = $conn->prepare("INSERT INTO episodes (season_id, episode_number, title, duration, description, thumbnail) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($data['Categories'] as $category) {
    // Insert category
    $stmt_category->bind_param("s", $category['MainCategory']);
    $stmt_category->execute();
    $category_id = $conn->insert_id;
    if ($category_id == 0) {
        $res = $conn->query("SELECT id FROM categories WHERE name = '" . $conn->real_escape_string($category['MainCategory']) . "'");
        $category_id = $res->fetch_assoc()['id'];
    }


    foreach ($category['SubCategories'] as $subcat_name) {
        // Insert subcategory
        $stmt_subcategory->bind_param("s", $subcat_name);
        $stmt_subcategory->execute();
    }

    foreach ($category['Entries'] as $entry) {
        // Get subcategory id
        $subcategory_id = null;
        if (!empty($entry['SubCategory'])) {
            $res = $conn->query("SELECT id FROM subcategories WHERE name = '" . $conn->real_escape_string($entry['SubCategory']) . "'");
            if ($res->num_rows > 0) {
                $subcategory_id = $res->fetch_assoc()['id'];
            }
        }

        // Insert entry
        $parentalRating = isset($entry['parentalRating']) ? $entry['parentalRating'] : null;
        $stmt_entry->bind_param("iisssssdsis", $category_id, $subcategory_id, $entry['Title'], $entry['Country'], $entry['Description'], $entry['Poster'], $entry['Thumbnail'], $entry['Rating'], $entry['Duration'], $entry['Year'], $parentalRating);
        $stmt_entry->execute();
        $entry_id = $conn->insert_id;

        // Insert servers for entry
        if (isset($entry['Servers'])) {
            foreach ($entry['Servers'] as $server) {
                $episode_id = null;
                $license = isset($server['license']) ? $server['license'] : null;
                $drm = isset($server['drm']) ? $server['drm'] : null;
                $stmt_server->bind_param("iisssi", $entry_id, $episode_id, $server['name'], $server['url'], $license, $drm);
                $stmt_server->execute();
            }
        }

        // Insert seasons and episodes
        if (isset($entry['Seasons'])) {
            foreach ($entry['Seasons'] as $season) {
                $stmt_season->bind_param("iis", $entry_id, $season['Season'], $season['SeasonPoster']);
                $stmt_season->execute();
                $season_id = $conn->insert_id;

                if (isset($season['Episodes'])) {
                    foreach ($season['Episodes'] as $episode) {
                        $stmt_episode->bind_param("isssss", $season_id, $episode['Episode'], $episode['Title'], $episode['Duration'], $episode['Description'], $episode['Thumbnail']);
                        $stmt_episode->execute();
                        $episode_id = $conn->insert_id;

                        if (isset($episode['Servers'])) {
                            foreach ($episode['Servers'] as $server) {
                                $entry_id_null = null;
                                $license = isset($server['license']) ? $server['license'] : null;
                                $drm = isset($server['drm']) ? $server['drm'] : null;
                                $stmt_server->bind_param("iisssi", $entry_id_null, $episode_id, $server['name'], $server['url'], $license, $drm);
                                $stmt_server->execute();
                            }
                        }
                    }
                }
            }
        }
    }
}

echo "Database populated successfully\n";

$stmt_category->close();
$stmt_subcategory->close();
$stmt_entry->close();
$stmt_server->close();
$stmt_season->close();
$stmt_episode->close();
$conn->close();
?>
