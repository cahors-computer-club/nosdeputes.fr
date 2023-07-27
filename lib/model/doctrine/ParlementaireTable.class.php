<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class ParlementaireTable extends PersonnaliteTable
{

  public function findOneByIdAn($id_an) {
    $id_an = preg_replace('/PA/', '', $id_an);
    $modified_ids = array(
      "267999" => "344201"
    );
    if (array_key_exists($id_an, $modified_ids))
      $id_an = $modified_ids[$id_an];
    $query = $this->createQuery('p')->where('p.id_an = ?', $id_an);
    $deputes = $query->execute();
    if (count($deputes) == 0) {
      return;
    }
    if (count($deputes) > 1) {
      throw new sfException("More than one Parlementaire found");
    }
    return $deputes[0];
  }
  public function findOneByNomAndOrga($nom, $orga_id) {
    $memeNom = $this->findByNom($nom);
    if (count($memeNom) != 1 && $orga_id) {
      $query = $this->createQuery('p')
        ->leftJoin('p.ParlementaireOrganismes o')
        ->where('p.nom = ?', $nom)
        ->andWhere('o.organisme_id = ?', $orga_id);
      $memeNom = $query->execute();
    }
    if (count($memeNom) == 0) {
      $memeNom = $this->findByNomDeFamille($nom);
      if(count($memeNom) != 1 && $orga_id) {
        $query = $this->createQuery('p')
          ->leftJoin('p.ParlementaireOrganismes o')
          ->where('p.nom_de_famille = ?', $nom)
          ->andWhere('o.organisme_id = ?', $orga_id);
        $memeNom = $query->execute();
      }
    }
    if (count($memeNom) > 1)
      throw new sfException("More than one Parlementaire found for ".$nom);
    if (count($memeNom) == 1)
      return $memeNom[0];
    return null;
  }

  public function findOneByNomSexeGroupeCirco($nom, $sexe = null, $groupe = null, $circo = null, $document = null) {
    $depute = null;
    $memeNom = $this->findByNom($nom);
    if (count($memeNom) != 1 && ($circo)) {
      $query = $this->createQuery('p')
        ->where('p.nom_de_famille = ?', $nom)
        ->andWhere('p.nom_circo LIKE ?', $circo);
      $memeNom = $query->execute();
    }
    if (count($memeNom) == 0 && ($circo)) {
      $query = $this->createQuery('p')
        ->where('p.nom = ?', $nom)
        ->andWhere('p.nom_circo LIKE ?', $circo);
      $memeNom = $query->execute();
    }
    if (count($memeNom) == 0 && ($circo)) {
      $memeNom = $this->findByNom($circo." ".$nom);
    }
    if (count($memeNom) == 0) {
      $query = $this->createQuery('p')
        ->where('p.nom_de_famille = ?' , $nom);
      if ($document) $query->andWhere('p.fin_mandat is null or p.fin_mandat >= ?', $document->getDate());
      $memeNom = $query->execute();
    }
    if (count($memeNom) > 1) {
      $exactNom = array();
      foreach ($memeNom as $d) if ($d->nom_de_famille == $nom)
        $exactNom[] = $d;
      if (count($exactNom) == 1) $memeNom = $exactNom;
    }
    if (count($memeNom) == 0 && preg_match('/(Des |des |de La |de la |de l\'|de |du )?([A-ZÉ].*) ([A-ZÉ].*)/', $nom, $match)) {
      $revert_nom = $match[3]." ".$match[1].$match[2];
      $memeNom = $this->findByNom($revert_nom);
    }
    if (count($memeNom) == 0) $depute = $this->similarTo(preg_replace('/^\s*(de |du |la )+\s*/i', '', $nom), $sexe);
    elseif (count($memeNom) == 1) $depute = $memeNom[0];
    else {
      $memeSexe = array();
      if ($sexe) {
        foreach ($memeNom as $de)
          if ($de->sexe == $sexe) array_push($memeSexe, $de);
        if (count($memeSexe) == 1) $depute = $memeSexe[0];
      }
      if (!$depute && $groupe) {
        $memeGroupe = array();
        $procheGroupe = array();
        if (count($memeSexe) == 0) $memeSexe = $memeNom;
        foreach ($memeSexe as $de) {
          $groupe2 = $de->groupe_acronyme;
          if ($groupe2 === $groupe) array_push($memeGroupe, $de);
          else foreach(myTools::convertYamlToArray(sfConfig::get('app_groupes_proximite', '')) as $gpe) {
            $gpes = explode(' / ', $gpe);
            if (($groupe2 === $gpes[0] && $groupe === $gpes[1]) || ($groupe2 === $gpes[1] && $groupe === $gpes[0]))
              array_push($procheGroupe, $de);
          }
        }
        if (count($memeGroupe) == 1) $depute = $memeGroupe[0];
        elseif (count($procheGroupe) == 1) $depute = $procheGroupe[0];
        $memeSexe = $memeGroupe;
        unset($memeGroupe);
        unset($procheGroupe);
      }
      if (!$depute) {
        $enmandat = array();
        foreach ($memeSexe as $de)
          if ($de->isEnMandat()) array_push($enmandat, $de);
        if (count($enmandat) == 1) $depute = $enmandat[0];
      }
      unset($memeSexe);
    }
    unset($memeNom);
    return $depute;
  }

  public function getShortMandatesIds() {
    $shorts = array();
    foreach ($this->createQuery('p')->where('fin_mandat IS NULL OR fin_mandat < debut_mandat')->execute() as $d) {
      $mois = $d->getNbMois(array(), true);
      if ($mois < 10) $shorts[] = $d->id;
    }
    return $shorts;
  }

  public function prepareParlementairesTopQuery($fin=false) {
    $qp = $this->createQuery('p');

    // On ne renvoie les députés au mandat fini qu'en mode bilan
    if (!$fin) {
      $qp->andWhere('fin_mandat IS NULL OR fin_mandat < debut_mandat');

      // Au début on affiche tout le monde, puis après 10 mois uniquement les députés avec au moins 10 mois de mandat
      if (!myTools::isFreshLegislature()) {
        $shorts = $this->getShortMandatesIds();
        if (count($shorts))
          $qp->andWhereNotIn('id', $shorts);
      }
    }

    $qp->orderBy('nom_de_famille');
    return $qp;
  }

  public function getPager($request, $query = NULL)
  {
    $pager = new sfDoctrinePager('Parlementaire',30);
    if (!$query) {
      $query = $this->createQuery('p')->orderBy('p.nom_de_famille ASC');
    }
    $pager->setQuery($query);
    $pager->setPage($request->getParameter('page', 1));
    $pager->init();
    return $pager;
  }
}
