<?php

// @see: \PressBooks\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) )
	exit;

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $post_title; ?> -- <?php bloginfo( 'name' ); ?></title>
	<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />

	<?php if ( ! empty( $stylesheet ) ): ?><link rel="stylesheet" href="css/<?php echo $stylesheet; ?>" type="text/css" /><?php endif; ?>

</head>
<body>
<?php echo $post_content; ?>
</body>
</html>