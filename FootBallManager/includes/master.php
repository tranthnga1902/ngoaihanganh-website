<?php 
include __DIR__ . '/config.php';
 ?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $title ?? ""; ?></title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/index.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/footer.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/header.css">

  
   
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/search.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="<?php echo BASE_URL; ?>https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <script src="<?php echo BASE_URL; ?>https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>



</head>
<body>
  <?php include __DIR__ . '/header.php'; ?>

  <?php echo $content ?? ""; ?>

  <?php include __DIR__ . '/footer.php'; ?>

  <!-- back to top button -->
  <button id="back-to-top" style="display: none;">
        <i class="fa fa-arrow-up"></i>
  </button>

</body>

</html>