<!DOCTYPE html>
<html>
<head>
	<title>Admin Login</title>
	<style> 
		.h3{
			font-size: px;
			color: #cc00cc;
		}
		#Email,#pass{
			color: #ff0000;
			font-size: 18px;
		}
		.mail{
			height: 25px;
			width: 200px;
			border-color: gray;
		}
		.mail:hover{
			border-bottom: 6px;
		}
		.btn{
			color:blueviolet;
			font-size: 20px;
			padding-top: 5px;
			padding-left: 8px;
			padding-right: 8px;
			padding-bottom: 8px;
		}
		.btn:hover{
			color: white;
			background-color: blueviolet;
		}
	</style>
</head>
<body>
	<center><br><br>
		<h1 class="h3 ">Admin LogIn Page</h1><br>
		<form action="" method="post">
			<p id="Email"> Email ID: </p> <input type="text" name="email" required class="mail" ><br><br>
			<p id="pass"> Password: </p> <input type="password" name="password" required class="mail"><br><br>
			<input type="submit" name="submit" value="LogIn" class="btn">
		</form><br>
		
		<?php
			session_start();
			if(isset($_POST['submit'])){
				$connection = mysqli_connect("localhost","root","");
				$db = mysqli_select_db($connection,"sms");
				$query = "select * from login where email = '$_POST[email]'";
				$query_run = mysqli_query($connection,$query);
				while ($row = mysqli_fetch_assoc($query_run)) {
					if($row['email'] == $_POST['email']){
						if($row['password'] == $_POST['password']){
							$_SESSION['name'] =  $row['name'];
							$_SESSION['email'] =  $row['email'];
							header("Location: admin_dashboard.php");
						}
						else{
							?>
							<span>Wrong Password !!</span>
							<?php
						}
					}
				}
				
			}
		?>
	</center>
</body>
</html>