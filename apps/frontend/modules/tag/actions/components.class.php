<?php

class tagComponents extends sfComponents 
{
  public function executeTagcloud() {
    $this->tags = PluginTagTable::getAllTagNameWithCount($this->tagquery, array('model' => $this->model, 'triple' => false, 'min_tags_count' => $this->min_tag));

    asort($this->tags);


    //Ici on cherche à groupes les tags qui sont très similaires
    foreach(array_keys($this->tags) as $tag) {
      $sex = soundex($tag);
      if (isset($sound[$sex])) {
	foreach (array_keys($sound[$sex]) as $word) {
	  $words = preg_split('/\|/', $word);
	  similar_text($tag, $words[0], $pc);
	  if ($pc >= 75) {
	    $ntag = $tag.'|'.$word;
	    $this->tags[$ntag] = $this->tags[$tag] + $this->word[$word];
	    unset($this->tags[$tag]);
	    unset($this->tags[$word]);
	    unset($sound[$sex][$tag]);
	    unset($sound[$sex][$word]);
	    $sound[$sex][$ntag] = 1;
	    continue;
	  }
	}
      }
      $sound[$sex][$tag] = 1;
    }


    //On trie par ordre alpha, et inserre des infos sur l'utilisation des tags (class + count)
    $tot = count($this->tags);
    $cpt = 0;
    asort($this->tags);
    $class = array();
    foreach(array_keys($this->tags) as $tag) {
      $count = $this->tags[$tag];
      unset($this->tags[$tag]);
      $related = preg_split('/\|/', $tag);
      $tag = $related[0];
      $this->tags[$tag] = array();
      $this->tags[$tag]['count'] = $count;
      if (!isset($class[$count]))
	$class[$count] = intval($cpt * 4 / $tot);
      $cpt++;
      $this->tags[$tag]['class'] = $class[$count];
      $this->tags[$tag]['related'] = implode('|', $related);
    }
    ksort($this->tags);



  }
}