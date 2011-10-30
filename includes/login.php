<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/10/11
	 * Time: 7:20 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Login
	{
		static function kill()
		{
			session_unset();
			session_destroy();
		}

		static function fail()
		{
			header("HTTP/1.1 401 Authorization Required");
			echo "<center><br><br><font size='5' color='red'><b>" . _("Incorrect Password") . "<b></font><br><br>";
			echo "<b>" . _("The user and password combination is not valid for the system.") . "<b><br><br>";
			echo _("If you are not an authorized user, please contact your system administrator to obtain an account to enable you to use the system.");
			echo "<br><a href='/index.php'>" . _("Try again") . "</a>";
			echo "</center>";
			Login::kill();
			die();
		}

		static function timeout()
		{
			// skip timeout on logout page
			if (CurrentUser::instance()->logged) {
				$tout = CurrentUser::instance()->timeout;
				if ($tout && (time() > CurrentUser::instance()->last_act + $tout)) {
					CurrentUser::instance()->logged = false;
				}
				CurrentUser::instance()->last_act = time();
			}
		}
	}
