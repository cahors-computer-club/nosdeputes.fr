<?php

class tagSeanceTask extends sfBaseTask
{
  protected function configure()
  {
    $this->namespace = 'tag';
    $this->name = 'Seance';
    $this->briefDescription = 'Tag Seance';
  }
 
  protected function count($array, $excludeS = 0) {
    foreach($array as $i) {
      $i = preg_replace('/\([^\)]+\)/', '', $i);
      $i = preg_replace('/&#339;/', 'oe', $i['intervention']);
      foreach(preg_split('/[\s\,\;\.\:\_\(\)\&\#\<\>\']+/i', $i) as $w) {
	$w = strtolower($w);
	if (strlen($w)>1 && preg_match('/[a-z]/', $w)) {
	  //	  $s = soundex($w);
	  $s = $w;
	  $words[$s]++;
	  if (!$this->sound[$s])
	    $this->sound[$s] = $w;
	}
      }
    }
    foreach(array_keys($words) as $k) {
      if (preg_match('/s$/', $k)) {
	$ks = preg_replace('/s$/', '', $k);
	if ($words[$ks]) {
	  $words[$ks]+=$words[$k];
	  if ($excludeS)
	    unset($words[$k]);
	}
      }
    }
    arsort($words);
    return $words;
  }

  protected function execute($arguments = array(), $options = array())
  {

    // your code here
    $manager = new sfDatabaseManager($this->configuration);    
    $q = Doctrine_Query::create();
    $q->select('intervention')->from('Intervention i')->where('i.parlementaire_id IS NOT NULL');
    echo "count:\n\t";
    echo $q->count()."\n";
    $array = $q->fetchArray();
    $words = $this->count($array);
    $cpt = 0;
    $tot = count($words);

    $exclude = array('lecture'=>1, 'séance'=>1, 'alinéa'=>1, 'résolution'=>1, 'adoption'=>1, 'collègue'=>1, 'cher'=>1, 'collègues'=>1, 'chers'=>1,'bis'=>1, '1er'=>1, 'rectifié'=>1, 'question'=>1, 'rédactionnel'=>1, 'scrutin'=>1, 'exposer'=>1, 'identiques'=>1, 'identique'=>1);
    $include = array('télévision' => 1, 'dimanche'=>1, 'internet'=>1, 'outre-mer'=>1, 'logement'=>1, 'militaire'=>1, 'taxe'=>1, 'médecin'=>1, 'hôpital'=>1);

    foreach(array_keys($words) as $k) {
      if (!$include[$k])
	$exclude[$k] = 1;
      echo $k.': '.$words[$k]*100/$tot."\n";
      if ($words[$k]*100/$tot < 3)
	break;
    }

    $q = Doctrine_Query::create();
    $q->select('nom as intervention')->from('Parlementaire o');
    $array = $q->fetchArray();
    $words = $this->count($array);
    foreach(array_keys($words) as $k) {
      $exclude[$k] = 1;
    }

    $qs = doctrine::getTable('Seance')->createQuery()->select('id')->where('tagged IS NULL');

    foreach($qs->fetchArray() as $s) {

      //Recherche toutes les interventions pour cette séance
      $q = Doctrine_Query::create();
      $q->select('intervention, id')->from('Intervention i')->where('seance_id = ?', $s['id'])->andWhere('i.parlementaire_id IS NOT NULL');//->andWhere('(i.fonction IS NULL OR i.fonction NOT LIKE ? )', 'président%');

      $array = $q->fetchArray();
      if (!count($array))
	continue;
      $words = $this->count($array, 1);
      $cpt = 0;
      $tot = count($words);
      $tags = array();
      //Pour les mots le plus populaires non exclus on les gardes
      foreach(array_keys($words) as $k) {
	if (!$exclude[$k]) {
	  $cpt++;
	  $pc = $words[$k]*100/$tot;
	  if ($pc < 0.8)
	    break;
	  $tags[$k] = strlen($k);
	}
      }

      $sentences = null;
      $sent2word = null;
      //On cherche des groupes de mots commums à partir des tags trouvés
      foreach ($array as $inter) {
	$i = null;
	foreach (array_keys($tags) as $tag) {
	  if (preg_match('/([^\s\,\.\:\>\;\(\)]*[^\,\.\:\>\;\(\)]{6}'.$tag.'[^\s\,\.\:\<\&\(\)]*)/i', $inter['intervention'], $match)) {
	      $sentences[$match[1]]++;
	      $sent2word[$match[1]] = $tag;
	  }
	  if (preg_match('/([^\s\,\.\:\>\;\)\)]*'.$tag.'[^\,\.\:\<\&\(\)]{6}[^\s\,\.\:\<\&\(\)]*)/i', $inter['intervention'], $match)) {
	    $sentences[$match[1]]++;
	    $sent2word[$match[1]] = $tag;
	  }
	}
      }
      //asort($sentences);

      //Si les groupes de mots ont une certernaines popularité, on les garde
      //Au dessus de 70% d'utilisation le tag contenu est supprimé
      $debut_bani = 'à|de|la|ainsi|ensuite';
      if (count($sentences)) {
	foreach (array_keys($sentences) as $sent) {
	  
	  if  (preg_match("/^($debut_bani)/i", $sent) || preg_match("/($debut_bani)$/i", $sent) || preg_match('/\d|amendement|rapporteur|commision|collègue/i', $sent) ) 
	    continue;
	  
	  if (preg_match('/^[A-Z][a-z]/', $sent)) {
	    unset($tags[$sent2word[$sent]]);	  
	    continue;
	  }
	  
	  if (preg_match('/^([a-z]{2} |[A-Z]+)/', $sent) || preg_match('/ [a-z]$/i', $sent)) {
	    continue;
	  }
	  
	  if (($sentences[$sent]*100/$tot > 0.8 || $sentences[$sent]*100/$words[$sent2word[$sent]] > 70)&& $words[$sent2word[$sent]] > 5) {
	    $tags[$sent] = strlen($sent);
	    if ($sentences[$sent]*100/$words[$sent2word[$sent]] > 70)
	      unset($tags[$sent2word[$sent]]);
	  }
	}
      }

      //On cherche maintenant les tags dans les interventions pour les associer
      arsort($tags);
      foreach ($array as $inter) {
	$i = null;
	foreach (array_keys($tags) as $tag) {
	  if (preg_match('/'.$tag.'/i', $inter['intervention'])) {
	    if (!$i) 
	      $i = doctrine::getTable('Intervention')->find($inter['id']);
	    $i->addTag($tag);
	  }
	}
	if ($i) {
	  $i->getSeance()->tagged = 1;
	  $i->getSeance()->save();
	  $i->save();
	}
      }
      echo $s['id']." seance done\n";
    }
  }
}
