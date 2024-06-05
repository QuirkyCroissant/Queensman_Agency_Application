<?php session_start();

        if(isset($_POST['Submit'])){
                //array with login credentials
                $auth_array = array('Merlin' => 'secret');
                
                $user = isset($_POST['user']) ? $_POST['user'] : '';
                $pswd = isset($_POST['pswd']) ? $_POST['pswd'] : '';
                
                /* Check Username and Password existence in defined array */            
                if (isset($auth_array[$user]) && $auth_array[$user] == $pswd){
                        /* Success: Set session variables and redirect to Protected page  */
                        $_SESSION['UserData']['user']=$auth_array[$user];
						$_SESSION['Username']= $user;
                        header("location:index.php");
                        exit;
                } else {
                        /*Unsuccessful attempt: Set error message */
                        $msg="<span style='color:red'>Invalid Login! <br />After multiple failed attempts your PC will explode !</span>";
                }
        }
	?>


<html>
	<head>
		<title>Queensman</title>
		<link rel="stylesheet" href="design.css">
		<link rel="icon" type="image/x-icon" href="favicon.ico">

	</head>

	<body>
		
		<div class="login">

			<div style="text-align: left">
				<h2>Welcome back to Queensman Intranet</h2><br/>
				Let's kick some bottom.
			</div>

			<form action="" method="post" name="login_form">
				
				<?php if(isset($msg)){?>
						<span><?php echo $msg;?></span>
				<?php } ?>


				<div class="logo">
					<img class="img" src="Queensman_logo_green.png">
				
				<table class="login_table">
					<tr>
						<th> <label for="uname"><h3>Login</h3></label> </th>
						<th> <label for="psw"><h3>Password</h3></label> </th>
					</tr>
					<tr>
						<td> <input type="text" placeholder="QM User" name="user" required> </td>
						<td> <input type="password" placeholder="Password" name="pswd" required> </td>
					</tr>
					<tr style="text-align: center">
						<td ><input name="Submit" type="submit" value="Login" class="Button3"></td>
					</tr>
				</table>
				</div>	
				</br>
				Forgot <a href="#look_into_the_sourcecode_Agent">password?</a>
				<!--"Merlin" and "secret" are definitely not the login codes-->
				
			</form>
			
		</div>
	</body>

</html>
