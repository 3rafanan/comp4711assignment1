<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
*	Based on instructions found on the CodeIgniter website:
*	https://ellislab.com/codeigniter/user-guide/libraries/form_validation.html#theform
*/
class Create_ad extends Application {

	 /**
	 * Constructor, creates the Create_ad class object, and loads the methods found in the formfields_helper
	 *
	 */
	function __construct()
	{
		parent::__construct();
		$this->load->helper('formfields_helper');
		$this->load->model('Ads');
		$this->load->model('Users');
		$this->load->model('Categories');
		$this->load->model('Adimages');
	}

	/**
	 * Responsible for creating the form elements programatically by filling in templates
	 *
	 */
	public function index()
	{
		$newAd = $this->Ads->create();
		$this->present($newAd);
	}

	 /**
	 *	Get variables from the form, validate them, and submit them to the RDB
	 */
	public function submit()
	{
		// create empty entry in RDB
		$newAd = $this->Ads->create();

		// retrieve form parameters, and inject them into the ad object
		$newAd->categoryID  = $this->input->post('ad_category');
		$newAd->title = $this->input->post('ad_title');
		$newAd->price = $this->input->post('ad_price');
		$newAd->description = $this->input->post('ad_description');
		$newAd->flags = 0;			//0 complaints against this post
		$newAd->uploaded = date('Y-m-d'); //2015-03-04 yyyy-mm-dd
		$newAd->userID = $this->users->get_current_user_id();

		// validate user input
		if ($newAd->userID == null)
			$this->errors[] = 'You must log in to submit a post';
		if (empty($newAd->title))
			$this->errors[] = 'You must enter a title for your advertisement';
		if ($newAd->price < 0)
			$this->errors[] = 'You cannot enter a negative amount of money';

		// redisplay if any errors
		if (count($this->errors) > 0)
		{
			$this->present($newAd);
			return; // make sure we don't try to save anything
		}

		// insert or update the ad
		if (empty($newAd->ID))
		{
			$this->Ads->add($newAd);
			$newAd->ID = $this->Ads->highest();
		}
		else
		{
			$this->Ads->update($newAd);
		}

		// make a directory for the uploaded file(s)
		mkdir('./uploads/posts/'.$this->users->get_current_user_id());

		// load & configure the upload library
		$this->load->library('upload');

		$config['upload_path']   =
			'./uploads/users/'.$this->users->get_current_user_id();
		$config['allowed_types'] = 'gif|jpg|png';
		$config['max_size']      = 100;
		$this->upload->initialize($config);

		// do the uploading
		$this->upload->do_multi_upload('imagefile');

		// insert uploaded images into the database

		// associate the uploaded images with the ad, or if no images were
		// uploaded, associate the ad with the default image.
		$adimagerow = $this->Adimages->create();
		$adimagerow->adID = $this->Ads->highest();
		$adimagerow->imageID = 7;
		$this->Adimages->add($adimagerow);

		redirect('/');
	 }

	/**
	 *	Re-renders the form to the screen, including any error messages
	 *
	 */
	public function present($record)
	{
		// inject error messages
		$errorMessage = '';
		if (count($this->errors) > 0)
		{
			foreach ($this->errors as $errors)
			{
				$errorMessage .= $errors . BR;
			}
		}
		$this->data['message'] = $errorMessage;

		// inject template parameters
		$this->data['navbar_activelink'] = base_url('/Create_ad');
		$this->data['page_title'] = 'Starter Template for Bootstrap'; //Change to whatever the ad is later
		$this->data['page_body'] = 'create_ad'; //the view that is to be rendered

		// create combo box options
		$categories = $this->Categories->all();
		$combobox_entries = array();
		foreach ($categories as $key => $value) {
			$combobox_entries[$categories[$key]->ID] = $categories[$key]->name;
		}

		// inject form parameters
		$this->data['ad_images']      = makeUploadImageField('ad images', 'imagefile', true);
		$this->data['ad_category']    = MakeComboField('category', 'ad_category', $record->categoryID, $combobox_entries);
		$this->data['ad_title']       = MakeTextField('title', 'ad_title', $record->title);
		$this->data['ad_price']       = MakeTextField('price', 'ad_price', $record->price);
		$this->data['ad_description'] = MakeTextArea('description', 'ad_description', $record->description);
		$this->data['ad_submit']      = makeSubmitButton('Process Ad', "Submit", 'btn-success');
		$this->data['ad_cancel']      = makeCancelButton('Cancel');

		$this->render();
	}
}

/* End of file Create_ad.php */
/* Location: ./application/controllers/Create_ad.php */
