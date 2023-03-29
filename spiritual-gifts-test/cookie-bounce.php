<?php
/*
 * Template Name: Cookie Bounce
 */ 
setcookie("visited", "1", time()+86400*180, "/");
header('Location: '.$_SERVER['HTTP_REFERER']);
exit();
