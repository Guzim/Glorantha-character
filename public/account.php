<?php
/*
UserCake Version: 2.0.2
http://usercake.com
*/

require_once("models/config.php");
if (!securePage($_SERVER['PHP_SELF'])){die();}
require_once("models/header.php");

echo "
<body>
<div id='wrapper'>
<div id='top'><div id='logo'></div></div>
<div id='content'>
<h1>Glorantha</h1>
<h2>Account</h2>
<div id='left-nav'>";

include("left-nav.php");

echo "
</div>
<div id='main'>
";

//Links for DB management
if ($loggedInUser->checkPermission(array(2))){
  echo "
  <ul>
  <li><a href='gl_admin_continents.php'>Manage continents</li>
  <li><a href='gl_admin_regions.php'>Manage regions</li>
  <li><a href='gl_admin_cultures.php'>Manage cultures</li>
  </ul>";
}
else {
  echo "
  <ul>
  <li>Test</li>
  </ul>
  ";
}
echo "
  </div>
  <div id='bottom'></div>
  </div>
  </body>
  </html>";
?>
