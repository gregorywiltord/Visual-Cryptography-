<?php
// Establish a database connection (same as in the previous code)
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
    $name = $_POST["name"];
    $image = $_FILES["image"]["tmp_name"];
    // Process uploaded image
    $targetDirectory = "C:\Users\greg\Pictures"; // Specify the directory to store uploaded images
    $targetFile = $targetDirectory . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if the uploaded file is an image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        echo "Invalid image file.";
        exit;
    }

    // Check if the file already exists
    if (file_exists($targetFile)) {
        echo "File already exists.";
        exit;
    }

    // Check file size (adjust as per your requirements)
    if ($_FILES["image"]["size"] > 8000000000) {
        echo "File size is too large.";
        exit;
    }

    // Allow only specific file formats (e.g., JPEG, PNG)
    $allowedFormats = array("jpg", "jpeg", "png");
    if (!in_array($imageFileType, $allowedFormats)) {
        echo "Only JPG, JPEG, and PNG files are allowed.";
        exit;
    }

    // Move the uploaded file to the target directory
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        echo "Error uploading the image.";
        exit;
    }

    // Generate two shares of the uploaded image
    $share1 = "share1_" . uniqid() . ".png";
    $share2 = "share2_" . uniqid() . ".png";

    // Read the original image
    $originalImage = imagecreatefromstring(file_get_contents($targetFile));
    if ($originalImage === false) {
        echo "Error loading the image.";
        exit;
    }

    // Get the dimensions of the original image
    $imageWidth = imagesx($originalImage);
    $imageHeight = imagesy($originalImage);

    // Create share1 and share2 images
    $share1Image = imagecreatetruecolor($imageWidth, $imageHeight);
    $share2Image = imagecreatetruecolor($imageWidth, $imageHeight);

    // Generate random pixels for share1
    for ($x = 0; $x < $imageWidth; $x++) {
        for ($y = 0; $y < $imageHeight; $y++) {
            $rgb = imagecolorat($originalImage, $x, $y);

            // Generate random pixel values for share1
            $randR1 = mt_rand(0, 255);
            $randG1 = mt_rand(0, 255);
            $randB1 = mt_rand(0, 255);
            $share1Pixel = imagecolorallocate($share1Image, $randR1, $randG1, $randB1);
            imagesetpixel($share1Image, $x, $y, $share1Pixel);
        }
    }

    // Generate random pixels for share2
    for ($x = 0; $x < $imageWidth; $x++) {
        for ($y = 0; $y < $imageHeight; $y++) {
            $rgb = imagecolorat($originalImage, $x, $y);

            // Generate random pixel values for share2
            $randR2 = mt_rand(0, 255);
            $randG2 = mt_rand(0, 255);
            $randB2 = mt_rand(0, 255);
            $share2Pixel = imagecolorallocate($share2Image, $randR2, $randG2, $randB2);
            imagesetpixel($share2Image, $x, $y, $share2Pixel);
        }
    }

    // Save share1 and share2 as PNG files
    imagepng($share1Image, $share1);
    imagepng($share2Image, $share2);

    // Insert the name, password, and share1 into the database
    $sql = "INSERT INTO crypt (name,image, share1) VALUES ('$name','$image', '$share1')";
    if (mysqli_query($conn, $sql)) {
        // Provide a success message or redirect the user to another page
        echo "Registration successful!";
        echo '<a href="'.$share2.'" download>Download Share 2</a>';
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    // Clean up
    imagedestroy($originalImage);
    imagedestroy($share1Image);
    imagedestroy($share2Image);
    unlink($targetFile);
}

mysqli_close($conn);
?>