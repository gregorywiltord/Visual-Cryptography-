<?php
// Establish a secure database connection using prepared statements and parameterized queries
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cryptography";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user input to prevent SQL injection and other attacks
    $name = mysqli_real_escape_string($conn, $_POST["name"]);

    // Validate uploaded file to ensure it's an image
    $share2File = $_FILES["share2"]["tmp_name"];
    $share2Type = mime_content_type($share2File);
    if ($share2Type != "image/png") {
        echo "Invalid file type. Only PNG files are allowed.";
        exit;
    }

    // Verify that the user exists and has a valid share1
    $stmt = mysqli_prepare($conn, "SELECT share1 FROM crypt WHERE name = ?");
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $share1Filename = $row["share1"];

        // Read share1 from the server
        $share1Image = imagecreatefrompng($share1Filename);
        if ($share1Image === false) {
            echo "Error loading Share 1 image.";
            exit;
        }

        // Read share2 uploaded by the user
        $share2Image = imagecreatefrompng($share2File);
        if ($share2Image === false) {
            echo "Error loading Share 2 image.";
            exit;
        }

        // Validate share dimensions
        $share1Width = imagesx($share1Image);
        $share1Height = imagesy($share1Image);
        $share2Width = imagesx($share2Image);
        $share2Height = imagesy($share2Image);

        if ($share1Width != $share2Width || $share1Height != $share2Height) {
            echo "Invalid share dimensions.";
            exit;
        }

        // Create a new image to combine the shares
        $originalImage = imagecreatetruecolor($share1Width, $share1Height);

        // Combine the shares by performing bitwise XOR operation on pixels
        for ($x = 0; $x < $share1Width; $x++) {
            for ($y = 0; $y < $share1Height; $y++) {
                $share1Pixel = imagecolorat($share1Image, $x, $y);
                $share2Pixel = imagecolorat($share2Image, $x, $y);
                $originalPixel = $share1Pixel ^ $share2Pixel;

                imagesetpixel($originalImage, $x, $y, $originalPixel);
            }
        }

        // Output the original image to the browser
        //header('Content-Type: image/png');
        //imagepng($originalImage);

        // Clean up
        imagedestroy($share1Image);
        imagedestroy($share2Image);
        imagedestroy($originalImage);

        // Display success message
        header('Location: voting.html');
        exit;
    } else {
        echo "Invalid username or Share 1 not found.";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);