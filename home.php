<?php 
    require 'functions/functions.php';
    session_start();
    // Check whether user is logged on or not
    if (!isset($_SESSION['user_id'])) {
        header("location:index.php");
    }
    $temp = $_SESSION['user_id'];
    session_destroy();
    session_start();
    $_SESSION['user_id'] = $temp;
    ob_start(); 
    // Establish Database Connection
    $conn = connect();
?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Social Network</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
    <link rel="stylesheet" href="resourcess/css/login.css">
    <link rel="stylesheet" type="text/css" href="resourcess/css/main.css">
    
</head>

<body class="body-background">

    <div class="container">
        <?php include 'includes/navbar.php'; ?>
        <br>
        <div class="createpost">
            <form method="post" action="" onsubmit="return validatePost()" enctype="multipart/form-data">
                <h2>PETSWEB PUBLICACION</h2>
                <hr>
                <span style="float:right; color:black">
                <input type="checkbox" id="public" name="public">
                <label for="public">Publico</label>
                </span>
                Ingresa el texto ademas de ingresar una imagen <span class="required" style="display:none;"> *No puedes dejarlo vacio.</span><br>
                <textarea rows="6" name="caption"></textarea>
                <center><img src="" id="preview" style="max-width:580px; display:none;"></center>
                <div class="createpostbuttons">
                    <!--<form action="" method="post" enctype="multipart/form-data" id="imageform">-->
                    <label>
                        <img src="images/photo.png">
                        <input type="file" name="fileUpload" id="imagefile">
                        <!--<input type="submit" style="display:none;">-->
                    </label>
                    <input type="submit" value="Post" name="post">
                    <!--</form>-->
                </div>
            </form>
        </div>

        <h1>Noticias</h1>

        <?php 
        // Public Posts Union Friends' Private Posts
        $sql = "SELECT posts.post_caption, posts.post_time, posts.post_public, users.user_firstname,
                        users.user_lastname, users.user_id, users.user_gender, posts.post_id
                FROM posts
                JOIN users
                ON posts.post_by = users.user_id
                WHERE posts.post_public = 'Y' OR users.user_id = {$_SESSION['user_id']}
                UNION
                SELECT posts.post_caption, posts.post_time, posts.post_public, users.user_firstname,
                        users.user_lastname, users.user_id, users.user_gender, posts.post_id
                FROM posts
                JOIN users
                ON posts.post_by = users.user_id
                JOIN (
                    SELECT friendship.user1_id AS user_id
                    FROM friendship
                    WHERE friendship.user2_id = {$_SESSION['user_id']} AND friendship.friendship_status = 1
                    UNION
                    SELECT friendship.user2_id AS user_id
                    FROM friendship
                    WHERE friendship.user1_id = {$_SESSION['user_id']} AND friendship.friendship_status = 1
                ) userfriends
                ON userfriends.user_id = posts.post_by
                WHERE posts.post_public = 'N'
                ORDER BY post_time DESC";
        $query = mysqli_query($conn, $sql);
        if(!$query){
            echo mysqli_error($conn);
        }
        if(mysqli_num_rows($query) == 0){
            echo '<div class="post">';
            echo 'No hay posts por mostrar.';
            echo '</div>';
        }
        else{
            $width = '40px'; // Profile Image Dimensions
            $height = '40px';
            while($row = mysqli_fetch_assoc($query)){
                include 'includes/post.php';
                echo '<br>';
            }
        }
        ?>
        <br><br><br>
    </div>
    
    <script src="resourcess/js/jquery.js"></script>
    <script>
        // Invoke preview when an image file is choosen.
        $(document).ready(function(){
            $('#imagefile').change(function(){
                preview(this);
            });
        });
        // Preview function
        function preview(input){
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (event){
                    $('#preview').attr('src', event.target.result);
                    $('#preview').css('display', 'initial');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        // Form Validation
        function validatePost(){
            var required = document.getElementsByClassName("required");
            var caption = document.getElementsByTagName("textarea")[0].value;
            required[0].style.display = "none";
            if(caption == ""){
                required[0].style.display = "initial";
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

<?php
    if($_SERVER['REQUEST_METHOD'] == 'POST') { // Form is Posted
        // Assign Variables
        $caption = $_POST['caption'];
        if(isset($_POST['public'])) {
            $public = "Y";
        } else {
            $public = "N";
        }
        $poster = $_SESSION['user_id'];
        // Apply Insertion Query
        $sql = "INSERT INTO posts (post_caption, post_public, post_time, post_by)
                VALUES ('$caption', '$public', NOW(), $poster)";
        $query = mysqli_query($conn, $sql);
        // Action on Successful Query
        if($query){
            // Upload Post Image If a file was choosen
            if (!empty($_FILES['fileUpload']['name'])) {
                echo 'FUUUQ';
                // Retrieve Post ID
                $last_id = mysqli_insert_id($conn);
                include 'functions/upload.php';
            }
            header("location: home.php");
        }
    }
?>