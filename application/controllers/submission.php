<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Submission extends MY_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->load->model('submission_model');
        
        $this->load->library('image_lib');
    }

    public function upload() {
        $this->secure();
        
        if ($this->input->post()){
            $sTitle = $this->input->post('title');
            // sanitize title:
            $sTitle = str_replace("/", "", $sTitle);
            $sTitle = htmlentities($sTitle);
            
            $iSubmissionId = $this->input->post('submission_id');
            // check if it is yours
            $oSubmission = $this->submission_model->get($iSubmissionId);
            if ($oSubmission->u_id != $this->member_id){
                lm("Tried to edit submission_id $iSubmissionId which is not his");
                exit();
            }
            
            $this->submission_model->edit($iSubmissionId, array(
                'p_name' => $sTitle,
                'p_active' => 'Y'
            ));
            
            redirect(site_url() . '/submission/view/' . $sTitle .'/' . $iSubmissionId);
        }
        
        $this->data['sUserhash'] = $this->member_model->makeHash($this->member_id);
        
        $this->load->view('include/header', $this->data);
        $this->load->view('submission/upload', $this->data);
		$this->load->view('include/footer', $this->data);
    }
    
    public function view($sTitle, $id){
        $this->data['oPhoto'] = $this->submission_model->get($id);
        
        $this->load->view('include/header', $this->data);
        $this->load->view('submission/view', $this->data);
		$this->load->view('include/footer', $this->data);
    }
    
    
    public function ajax_file_upload() {
        $this->load->model('quest_model');
        
        $this->secure();
        
        
        if (!empty($_FILES)) {
            $iSubmissionId = $this->submission_model->getSeqId();
            $tempFile = $_FILES['Filedata']['tmp_name'];
            $targetPath = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '/';
            
            $sTargetPath = $_SERVER['DOCUMENT_ROOT'] . '/media/storage/submissions/' . $iSubmissionId .'/';
            mkdir($sTargetPath);
            $sCalculatedFilename = md5(time()); //@TODO: dynamic extension
            $sFileLocation =  str_replace('//','/',$sTargetPath) . $sCalculatedFilename . '.jpg';
            move_uploaded_file($tempFile,$sFileLocation);
            //echo str_replace($_SERVER['DOCUMENT_ROOT'],'',$targetFile);
            
            $this->_resize_img($sFileLocation, THUMB_MAX_WIDTH, THUMB_MAX_HEIGHT, '_thumb');
            $this->_resize_img($sFileLocation, PREVIEW_MAX_WIDTH, PREVIEW_MAX_HEIGHT, '_preview');
            
            $oInfo = getimagesize($sFileLocation);
            if (! strstr($oInfo['mime'], 'image')){
                echo json_encode(array('success' => false, 'msg' => 'Not an image'));
                exit();
            }
            
            $oQuest = $this->quest_model->getCurrentQuest();
            
            $aData = array(
                'p_id'      => $iSubmissionId,
                'u_id'      => $this->member_id,
                'q_id'      => $oQuest->q_id,
                'p_date'    => date("Y-m-d H:i:s"),
                'p_image'   => $sCalculatedFilename
            );
            
            $this->submission_model->add($aData);
            $aOut = array(
                'success' => true, 
                'id' => $iSubmissionId, 
                'filename' => $sCalculatedFilename
            );
            echo json_encode($aOut);
            exit();
        }
    }
    
    protected function _resize_img($sFileLocation, $iMaxWidth, $iMaxHeight, $sSuffix){
        $aConfig = array();

        list($iImageWidth, $iImageHeight) = getimagesize($sFileLocation);

        // create a thumbnail
        $aConfig['image_library'] = 'GD2';
        $aConfig['source_image'] = $sFileLocation;
        $aConfig['quality'] = 100;
        $aConfig['height'] = $iMaxHeight;
        $aConfig['width'] = $iMaxWidth;
        $aConfig['create_thumb']  = TRUE;
        $aConfig['maintain_ratio']= TRUE;
        $aConfig['thumb_marker'] = $sSuffix;
        //$aConfig['master_dim'] = ($iImageWidth > $iImageHeight) ? 'height' : 'width';
        $this->image_lib->initialize($aConfig);

        if (!$this->image_lib->resize()) { 
            echo $this->image_lib->display_errors();
        }

        $this->image_lib->clear();
        unset($aConfig);
    }
    
    
    protected function _image_thumb_name($sFileLocation, $sSuffix)
    {
        if(!empty($sFileLocation)) {
            $exploded = explode('.', $sFileLocation);
            return $exploded['0'] . $sSuffix. '.' . $exploded['1'];
        }
    }
    
    
}


?>