<?php
/*
  This page show all the continents and enable to manage it.
*/

require_once("models/config.php");
if (!securePage($_SERVER['PHP_SELF'])){die();}

require_once("models/header.php");

require('rb.php');
R::setup('mysql:host=localhost; dbname=glorantha','root','admin');

/* ERROR CODES */
define ('ERR_EXIST', -1);    /* object already exists */
define ('ERR_MISSING', -2);  /* object doesn't exist  */

/* OPERATION CODES */
define ('OPE_ADD', 1);
define ('OPE_SUP', 2);
define ('OPE_MOD', 3);

/* Check name unicity */
function check_unicity($name) {
  $nb = R::count('continents','name = ?', array($name));
  if (0 == $nb) return ERR_MISSING;
  return ERR_EXIST;
};

/* CRUD functions (except READ) to manage continents */ 
function manage_a_continent($ope, $name, $description) {
  if (OPE_ADD != $ope) {
    if (func_num_args() < 4) {
      echo 'function manage_a_continent missing argument for operation' . $ope;
      echo 'Nb_args: ' . func_num_args () . '<br>';
      var_dump(func_get_args());
      echo '<br>';
      return;
    }
    $id = intval(func_get_arg(3));
  }
  switch ($ope) {
    case OPE_ADD:
      $continent =  R::dispense('continents');
      $continent->name = $name;
      $continent->description = $description;
      if (ERR_MISSING == check_unicity($name)) { R::store($continent); }
      else echo 'continents already have this name: ' . $name . '.<br>';
      break;
    case OPE_MOD:
      $continent =  R::load('continents', $id);
      if (!$continent->id) break;
      if ($continent->name != $name) {
        if (ERR_EXIST == check_unicity($name)) { 
          echo 'A continent already has this name: ' . $name . '.<br>';
          break;
        }
      }
      $continent->name = $name;
      $continent->description = $description;
      R::store($continent);
      break;
    case OPE_SUP:
      $continent =  R::load('continents', $id);
      if (!$continent->id) break;
      if (0 != count($continent->ownRegions)) {
          echo 'Impossible to remove ' . $continent->name .
               ' as regions are still affected to this continent.<br>';
          break;
      }
      R::trash($continent);
      break;
  }
};

echo "
<body>
<div id='wrapper'>
<div id='top'><div id='logo'></div></div>
<div id='content'>
<h1>Glorantha</h1>
<h2>Manage Continents</h2>
<div id='left-nav'>";
include("left-nav.php");
echo "
</div>
";
/*  It's a re-entrance page. It means that the "form" actions are
  directed to this page.
  That's why we deal with the $_GET variable first.
*/
if ($_SERVER["REQUEST_METHOD"] == "GET") {
  /* Detect the action */
  foreach ($_GET as $key => $value) {
    if (($key=='add') && ($_GET['continent_add'])) {
      manage_a_continent(OPE_ADD, $_GET['continent_add'],
                         $_GET['description_add']);
      break;
    }
    /* delete */
    if (!substr_compare($key, 'delete', 0, 6, true)) {
      $id = substr ($key, 7);
      manage_a_continent(OPE_SUP, $_GET['continent_' . $id],
                         $_GET['description_' . $id], $id);
      break;
    } 
    /* modify */
    if (!substr_compare($key, 'modify', 0, 6, true)) {
      $id = substr ($key, 7);
      manage_a_continent(OPE_MOD, $_GET['continent_' . $id],
                         $_GET['description_' . $id], $id);
      break;
    } 
  }
}
echo "
<form name='continents' action='gl_admin_continents.php' action='GEST'>
<div id='main'>
<table>
  <tr>
    <th>Continent</th>
    <th>Description</th>
    <th>Actions</th>
  </tr>
";

$nb=R::count('continents');
$continents=R::findAll('continents');
foreach ($continents as $i) {  
  echo "
  <tr>
    <td><input type='text' name='continent_$i->id' value='$i->name'></td>
    <td><input type='text' name='description_$i->id' value='$i->description'></td>
    <td>
      <input type='submit' name='modify_$i->id' value='Modifier'>
      <input type='submit' name='delete_$i->id' value='Supprimer'>
      </td>
  </tr>
  ";
}
echo "
  <tr>
    <td><input type='text' name='continent_add'></td>
    <td><input type='text' name='description_add'></td>
    <td><input type='submit' name='add' value='Ajouter'></td>
  </tr>
</table>
<input type='submit' value='valider'>
</form>
</div>
<div id='bottom'></div>
</div>
</body>
</html>";

?>
