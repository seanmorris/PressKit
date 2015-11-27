<?php
namespace SeanMorris\PressKit\Form;
class Form extends \SeanMorris\Form\Form
{
	// 'SeanMorris\Access\Role\Administrator'

	// @todo Abstract the _access method from controller
	// put it in Role?
	// Use it in here to determine form/field access

	public function __construct($skeleton)
	{
		if(isset($skeleton['_access']))
		{
			$roleNeeded = $skeleton['_access'];
			
		}
	}
}