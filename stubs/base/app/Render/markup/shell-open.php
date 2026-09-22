<?php
/**
 * The shell's opening half. One head, one skip link, one main landmark.
 *
 * @var Iniznet\Howdah\Support\ClassResolver $c
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class($c('site')); ?>>
<?php wp_body_open(); ?>
<a class="<?php echo esc_attr($c('skip-link')); ?>" href="#main"><?php esc_html_e('Skip to content', 'howdah'); ?></a>
<main id="main" class="<?php echo esc_attr($c('main')); ?>">
	<h1 class="<?php echo esc_attr($c('document-title')); ?>"><?php echo esc_html(wp_get_document_title()); ?></h1>
