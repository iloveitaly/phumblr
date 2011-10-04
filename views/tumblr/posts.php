<div class="posts">
<?
foreach ($tumblr->posts as $post) {
	echo View::factory('tumblr/post', array(
		'post' => $post,
		'action' => $action,
		'tumblr' => $tumblr
	));
}
?>
</div>
