<script>



</script>

<?php

//demo progress bars: http://progphp.com/progress.php
//http://valums.com/files/2010/file-uploader/demo.htm
if(isset($_POST['chatFile'])){
	print_r($_FILES);
	echo "submited<br /><pre>";
	print_r($_FILES['chatFile']);
	//echo "files array:".$_FILES['upload_file']['name'];
	//print_r($_POST);
	echo "</pre>";
}
?>
<form action="" method="post" enctype='multipart/form-data'>
	File for db: <input type="file" name="chatFile" />
	<input type="submit" value="Upload File" name="submit" />
</form>