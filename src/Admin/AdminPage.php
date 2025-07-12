<?php

namespace Jk\Vts\Admin;

class AdminPage {
	public function register() {
		add_menu_page(
			'Vimeo Transcript Search',
			'Vimeo Transcript Search',
			'manage_options',
			'vimeo-transcript-search',
			[$this, 'render']
		);
	}

	public function render() {
		?>
		<div class="wrap">
			<h1>Vimeo Transcript Search</h1>
			<p>This is the admin page.</p>
		</div>
		<?php
	}
}
