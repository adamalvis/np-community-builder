<?php
	include_once 'global.php';
	if (!isset($_SESSION['email'])) {
		//	Session variable not set - redirect to login
		header("Location: " . $login_url);
	} else {
		//////////////////////
		// Manage Page for Staff
		// - Forms POST to this page
		//////////////////////

		// Error display
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		$return_message = "";

		// Only process if email was passed
		if(isset($_POST['type'])) {
			$manage_type = $_POST['type'];

////////////////////////////////////////////////////				
// MANAGE VOLUNTEER
// - TEST: type=volunteer-period&vol-id=1&signintime=03/15/2018 1:30 PM&signouttime=03/15/2018 3:30 PM&location=7&task=1&organization=HCA

			if ($manage_type == "volunteer") {
				echo "VOLUNTEER!";
				// Make sure we have required values for a VOLUNTEER update
				if(!isset($_POST['fn'])) {
					$return_message = "Must provide first name for volunteer.";
				} elseif(!isset($_POST['ln'])) {
					$return_message = "Must provide last name for volunteer.";
				} elseif(!isset($_POST['email'])) {
					$return_message = "Must provide email for volunteer.";
				} else				
					// Sanitize Strings
					$vol_id = filter_var ( $_POST['vol-id'], FILTER_SANITIZE_STRING);
					$vol_fn = filter_var ( $_POST['fn'], FILTER_SANITIZE_STRING);
					$vol_ln = filter_var ( $_POST['ln'], FILTER_SANITIZE_STRING);
					$vol_email = filter_var ( $_POST['email'], FILTER_SANITIZE_STRING);
					
					// Update String Query
					$update_string = "UPDATE volunteer
										SET email = '".$vol_email."'
											,first_name = '".$vol_fn."'
											,last_name = '".$vol_ln."'";
					
					// Optional fields
					 if(isset($_POST['phone'])) {
						 $vol_phone = filter_var ( $_POST['phone'], FILTER_SANITIZE_STRING);
						 $update_string = $update_string . ",emergency_contact_phone = '".$vol_phone."'";
	   				}
					if(isset($_POST['skills'])) {
						$vol_skills = filter_var ( $_POST['skills'], FILTER_SANITIZE_STRING);
						 $update_string = $update_string . ",skills = '".$vol_skills."'";
	   				}
					if(isset($_POST['interests'])) {
						$vol_interests = filter_var ( $_POST['interests'], FILTER_SANITIZE_STRING);
						 $update_string = $update_string . ",interests = '".$vol_interests."'";
	   				}
					if(isset($_POST['availability'])) {
						$vol_availability = filter_var ( $_POST['availability'], FILTER_SANITIZE_STRING);
						 $update_string = $update_string . ",availability = '".$vol_availability."'";
	   				}
					if(isset($_POST['email_dist'])) {
						$vol_email_dist = filter_var ( $_POST['email_dist'], FILTER_SANITIZE_STRING);
						 $update_string = $update_string . ",include_email_dist = '".$vol_email_dist."'";
	   				} 
					$update_string = $update_string . "WHERE id = ".$vol_id;
					if ($db->executeStatement($update_string,[])) {
						// Success
						$return_message = "Successfully Updated Volunteer!";
					} else {
						// Failure
						$return_message = "Sorry! Was unable to update the volunteer.";
					}

////////////////////////////////////////////////////				
// MANAGE VOLUNTEER PERIOD
// - TEST: type=volunteer-period&vol-id=1&signintime=03/15/2018 1:30 PM&signouttime=03/15/2018 3:30 PM&location=7&task=1&organization=HCA

			} elseif ($manage_type == "volunteer-period") {

				// Make sure we have required values for a VOLUNTEER PERIOD UPDATE
				if(!isset($_POST['vol-id'])) {
					$return_message = "Volunteer period id was not provided.";
				} elseif(!isset($_POST['signintime'])) {
					$return_message = "Sign in time was not provided.";
				} elseif(!isset($_POST['signouttime'])) {
					$return_message = "Sign out time was not provided.";
				} elseif(!isset($_POST['location'])) {
					$return_message = "Location id was not provided.";
				} elseif(!isset($_POST['task'])) {
					$return_message = "Task id was not provided.";
				} elseif(!isset($_POST['organization'])) {
					$return_message = "Organization was not provided.";
				} else {
					// Sanitize Strings
					$vol_period_id = filter_var ( $_POST['vol-id'], FILTER_SANITIZE_STRING);
					$signin_datetime = filter_var ( $_POST['signintime'], FILTER_SANITIZE_STRING);
					$signout_datetime = filter_var ( $_POST['signouttime'], FILTER_SANITIZE_STRING);
					$location_id = filter_var ( $_POST['location'], FILTER_SANITIZE_STRING);
					$task_id = filter_var ( $_POST['task'], FILTER_SANITIZE_STRING);
					$organization = filter_var ( $_POST['organization'], FILTER_SANITIZE_STRING);					
					
					// Format Dates
					$signin_date = date_parse_from_format ( $ui_date_format , $signin_datetime );
					$sign_in_time = $signin_date["year"] . "-" . $signin_date["month"] . "-" . $signin_date["day"] 
										. " " . $signin_date["hour"] . ":" . $signin_date["minute"] .":00";
					$signout_date = date_parse_from_format ( $ui_date_format , $signout_datetime );
					$sign_out_time = $signout_date["year"] . "-" . $signout_date["month"] . "-" . $signout_date["day"] 
										. " " . $signout_date["hour"] . ":" . $signout_date["minute"] .":00";

					// Update value in volunteer period
					$hours = calculateHours($signin_date, $signout_date);
					if ($hours < 0) {						
						?>For the date <?= $sign_out_time ?>, it looks like you didn't sign out after your <?= $sign_in_time ?> sign in.  We need  you to sign out of that day after the sign in time. Thanks!. <span class='hidden'>ERROR: Signing out on a day where they did not sign in.</span></p><?php 						
					} else {
						// Update String Query
						$update_string = "UPDATE volunteer_period
											SET check_out_time = '".$sign_out_time."'
												,hours = '".$hours."'
												,check_in_time = '".$sign_in_time."'
												,affiliation = '".$organization."'
												,job_type_id = '".$task_id."'
												,location_id = '".$location_id."'
										  	WHERE id = ".$vol_period_id;
						if ($db->executeStatement($update_string,[])) {
							// Success
							$return_message = "Successfully Updated Volunteer Period!";
						} else {
							// Failure
							$return_message = "Sorry! Was unable to update the volunteer period.";
						}
					}
				}
			} else {
				$return_message = "Sorry! You requested an unsupported action/type (type requested was <?=$manage_type?>).";
			}
		} else {
			$return_message = "Sorry! There was an issue with the action/type you attempted to take.";
		}
		if (strrpos($referring_url, "?")) {
			if (strrpos($referring_url, "&message=")) {
				// Message already in alert, remove and replace
				$referring_url = preg_replace('/message=.*/', "message=".$return_message, $referring_url);
				header("Location: " . $referring_url);
			} else {
				header("Location: " . $referring_url . "&message=".$return_message);	
			}
		} else {
			header("Location: " . $referring_url . "?message=".$return_message);			
		}
	}
?>