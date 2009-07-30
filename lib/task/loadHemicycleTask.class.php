<?php

class loadHemicyleTask extends sfBaseTask
{
  protected function configure()
  {
    $this->namespace = 'load';
    $this->name = 'Hemicycle';
    $this->briefDescription = 'Load Hemicycle data';
  }
 
  protected function execute($arguments = array(), $options = array())
  {
    // your code here
    $dir = dirname(__FILE__).'/../../batch/hemicycle/out/';
    $manager = new sfDatabaseManager($this->configuration);    

    if (is_dir($dir)) {
      if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
	  if (preg_match('/^\./', $file))
	    continue;
	  echo "$dir$file\n";
	  foreach(file($dir.$file) as $line) {
	    $json = json_decode($line);
	    if (!$json || !$json->intervention || !$json->date || !$json->heure || !$json->source) {
	      echo "ERROR json : ";
	      echo $line;
	      echo "\n";
	      continue;
	    }
	    $id = md5($json->intervention.$json->date.$json->heure.'hemicyle'.$json->context);
	    $intervention = Doctrine::getTable('Intervention')->find($id);
	    if(!$intervention) {
	      $intervention = new Intervention();
	      $intervention->id = $id;
	      $intervention->setIntervention($json->intervention);
	      if (preg_match('/question/i', $json->context))
		$type = 'question';
	      else
		$type = 'loi';
	      $intervention->setSeance($type, $json->date, $json->heure);
	      $intervention->setSource($json->source);
	      $intervention->setTimestamp($json->timestamp);
	    }
	    if ($json->intervenant) {
	      $p = null;
	      if ($json->intervenant_url) {
		$p = doctrine::getTable('Parlementaire')->findOneByUrlAn($json->intervenant_url);
		if ($p) {
		  $intervention->setParlementaire($p);
		}
	      }
	      if (!$p) {
		$intervention->setPersonnaliteByNom($json->intervenant, $json->fonction);
	      }
	    }
	    $intervention->save();
	  }
	  unlink($dir.$file);
	}
        closedir($dh);
      }
    }
  }
}
