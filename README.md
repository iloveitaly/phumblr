This code provides what is essentially a content mirror Tumblr site, using the Tumblr REST API. I wrote this for my own use, as I wanted to use Tumblr but have a bit more control over the actual page layout and host it on my own server. It includes a cache to ensure that you are not hitting the Tumblr API constantly.

Based on Kohana 2 (but would be easy to adapt to any other PHP framework). Easy to implement:

	...
	public function blog($action = "") {
		$tumblr = new Tumblr();
		$this->template->content = $tumblr->process($action);
	}
	...

What it does NOT do:  
- It does not provide archive or RSS, and still redirects to Tumblr for those services.  
- It does not maintain any themes or HTML from Tumblr, other than the actual contents of your posts.
