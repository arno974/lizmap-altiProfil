<?php
/**
* altiProfil administration
* @package   lizmap
* @subpackage admin
* @author    3liz
* @copyright 2020 3liz
* @link      http://3liz.com
* @license GPL 3
*/

class configCtrl extends jController {

    // Configure access via jacl2 rights management
    public $pluginParams = array(
        '*' => array( 'jacl2.right'=>'lizmap.admin.access'),
        'modify' => array( 'jacl2.right'=>'lizmap.admin.services.update'),
        'edit' => array( 'jacl2.right'=>'lizmap.admin.services.update'),
        'save' => array( 'jacl2.right'=>'lizmap.admin.services.update'),
        'validate' => array( 'jacl2.right'=>'lizmap.admin.services.update')
    );

    private $ini = null;

    function __construct( $request ) {
        parent::__construct( $request );
        $monfichier = jApp::configPath('altiProfil.ini.php');
        $this->ini = new jIniFileModifier ($monfichier);
    }

    /**
     * Display a summary of the information taken from the ~ configuration file.
     *
     * @return jResponseHtml Administration backend for the repositories.
     */
    function index() {
        $rep = $this->getResponse('html');

        // Create the form
        $form = jForms::create('altiProfilAdmin~config');

        // Set form data values
        foreach ( $form->getControls() as $ctrl ) {
            if ( $ctrl->type != 'submit' ){
                $val = $this->ini->getValue( $ctrl->ref, 'altiProfil' );
                $form->setData( $ctrl->ref, $val );
            }
        }

        $tpl = new jTpl();
        $tpl->assign( 'form', $form );
        $rep->body->assign('MAIN', $tpl->fetch('config_view'));
        $rep->body->assign('selectedMenuItem','altiProfilAdmin_config');

        return $rep;
    }



    /**
     * Modification of the configuration.
     * @return jResponseRedirect Redirect to the form display action.
     */
    public function modify(){

        // Create the form
        $form = jForms::create('altiProfilAdmin~config');

        // Set form data values
        foreach ( $form->getControls() as $ctrl ) {
            if ( $ctrl->type != 'submit' ){
                $val = $this->ini->getValue( $ctrl->ref, 'altiProfil' );
                $form->setData( $ctrl->ref, $val );
            }
        }

        // redirect to the form display action
        $rep= $this->getResponse("redirect");
        $rep->action="altiProfilAdmin~config:edit";
        return $rep;
    }


    /**
     * Display the form to modify the config.
     * @return jResponseHtml Display the form.
     */
    public function edit(){
        $rep = $this->getResponse('html');

        // Get the form
        $form = jForms::get('altiProfilAdmin~config');

        if ( !$form ) {
            // redirect to default page
            jMessage::add('error in edit');
            $rep =  $this->getResponse('redirect');
            $rep->action ='altiProfilAdmin~config:index';
            return $rep;
        }
        // Display form
        $tpl = new jTpl();
        $tpl->assign('form', $form);
        $rep->body->assign('MAIN', $tpl->fetch('altiProfilAdmin~config_edit'));
        $rep->body->assign('selectedMenuItem','altiProfilAdmin_config');
        return $rep;
  }


  /**
  * Save the data for the config.
  * @return jResponseRedirect Redirect to the index.
  */
  function save(){
    $form = jForms::get('altiProfilAdmin~config');

    // token
    $token = $this->param('__JFORMS_TOKEN__');
    if( !$token ){
      // redirection vers la page d'erreur
      $rep= $this->getResponse("redirect");
      $rep->action="altiProfilAdmin~config:index";
      return $rep;
    }

    // If the form is not defined, redirection
    if( !$form ){
      $rep= $this->getResponse("redirect");
      $rep->action="altiProfilAdmin~config:index";
      return $rep;
    }

    // Set the other form data from the request data
    $form->initFromRequest();

    // Check the form
    if ( !$form->check() ) {
      // Errors : redirection to the display action
      $rep = $this->getResponse('redirect');
      $rep->action='altiProfilAdmin~config:edit';
      $rep->params['errors']= "1";
      return $rep;
    }

    // Save the data
    foreach ( $form->getControls() as $ctrl ) {
        if ( $ctrl->type != 'submit' ){
            $val = $form->getData( $ctrl->ref );
            $this->ini->setValue( $ctrl->ref, $val, 'altiProfil' );
        }
    }
    $this->ini->save();

    // Redirect to the validation page
    $rep= $this->getResponse("redirect");
    $rep->action="altiProfilAdmin~config:validate";

    return $rep;
  }


  /**
  * Validate the data for the config : destroy form and redirect.
  * @return jResponseRedirect Redirect to the index.
  */
  function validate(){

    // Destroy the form
    if($form = jForms::get('altiProfilAdmin~config')){
      jForms::destroy('altiProfilAdmin~config');
    }

    // Redirect to the index
    $rep= $this->getResponse("redirect");
    $rep->action="altiProfilAdmin~config:index";

    return $rep;
  }

}
