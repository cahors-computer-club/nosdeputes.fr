<?php

/**
 * Texteloi
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    cpc
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Texteloi extends BaseTexteloi
{

  protected $cpt = 0;

  public function getLink() {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
    return url_for('@document?id='.$this->id);
  }
  public function getLinkSource() {
    return $this->source;
  }
  public function __toString() {
    $str = substr($this->getExtract(), 0, 250);
    if (strlen($str) == 250) {
      $str .= '...';
    } else if (!$str) $str = "";
    return $str;
  }

  public function getNature() {
    if (preg_match('/^Proposition /', $this->type))
      return "proposition";
    if (preg_match('/^(Projet|Texte)/', $this->type))
      return "texte";
    return "rapport";
  }

  public function getDossier() {
    if ($section = $this->getSection())
      return $section->getTitreComplet();
    return '';
  }

  public function getPersonne() {
    $auteurs = $this->getAuteurs();
    if (!$auteurs) return "";
    return $auteurs[0]['nom'];
  }

  public function getAmendements($help = 0) {
    return self::getAmdmts($this->type, $this->numero, $help);
  }

  public static function getAmdmts($type, $numero, $help = 0) {
    if (!($type === "Rapport" && $help) && !preg_match('/(Projet de loi|Proposition de loi|Proposition de résolution|Texte de la commission)/', $type))
      return 0;
    $res = count(Doctrine::getTable('Amendement')->createQuery('a')
      ->where('texteloi_id = ?', $numero)
      ->fetchArray());
    return $res;
  }

  public function getSection() {
    $section = Doctrine::getTable('Section')->findOneByIdDossierAn($this->id_dossier_an);
    if (!$section) $section = Doctrine_Query::create()
      ->select('s.id')
      ->from('Section s, Tagging ta, Tag t')
      ->where('s.section_id = s.id')
      ->andWhere('ta.taggable_id = s.id')
      ->andWhere('ta.tag_id = t.id')
      ->andWhere('ta.taggable_model = ?', "Section")
      ->andWhere('t.name = ?', "loi:numero=".$this->numero)
      ->fetchOne();
    return $section;
  }

  public function setDossier($urldossier) {
    $this->id_dossier_an = $urldossier;
    $section = Doctrine::getTable('Section')->findOneByIdDossierAn($urldossier);
    if ($section) {
   # cela parait plus cohérent que les dossiers apparaissent comme récemment modifiés uniquement s'ils sont discutés et pas si un nouveau rapport vient les compléter je pense mais on peut envisager de le mettre tout de même si c utile pour solr par exemple
   #   $section->setMaxDate($this->date);
   #   $section->save();
 #print "$section->id, $this->id_dossier_an\n";
      return true;
    }
    $sections = Doctrine_Query::create()
      ->select('s.id')
      ->from('Section s, Tagging ta, Tag t')
      ->where('s.section_id = s.id')
      ->andWhere('ta.taggable_id = s.id')
      ->andWhere('ta.tag_id = t.id')
      ->andWhere('ta.taggable_model = ?', "Section")
      ->andWhere('t.name = ?', "loi:numero=".$this->numero)
      ->fetchArray();
    $res = count($sections);
    if ($res == 0) {
     #print "Pas de dossier trouvé pour le texte $this->id\n";
     return false;
    } else if ($res == 1) {
      $section = Doctrine::getTable('Section')->find($sections[0]['id']);
      $section->id_dossier_an = $urldossier;
   # voir plus haut
   #  $section->setMaxDate($this->date);
      $section->save();
     #print "$section->id, $this->id_dossier_an\n";
      return true;
    } else {
     echo "$this->source : Plusieurs dossiers trouvés pour le texte $this->id de type $this->type\n";
     return false;
    }
  }

  public function setAuteurs($signataires) {
    $debug=0;
    $this->signataires = $signataires;
   //Set signatires, auteurs via PArlemnaitreTexteDocu et Organisme
    $orga = null;
    $sexe = null;
    $fonction = null;
    $signataires = preg_replace("/(Auteur|Cosignataire|Rapporteur|Rapporteur Spécial), /", "\\1#", $signataires);
    if ($debug) echo $this->source." : ".$signataires."\n";
    $signataires = preg_split('/#/', $signataires);
    foreach ($signataires as $depute) {
      if (preg_match('/^(M[\.mle]+)/', $depute, $match))
        continue;
      if (preg_match('/^(.*)(\set apparentés)?\s+(Auteur|Cosignataire|Rapporteur)/', $depute, $match)) {
        $orga = trim($match[1]).$match[2];
        $organisme = Doctrine::getTable('Organisme')->findOneByNomType($orga, 'parlementaire');
        if ($organisme) {
          $this->setOrganisme($organisme);
          if (!($this->type_details)) {
            $this->type_details = "de l";
            if (preg_match('/^[aeiouyh]/i', $organisme->nom))
              $this->type_details .= "'";
            else if (preg_match('/^comit/i', $organisme))
              $this->type_details .= "e ";
            else $this->type_details .= "a ";
            $this->type_details .= $organisme->nom;
          }
          $orga = $organisme->nom;
        } else {
          $groupe = Doctrine::getTable('Organisme')->findOneByNomType($orga, 'groupe');
          if ($groupe) {
            $this->setOrganisme($groupe);
            $orga = " pour le groupe ".$orga;
          }
        }
        break;
      }
    }
    foreach ($signataires as $depute) {
      if (preg_match('/^(M[\.mle]+)\s+(.*)\s+(Auteur|Cosignataire|Rapporteur Spécial|Rapporteur)/', $depute, $match)) {
        if (preg_match('/M[ml]/', $match[1]))
          $sexe = 'F';
        else $sexe = 'H';
        $nom = $match[2];
        $fonction = $match[3];
        if (preg_match('/^(.*)\((.*)\)/', $nom, $match)) {
          $nom = trim($match[1]);
          $circo = preg_replace('/\s/', '-', ucfirst(trim($match[2])));
        } else $circo = null;
        if (preg_match('/(ministre|[eéÉ]tat|président|haut-commissaire)/i', $nom)) {
          if ($debug) print "WARN: Skip auteur ".$nom." for ".$this->source."\n";
          continue;
        }
        $nom = ucfirst($nom);
        if ($debug) echo $nom."//".$sexe."//".$orga."//".$circo."//".$fonction." => ";
        $depute = Doctrine::getTable('Parlementaire')->findOneByNomSexeGroupeCirco($nom, $sexe, null, $circo, $this);
        if (!$depute) print "\nWARNING: Auteur introuvable in ".$this->source." : ".$nom." // ".$sexe." // ".$orga."//".$fonction."\n";
        else {
          if ($debug) echo $depute->nom."\n";
          $this->addParlementaire($depute, $fonction, $orga);
          $depute->free();
        }
      }
    }
  }

  public function addParlementaire($depute, $fonction, $organisme = 0) {
    foreach(Doctrine::getTable('ParlementaireTexteloi')->createQuery('pa')->select('parlementaire_id')->where('texteloi_id = ?', $this->id)->fetchArray() as $parldt) if ($parldt['parlementaire_id'] == $depute->id) return true;

    $pd = new ParlementaireTexteloi();
    $pd->_set('Parlementaire', $depute);
    $pd->_set('parlementaire_groupe_acronyme', $depute->groupe_acronyme);
    $pd->_set('Texteloi', $this);
    if ($fonction === "Auteur")
      $pd->_set('importance', 1);
    else if ($fonction === "Rapporteur" || $fonction === "Rapporteur Spécial")
      $pd->_set('importance', 3);
    else if ($fonction === "Cosignataire")
      $pd->_set('importance', 5);
    else print "ERREUR: fonction mauvaise pour ".$depute->nom." : ".$fonction;
    if ($organisme && $fonction != "Cosignataire") {
      if (!(preg_match('/^ pour le groupe/', $organisme))) {
        $fonction .= " pour l";
        if (preg_match('/^[aeiouyh]/i', $organisme))
          $fonction .= "'";
        else if (preg_match('/^comit/i', $organisme))
          $fonction .= "e ";
        else $fonction .= "a ";
      }
      $fonction .= $organisme;
    }
    $pd->_set('fonction', $fonction);
    if ($pd->save()) {
      $pd->free();
      return true;
    } else return false;
  }

  public function getTypeString() {
    $str = "ce";
    if (preg_match('/(propos|lettre)/i', $this->type))
      $str .= "tte";
    else if ($this->type === "Avis")
      $str .= "t";
    $str .= " ".strtolower($this->type);
    return $str;
  }

  public function getAuteurs() {
    return Doctrine_Query::create()
      ->select('p.*, pt.fonction')
      ->from('Parlementaire p')
      ->leftJoin('p.ParlementaireTexteloi pt')
      ->where('pt.importance < 4')
      ->andWhere('pt.texteloi_id = ?', $this->id)
      ->orderBy('pt.importance, p.nom_de_famille')
      ->execute();
  }

  public function getCosignataires() {
    return Doctrine_Query::create()
      ->select('p.*, pt.fonction')
      ->from('Parlementaire p')
      ->leftJoin('p.ParlementaireTexteloi pt')
      ->where('pt.importance >= 4')
      ->andWhere('pt.texteloi_id = ?', $this->id)
      ->orderBy('pt.importance, p.nom_de_famille')
      ->execute();
  }

  public function getSignatairesString() {
    $str = preg_replace('/ (Cosignataire|Auteur|Rapporteur)/', '', $this->signataires);
    return $str;
  }

  public function getCommission() {
    if ($this->type === "Texte de la commission") {
      $rap = Doctrine::getTable('Texteloi')->find("$this->numero");
      return $rap->getOrganisme();
    }
    return $this->getOrganisme();
  }

  public function getShortTitre() {
    $str = "";
    if ($this->annexe && preg_match('/a([1-9]\d*)/', $this->id, $ann)) {
      $str .= "Annexe N° ".$ann[1]." ";
      if ($this->type === "Avis")
        $str .= "à l'";
      else $str .=  "au ";
    }
    $str .= $this->type;
    if ($this->annexe && $this->annexe === "a00")
      $str .= " annexé au Rapport";
    $str .= " N° ".$this->numero;
    if ($this->annexe && preg_match('/t([\dIVX]+)/', $this->id, $tom)) {
      $str .= " (Tome ".$tom[1];
      if ($this->annexe && preg_match('/v(\d+)/', $this->id, $vol))
        $str .= " - volume ".$vol[1];
      $str .= ")";
    }
    return $str;
  }

  public function getShortTitreExplained() {
    if ($this->type === "Projet de loi")
      return $this->type." du gouvernement n°&nbsp;".$this->numero;
    return $this->getShortTitre();
  }

  public function getTitre() {
    $str = $this->getDetailsTitre();
    if ($str)
      return $this->getShortTitre()." ".$str;
    return $this->getShortTitre();
  }

  public function getDetailsTitre() {
    $str = "";
    if ($this->type_details && !preg_match('/'.$this->type_details.'/', $this->_get('titre')))
      $str .= " ".$this->type_details;
    if ($this->_get('titre'))
      $str .= " ".$this->_get('titre');
    $str = preg_replace('/^,\s*/', '', preg_replace('/\s*,\s*/', ', ', $str));
    return $str;
  }

  public function getTitreCommission() {
    $str = $this->getShortTitre();
    if ($this->_get('titre'))
      $str .= " ".$this->_get('titre');
    $str = preg_replace('/^,\s*/', '', preg_replace('/\s*,\s*/', ', ', $str));
    return $str;
  }

  public function getContenu() {
    $c = $this->_get('contenu');
    $t = base64_decode($c);
    $c = '';
    $file = tempnam(sys_get_temp_dir(), 'textloi');
    $temp = fopen($file, 'w');
    fwrite($temp, $t);
    fclose($temp);
    $temp = gzopen($file, 'r');
    $z = gzgets($temp);
    gzclose($temp);
    unlink($file);
    return $z;
  }

  public function setContenu($c) {
    $file = tempnam(sys_get_temp_dir(), 'textloi');
    $temp = gzopen($file, 'w');
    gzwrite($temp, $c);
    gzclose($temp);
    $ret = $this->_set('contenu', base64_encode(file_get_contents($file)));
    unlink($file);
    return $ret;
  }

  public function getExtract() {
    $sub = substr(strip_tags($this->contenu), 0, 30000);
    $str = preg_replace('/^.*(mesdames)/i', '\\1', $sub);
    if (!preg_match('/^mesdames/i', $str)) {
      if (preg_match('/^.*introduction(.*)$/i', $sub, $match))
        $str = $match[1];
      else return null;
    }
    $str2 = substr($str, 0, 1000);
    $str2 = preg_replace('/\s+\S+$/', '', $str2);
    if (strlen($str) > 1000) {
      $str2 .= '...';
    } else if (!$str) $str2 = "";
    return $str2;
  }

}
