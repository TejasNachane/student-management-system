<!DOCTYPE html>
<html>
<head>
	<title>Login Page</title>
	<style> 
		.body{
			padding-top: 30px;
		}
		.h3{
			padding-top: 15px;
			font-size: 27px;
			color: #cc00cc;
		}
		.admin{
			color: #0040ff;
			font-size: 23px;
		}
		.admin:hover,.std:hover{
			background-color: blueviolet;
			color:bisque;
			border: none;
			border-radius: 5px;
		}
		.std{
			color: #0040ff;
			font-size: 23px;
		}
		#mar{
			color:white;
			background-color: blueviolet
		}
		
	</style>
</head>
<body class="body">
	<marquee id="mar"> :- CPP Project -: </marquee>
	<center><br><br>
	<h3 class="h3">Student Management System</h3><br>
	<form action="" method="POST">
		<input type="submit" name="admin_login" value="Admin Login" required class="admin">
		<input type="submit" name="student_login" value="Student Login" required class="std">
	</form>
	<?php
		if(isset($_POST['admin_login'])){
			header("Location: admin_login.php");
		}
		if(isset($_POST['student_login'])){
			header("Location: student_login.php");
		}
	?>
	</center>
</body>
</html>