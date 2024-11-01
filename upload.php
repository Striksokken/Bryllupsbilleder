<?php
if (!isset($_POST['name']) || empty($_POST['name'])) {
    echo json_encode(["status" => "error", "message" => "Navn er påkrævet."]);
    exit;
}

$target_dir = "uploads/";
$name = preg_replace("/[^a-zA-Z0-9]/", "", $_POST['name']); // Sanitize name

$uploaded_files = [];
foreach ($_FILES["fileToUpload"]["name"] as $key => $filename) {
    $imageFileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $target_file = $target_dir . (count(glob($target_dir . "*.*")) + 1) . ', ' . $name . '.' . $imageFileType;
    $uploadOk = 1;

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"][$key]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo json_encode(["status" => "error", "message" => "Filen er ikke et billede."]);
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["fileToUpload"]["size"][$key] > 10000000) { // 10MB
        echo json_encode(["status" => "error", "message" => "Dit billede fylder desværre for meget, maks. størrelse er 10mb."]);
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo json_encode(["status" => "error", "message" => "Det er desværre kun JPG, JPEG, PNG & GIF som er tilladt."]);
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][$key], $target_file)) {
            $uploaded_files[] = basename($target_file);
        } else {
            echo json_encode(["status" => "error", "message" => "Der var en fejl ved upload af dine billeder."]);
        }
    }
}

if (!empty($uploaded_files)) {
    echo json_encode(["status" => "success", "message" => "Filerne er nu uploadet.", "filenames" => $uploaded_files]);
}
?>
