<?php

/**
 * BaseQuestionEcrite
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property string          $source                                     Type: string(255), unique
 * @property int             $legislature                                Type: integer
 * @property int             $numero                                     Type: integer
 * @property string          $date                                       Type: date, Date in ISO-8601 format (YYYY-MM-DD)
 * @property string          $date_cloture                               Type: date, Date in ISO-8601 format (YYYY-MM-DD)
 * @property string          $ministere                                  Type: string
 * @property string          $themes                                     Type: string
 * @property string          $question                                   Type: string
 * @property string          $reponse                                    Type: string
 * @property string          $motif_retrait                              Type: string
 * @property string          $content_md5                                Type: string(36)
 * @property int             $parlementaire_id                           Type: integer
 * @property string          $parlementaire_groupe_acronyme              Type: string(16)
 * @property Parlementaire   $Parlementaire                              
 *  
 * @method string            getSource()                                 Type: string(255), unique
 * @method int               getLegislature()                            Type: integer
 * @method int               getNumero()                                 Type: integer
 * @method string            getDate()                                   Type: date, Date in ISO-8601 format (YYYY-MM-DD)
 * @method string            getDateCloture()                            Type: date, Date in ISO-8601 format (YYYY-MM-DD)
 * @method string            getMinistere()                              Type: string
 * @method string            getThemes()                                 Type: string
 * @method string            getQuestion()                               Type: string
 * @method string            getReponse()                                Type: string
 * @method string            getMotifRetrait()                           Type: string
 * @method string            getContentMd5()                             Type: string(36)
 * @method int               getParlementaireId()                        Type: integer
 * @method string            getParlementaireGroupeAcronyme()            Type: string(16)
 * @method Parlementaire     getParlementaire()                          
 *  
 * @method QuestionEcrite    setSource(string $val)                      Type: string(255), unique
 * @method QuestionEcrite    setLegislature(int $val)                    Type: integer
 * @method QuestionEcrite    setNumero(int $val)                         Type: integer
 * @method QuestionEcrite    setDate(string $val)                        Type: date, Date in ISO-8601 format (YYYY-MM-DD)
 * @method QuestionEcrite    setDateCloture(string $val)                 Type: date, Date in ISO-8601 format (YYYY-MM-DD)
 * @method QuestionEcrite    setMinistere(string $val)                   Type: string
 * @method QuestionEcrite    setThemes(string $val)                      Type: string
 * @method QuestionEcrite    setQuestion(string $val)                    Type: string
 * @method QuestionEcrite    setReponse(string $val)                     Type: string
 * @method QuestionEcrite    setMotifRetrait(string $val)                Type: string
 * @method QuestionEcrite    setContentMd5(string $val)                  Type: string(36)
 * @method QuestionEcrite    setParlementaireId(int $val)                Type: integer
 * @method QuestionEcrite    setParlementaireGroupeAcronyme(string $val) Type: string(16)
 * @method QuestionEcrite    setParlementaire(Parlementaire $val)        
 *  
 * @package    cpc
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class BaseQuestionEcrite extends ObjectCommentable
{
    public function setTableDefinition()
    {
        parent::setTableDefinition();
        $this->setTableName('question_ecrite');
        $this->hasColumn('source', 'string', 255, array(
             'type' => 'string',
             'unique' => true,
             'length' => 255,
             ));
        $this->hasColumn('legislature', 'integer', null, array(
             'type' => 'integer',
             ));
        $this->hasColumn('numero', 'integer', null, array(
             'type' => 'integer',
             ));
        $this->hasColumn('date', 'date', null, array(
             'type' => 'date',
             ));
        $this->hasColumn('date_cloture', 'date', null, array(
             'type' => 'date',
             ));
        $this->hasColumn('ministere', 'string', null, array(
             'type' => 'string',
             ));
        $this->hasColumn('themes', 'string', null, array(
             'type' => 'string',
             ));
        $this->hasColumn('question', 'string', null, array(
             'type' => 'string',
             ));
        $this->hasColumn('reponse', 'string', null, array(
             'type' => 'string',
             ));
        $this->hasColumn('motif_retrait', 'string', null, array(
             'type' => 'string',
             ));
        $this->hasColumn('content_md5', 'string', 36, array(
             'type' => 'string',
             'length' => 36,
             ));
        $this->hasColumn('parlementaire_id', 'integer', null, array(
             'type' => 'integer',
             ));
        $this->hasColumn('parlementaire_groupe_acronyme', 'string', 16, array(
             'type' => 'string',
             'length' => 16,
             ));


        $this->index('uniq_num', array(
             'fields' => 
             array(
              0 => 'legislature',
              1 => 'numero',
             ),
             'type' => 'unique',
             ));
        $this->index('index_date', array(
             'fields' => 
             array(
              0 => 'date',
             ),
             ));
        $this->option('type', 'MyISAM');
        $this->option('collate', 'utf8_unicode_ci');
        $this->option('charset', 'utf8');
    }

    public function setUp()
    {
        parent::setUp();
        $this->hasOne('Parlementaire', array(
             'local' => 'parlementaire_id',
             'foreign' => 'id'));

        $timestampable0 = new Doctrine_Template_Timestampable();
        $taggable0 = new Taggable();
        $solrable0 = new Solrable(array(
             'title' => 
             array(
              0 => 'titre',
             ),
             'description' => 
             array(
              0 => 'question',
              1 => 'ministere',
              2 => 'reponse',
              3 => 'themes',
             ),
             'moretags' => 
             array(
              0 => 'Parlementaire',
              1 => 'ministere',
              2 => 'motif_retrait',
              3 => 'themes',
              4 => 'link_source',
             ),
             'date' => 'last_date',
             ));
        $this->actAs($timestampable0);
        $this->actAs($taggable0);
        $this->actAs($solrable0);
    }
}