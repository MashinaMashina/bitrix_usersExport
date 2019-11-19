<?php

$doUsersPerRun = 1000; // Users per one script run
$filename = 'users.csv';
$delimiter = ";";

/*
//
*/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

function user_validate($user)
{
	foreach($user as $k => &$v)
	{
		if ($v === 'ID')
			$v = 'USER_ID'; // excel bug fix (https://anti-anti-life.livejournal.com/28698.html)
		
		if (is_object($v) and is_callable([$v, 'toString']))
		{
			$v = $v->toString();
		}
		
		$v = iconv('UTF-8', 'windows-1251', $v);
		
		$v = str_replace(["\r\n", "\n"], " ", $v);
	}
	
	return $user;
}

$intUsersCount = CUser::GetCount();
$page = empty($_GET['page']) ? 0 : $_GET['page'];
$offset = $page * $doUsersPerRun;
$needsHeader = $page === 0 ? true : false;
$do = empty($_GET['do']) ? false : true;

if (!$do)
{
	echo 'Users count: '.$intUsersCount.'<br />';
	echo '<a href="export.php?do=true">Start export</a>';
	exit;
}

$params = [
	'order' => 'ID',
	'limit' => $doUsersPerRun,
	'offset' => $offset
];
$users = Bitrix\Main\UserTable::GetList($params);

if ($needsHeader)
{
	unlink(__DIR__.'/'.$filename);
}

$resourse = fopen(__DIR__.'/'.$filename, 'a');

$c = 0;
while($user = $users->fetch())
{
	$c++;
	unset($user['PASSWORD']);
	
	if ($needsHeader)
	{
		$needsHeader = false;
		$arrHeaderCSV = array_keys($user);
		$arrHeaderCSV = user_validate($arrHeaderCSV);
		fputcsv($resourse, $arrHeaderCSV, $delimiter);
	}
	
	$user = user_validate($user);
	fputcsv($resourse, array_values($user), $delimiter);
}

fclose($fp);

$success = false;
if ($c === 0)
{
	$success = true;
}

if ($success)
{
	echo "100% <a href='{$filename}'>Download</a>";
}
else
{
	$percent = round(($offset + $doUsersPerRun) / $intUsersCount * 100);
	$percent = ($percent > 100) ? 100 : $percent;
	echo $percent.'%';
	echo '<script>setTimeout(function(){window.location.href = "export.php?do=true&page='. ($page+1) .'";}, 1000)</script>';
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
