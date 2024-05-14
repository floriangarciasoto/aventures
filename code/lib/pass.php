<?php
function getPasswordHash($password) {
	return password_hash($password,PASSWORD_BCRYPT,['cost'=>11]);
}
?>