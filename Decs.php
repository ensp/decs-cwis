<?php

/**
 * @file
 * Decs Plugin imported from Drupal6 decs module
 * 
 */
class Decs extends Plugin {

	var $language = "es";

    // register the plugin 
    public function Register(){
        $this->Name = "Decs";
        $this->Version = "0.1";
        $this->Description = "This plugin add the Decs field in the resource form";
        $this->Author = "CCI/ENSP";
        $this->Email = "cci@ensp.fiocruz.br";
    }

    /**
     *  Add the field decs to the database.
     *
     *  @TODO Need fix the FieldId now the plugin is using 987654 as id but this
     *  can be improved. 
     */
    function Install(){

      $DB = new Database();

      $DB->Query("INSERT INTO MetadataFields ( FieldId , FieldName , FieldType , Description ,
                  RequiredBySPT , Enabled , Optional , Viewable , AllowMultiple , IncludeInKeywordSearch ,
                  IncludeInAdvancedSearch , IncludeInRecommender , TextFieldSize , MaxLength , ParagraphRows ,
                  ParagraphCols , DefaultValue , MinValue , MaxValue , FlagOnLabel , FlagOffLabel ,
                  DateFormat , DateFormatIsPerRecord , SearchWeight , RecommenderWeight , MaxHeight ,
                  MaxWidth , MaxPreviewHeight , MaxPreviewWidth , MaxThumbnailHeight , MaxThumbnailWidth ,
                  DefaultAltText , UsesQualifiers , HasItemLevelQualifiers , DefaultQualifier , DateLastModified , LastModifiedById ,
                  DisplayOrderPosition , EditingOrderPosition , UseForOaiSets , ViewingPrivilege ,
                  AuthoringPrivilege , EditingPrivilege , PointPrecision , PointDecimalDigits , UpdateMethod )
                  VALUES (
                      '987654', 'Decs', 'Text', 'Decs Field', NULL , '1', '1', '1', NULL , NULL , NULL , 
                      NULL , '75', NULL , NULL , NULL , NULL , NULL , NULL , NULL , NULL , NULL , NULL , 
                      '3', '0', NULL , NULL , NULL , NULL , NULL , NULL , NULL , '0', '0', NULL , NOW( ) ,
                      NULL , '8', '8', '0', '0', '12', '3', NULL , NULL , 'NoAutoUpdate'
                  );");

      $DB->Query("ALTER TABLE Resources ADD Decs text;");

    }

    /**
     *  Implements the Hook event to add the decs field
     *
     */
    function HookEvents(){
      return array(
          "EVENT_IN_HTML_HEADER" => "adcionarCampoDecs"
      ); 
    }

    /**
     *  Print the decs .css and .js in the page EditResource
     */
    function adcionarCampoDecs(){

      if( isset( $_GET['P'] ) ){
        if( $_GET['P'] == 'EditResource' ){
          print '<script type="text/javascript" src="plugins/Decs/decs.js"></script>'.
            '<link media="all" rel="stylesheet" type="text/css" href="plugins/Decs/decs.css">';
        }
      }
    }
    
  /**
   *  Get the descriptors from decs.bvsalud
   *  
   *  @return array
   *  @TODO this version return only portuguese descriptor and need work
   *  on all lanquages.
   */
  public function getDescriptorsByWords($words, $lang){
	  
	  if(empty($lang)) {
		$lang = $this->language;
	  }

    $xmlFile = Decs::getDescriptorsFromDecs( 'http://decs.bvsalud.org/cgi-bin/mx/cgi=@vmx/decs/?bool=' . urlencode($words) . "&lang=" . $lang ); 

    $xmlTree = $xmlFile->xpath("/decsvmx/decsws_response");

    $descriptors = array();

    foreach($xmlTree as $node){

	    $descriptors[(string) $node->tree->self->term_list->term] = array('tree_id'=>(string) $node['tree_id']);		
	    foreach($node->record_list->record->synonym_list->synonym as $synonym)
		    $descriptors[ (string) $synonym  ] = array('tree_id'=>(string) $node['tree_id']);
    }

    return array('descriptors'=>$descriptors);

  }

  /**
   *  Get the descriptors tree id from decs.bvsalud
   *  
   *  @return array 
   *  @TODO this version only work with lanquage pt-bt and need work with all decs langs
   */
  public function getDescriptorsByTreeId($treeId){

      $descriptors = array();	

      $result = array();

      $xmlFile = Decs::getDescriptorsFromDecs( 'http://decs.bvsalud.org/cgi-bin/mx/cgi=@vmx/decs?tree_id=' . $treeId );

      $term = $xmlFile->xpath("/decsvmx/decsws_response/tree/self/term_list[@lang='pt']/term");
      $definition = $xmlFile->xpath("/decsvmx/decsws_response/record_list/record/definition/occ/@n");
      $descendants = $xmlFile->xpath("/decsvmx/decsws_response/tree/descendants/term_list[@lang='pt']/term");

      foreach($descendants as $descendant)
	      $descriptors[ (string) $descendant ] = array('tree_id'=>(string) $descendant['tree_id']);			

      $result['definition'] = (string) $definition[0];
      $result['term'] = (string) $term[0];
      $result['descriptors'] = $descriptors;

      return array('result'=>$result);

  }
  
  /**
   *  Recebe o xml do servidor do Decs por Curl ou simplexml_load
   * 
   *  $param string $queryUrl
   *  @TODO This function needs to be more compatible and also need a better error treatment.
   */
  public function getDescriptorsFromDecs( $queryUrl ){
    // use the curl as default
    if ( function_exists('curl_version') ){

        $ch = curl_init();
        $timeout = 5; // set to zero for no timeout
        curl_setopt ( $ch, CURLOPT_URL, $queryUrl);
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        $file_contents = curl_exec($ch);
        curl_close($ch);

        $xmlFile = new SimpleXMLElement($file_contents);
    // if dont have the curl use the simplexml_load_file to load the decs xml
    } elseif ( function_exists('simplexml_load_file') == "Enabled" ){

      $xmlFile = simplexml_load_file( $queryUrl );

    } else {
      Throw new Exception('This module need simplexml or curl to get the descriptors from bvs.salude.');
    }

    return $xmlFile;

  }
  
}
