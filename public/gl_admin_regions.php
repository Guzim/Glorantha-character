<?php
/*
  This page show all the regions and enable to manage it.
*/

require_once("models/config.php");
if (!securePage($_SERVER['PHP_SELF'])){die();}

require_once("models/header.php");

require('rb.php');
R::setup('mysql:host=localhost; dbname=glorantha','root','admin');

/* ERROR CODES */
define ('ERR_EXIST', -1);  /* object already exists */
define ('ERR_MISSG', -2);  /* object doesn't exist  */
define ('ERR_INTEG', -3);  /* integrity error       */

/* OPERATION CODES */
define ('OPE_ADD', 1);
define ('OPE_SUP', 2);
define ('OPE_MOD', 3);

/* Check name unicity */
function check_unicity($name) {
  $nb = R::count('regions','name = ?', array($name));
  if (0 == $nb) return ERR_MISSING;
  return ERR_EXIST;
};

/* CRUD functions (except READ) to manage regions */ 
function manage_a_region($ope, $name, $description, $cont_id) {
  echo '<!-- description ' . $description . ' -->';
  if (OPE_ADD != $ope) {
    if (func_num_args() < 5) {
      echo 'function manage_a_region missing argument for operation' . $ope;
      echo 'Nb_args: ' . func_num_args () . '<br>';
      var_dump(func_get_args());
      echo '<br>';
      return;
    }
    $id = intval(func_get_arg(4));
  }
  // check continent
  $continent = R::load('continents', intval($cont_id));
  if (!$continent->id) {
      echo 'Error : continent ' . $cont_id . 
           ' doesn\'t exist in the database<br>';
      return;
  }
  if ((OPE_MOD == $ope) || (OPE_SUP == $ope)) {
    if (array_search (intval($cont_id), $continent->ownRegions, true)) {
       echo 'Error: the ' . $name . ' doesn\'t belong to continent ' .
             $continent->name . '.<br>';
    } 
  }

  switch ($ope) {
    case OPE_ADD:
      $region =  R::dispense('regions');
      $region->name = $name;
      $region->description = $description;
      if (ERR_MISSING == check_unicity($name)) { R::store($region); }
      else {
        echo 'Another region already has this name: ' . $name . '.<br>';
        break;
      }
      $continent->ownRegions[] = $region;
      R::store($continent);
      break;
    case OPE_MOD:
      $region =  R::load('regions', $id);
      if (!$region->id) break;
      if ($region->name != $name) {
        if (ERR_EXIST == check_unicity($name)) {
          echo 'A region already has this name: ' . $name . '.<br>';
          break;
        }
      }
      $region->name = $name;
      $region->description = $description;
      $continent->ownRegions[] = $region;
      R::store($region);
      R::store($continent);
      break;
    case OPE_SUP:
      echo '<!-- Suppression de ' . $id . ' -->';
      $region =  R::load('regions', $id);
      if (!$region->id) { 
        echo '<!-- region not found -->'; 
        break;
      }
      R::trash($region);
      break;
  }
};

/* fill a select object with continents */
function options_continent() {
  /* optional argument, id if a continent has already been selected. */
  if (1==func_num_args()) $selected_id=intval(func_get_arg(0));
  else $selected_id=null;
  $continents =  R::findAll('continents');
  foreach ($continents as $key => $cont) {
    $myString= '<option value = \'' . $key . '\'';
    if ($key == $selected_id) $myString = $myString . 'selected';
    echo $myString . '>' . $cont->name . '</option>';
  } 
};

echo "
<body>
<div id='wrapper'>
<div id='top'><div id='logo'></div></div>
<div id='content'>
<h1>Glorantha</h1>
<h2>Manage Regions</h2>
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
    if (($key=='add') && ($_GET['region_add'])) {
      manage_a_region(OPE_ADD, $_GET['region_add'],
                         $_GET['description_add'],
                         $_GET['continents_id_add']);
      break;
    }
    /* delete */
    if (!substr_compare($key, 'delete', 0, 6, true)) {
      $id = substr ($key, 7);
      manage_a_region(OPE_SUP, $_GET['region_' . $id],
                         $_GET['description_' . $id],
                         $_GET['continents_id_' . $id], $id);
      break;
    } 
    /* modify */
    if (!substr_compare($key, 'modify', 0, 6, true)) {
      $id = substr ($key, 7);
      manage_a_region(OPE_MOD, $_GET['region_' . $id],
                         $_GET['description_' . $id],
                         $_GET['continents_id_' . $id], $id);
      break;
    } 
  }
}

echo "
<form name='regions' action='gl_admin_regions.php' action='GEST'>
<div id='main'>
<table>
  <tr>
    <th>Region</th>
    <th>Continent</th>
    <th>Description</th>
    <th>Actions</th>
  </tr>
";

$nb = R::count('regions');
$regions = R::findAll('regions');
foreach ($regions as $i) {  
  echo "
  <tr>
    <td><input type='text' name='region_$i->id' value='$i->name'></td>
    <td><select name='continents_id_$i->id' value='$i->continents_id'>";
  options_continent($i->continents_id);
  echo "
        </select>
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
    <td><input type='text' name='region_add'></td>
    <td><select name='continents_id_add'>";
  options_continent();
  echo "
        </select>
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
