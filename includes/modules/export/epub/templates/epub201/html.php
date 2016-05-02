<?php

// @see: \Pressbooks\Modules\Export\Export loadTemplate()

if ( ! defined( 'ABSPATH' ) )
	exit;

echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>">
<head>
	<title><?php echo $post_title; ?> -- <?php bloginfo( 'name' ); ?></title>
	<meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />
	<meta name="EPB-UUID" content="<?php echo $isbn; ?>" />

	<?php if ( ! empty( $stylesheet ) ): ?><link rel="stylesheet" href="<?php echo $stylesheet; ?>" type="text/css" /><?php endif; ?>

</head>
<body>
<?php echo $post_content; ?>
</body>
</html>