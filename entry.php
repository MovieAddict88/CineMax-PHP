<!DOCTYPE html>
<html>
<head>
    <title>Entry</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <?php
    include 'db.php';
    $entry_id = $_GET['id'];

    // Fetch entry details
    $stmt = $conn->prepare("SELECT * FROM entries WHERE id = ?");
    $stmt->bind_param("i", $entry_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $entry = $result->fetch_assoc();
        echo '<h1>' . htmlspecialchars($entry['title']) . '</h1>';
        echo '<img src="' . htmlspecialchars($entry['poster']) . '" alt="' . htmlspecialchars($entry['title']) . '" width="200">';
        echo '<p><strong>Country:</strong> ' . htmlspecialchars($entry['country']) . '</p>';
        echo '<p><strong>Year:</strong> ' . htmlspecialchars($entry['year']) . '</p>';
        echo '<p><strong>Rating:</strong> ' . htmlspecialchars($entry['rating']) . '</p>';
        echo '<p><strong>Duration:</strong> ' . htmlspecialchars($entry['duration']) . '</p>';
        echo '<p><strong>Parental Rating:</strong> ' . htmlspecialchars($entry['parental_rating']) . '</p>';
        echo '<p><strong>Description:</strong> ' . htmlspecialchars($entry['description']) . '</p>';

        // Fetch servers for the entry
        $stmt_servers = $conn->prepare("SELECT * FROM servers WHERE entry_id = ?");
        $stmt_servers->bind_param("i", $entry_id);
        $stmt_servers->execute();
        $result_servers = $stmt_servers->get_result();
        if ($result_servers->num_rows > 0) {
            echo '<h2>Servers</h2>';
            echo '<ul>';
            while($server = $result_servers->fetch_assoc()) {
                echo '<li>' . htmlspecialchars($server['name']) . ': <a href="' . htmlspecialchars($server['url']) . '">' . htmlspecialchars($server['url']) . '</a></li>';
            }
            echo '</ul>';
        }
        $stmt_servers->close();

        // Fetch seasons and episodes if it is a TV Series
        $stmt_seasons = $conn->prepare("SELECT * FROM seasons WHERE entry_id = ? ORDER BY season_number");
        $stmt_seasons->bind_param("i", $entry_id);
        $stmt_seasons->execute();
        $result_seasons = $stmt_seasons->get_result();
        if ($result_seasons->num_rows > 0) {
            echo '<h2>Seasons</h2>';
            while($season = $result_seasons->fetch_assoc()) {
                echo '<h3>Season ' . htmlspecialchars($season['season_number']) . '</h3>';

                $stmt_episodes = $conn->prepare("SELECT * FROM episodes WHERE season_id = ? ORDER BY episode_number");
                $stmt_episodes->bind_param("i", $season['id']);
                $stmt_episodes->execute();
                $result_episodes = $stmt_episodes->get_result();

                if ($result_episodes->num_rows > 0) {
                    echo '<ul>';
                    while($episode = $result_episodes->fetch_assoc()) {
                        echo '<li>';
                        echo '<h4>' . htmlspecialchars($episode['episode_number']) . '. ' . htmlspecialchars($episode['title']) . '</h4>';
                        echo '<img src="' . htmlspecialchars($episode['thumbnail']) . '" alt="' . htmlspecialchars($episode['title']) . '" width="150">';
                        echo '<p><strong>Duration:</strong> ' . htmlspecialchars($episode['duration']) . '</p>';
                        echo '<p><strong>Description:</strong> ' . htmlspecialchars($episode['description']) . '</p>';

                        // Fetch servers for the episode
                        $stmt_episode_servers = $conn->prepare("SELECT * FROM servers WHERE episode_id = ?");
                        $stmt_episode_servers->bind_param("i", $episode['id']);
                        $stmt_episode_servers->execute();
                        $result_episode_servers = $stmt_episode_servers->get_result();

                        if ($result_episode_servers->num_rows > 0) {
                            echo '<h5>Servers</h5>';
                            echo '<ul>';
                            while($server = $result_episode_servers->fetch_assoc()) {
                                echo '<li>' . htmlspecialchars($server['name']) . ': <a href="' . htmlspecialchars($server['url']) . '">' . htmlspecialchars($server['url']) . '</a></li>';
                            }
                            echo '</ul>';
                        }
                        $stmt_episode_servers->close();
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                $stmt_episodes->close();
            }
        }
        $stmt_seasons->close();
    } else {
        echo "Entry not found.";
    }
    $stmt->close();
    $conn->close();
    ?>
    <br>
    <a href="javascript:history.back()">Back</a>
</body>
</html>
