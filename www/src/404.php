<?php
$title="404 Not Found - $title";
header("HTTP/1.1 404 Not Found");
header("Status: 404 Not Found");
function printContent(){
	?>
	<h1>404 Not Found</h1>
	<?php
}