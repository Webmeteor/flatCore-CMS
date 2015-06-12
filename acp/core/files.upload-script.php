<?php
session_start();
error_reporting(E_ALL);

require("../../config.php");
define("CONTENT_DB", "../../$fc_db_content");

if($_SESSION['user_class'] != "administrator"){
	header("location:../index.php");
	die("PERMISSION DENIED!");
}

$max_w = (int) $_REQUEST['w']; // max image width
$max_h = (int) $_REQUEST['h']; // max image height
$max_fz = (int) $_REQUEST['fz']; // max filesize

if($_REQUEST['d'] == 'images') {
	$destination = '../../'.$img_path;
} else {
	$destination = '../../'.$files_path;
}


if($_REQUEST['upload_type'] == 'images') {
	if(array_key_exists('file',$_FILES) && $_FILES['file']['error'] == 0 ){
		
		$tmp_name = $_FILES['file']['tmp_name'];
		$org_name = $_FILES['file']['name'];
		$suffix = strtolower(substr(strrchr($org_name,'.'),1));
		$prefix = basename($org_name,".$suffix");
		$img_name = clean_filename($prefix,$suffix);
		$target = "$destination/$img_name";
		fc_write_media_data_name("$destination/$img_name");
		$target = resize_image($tmp_name,$target, $max_w,$max_h,90);

	}
}



if($_REQUEST['upload_type'] == 'files') {
	if(array_key_exists('file',$_FILES) && $_FILES['file']['error'] == 0 ){
		$tmp_name = $_FILES["file"]["tmp_name"];   
	  $org_name = $_FILES["file"]["name"];
	  $suffix = strtolower(substr(strrchr($org_name,'.'),1));
	  $prefix = basename($org_name,".$suffix");
	  $files_name = clean_filename($prefix,$suffix);
	  $target = "$destination/$files_name";
		fc_write_media_data_name("$destination/$files_name");
		if(@move_uploaded_file($tmp_name, $target)) {
			print ('Upload complete');
		}
	}

}


function resize_image($img, $name, $thumbnail_width, $thumbnail_height, $quality){

	$arr_image_details	= GetImageSize("$img");
	$original_width		= $arr_image_details[0];
	$original_height	= $arr_image_details[1];
	
	$a = $thumbnail_width / $thumbnail_height;
  $b = $original_width / $original_height;


	if($a<$b) {
     $new_width = $thumbnail_width;
     $new_height	= intval($original_height*$new_width/$original_width);
  } else {
     $new_height = $thumbnail_height;
     $new_width	= intval($original_width*$new_height/$original_height);
  }
  
  if(($original_width <= $thumbnail_width) AND ($original_height <= $thumbnail_height)) {
	  $new_width = $original_width;
	  $new_height = $original_height;
  }
  
  

	if($arr_image_details[2]==1) { $imgt = "imagegif"; $imgcreatefrom = "imagecreatefromgif";  }
	if($arr_image_details[2]==2) { $imgt = "imagejpeg"; $imgcreatefrom = "imagecreatefromjpeg";  }
	if($arr_image_details[2]==3) { $imgt = "imagepng"; $imgcreatefrom = "imagecreatefrompng";  }

	if($imgt == 'imagejpeg') { 
		$old_image	= $imgcreatefrom("$img");
		$new_image	= imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($new_image,$old_image,0,0,0,0,$new_width,$new_height,$original_width,$original_height);
		imagejpeg($new_image,"$name",$quality);
		imagedestroy($new_image);
	}

	if($imgt == 'imagepng') { 
		$old_image	= $imgcreatefrom("$img");
		$new_image	= imagecreatetruecolor($new_width, $new_height);
		imagealphablending($new_image, false);
		imagesavealpha($new_image, true);
		$transparency = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
		imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparency);
		imagecopyresampled($new_image,$old_image,0,0,0,0,$new_width,$new_height,$original_width,$original_height);
		imagepng($new_image,"$name",0);
	}	

	if($imgt == 'imagegif') {	
		return $name;
	}

}


function increment_prefix($cnt,$target) {

	$nbr = $cnt+1;
	$path = pathinfo($target);
	$filepath = $path['dirname'];
	$filename = $path['filename'];
	$extension = $path['extension'];

	if(substr("$filename", -2,1) == '_' AND is_numeric(substr("$filename", -1))) {
		$filename_without_nbr = substr("$filename", 0,-2);
		$new_filename = $filename_without_nbr.'_'.$nbr;
		$new_target = "$filepath/$new_filename.$extension";

		if(is_file("$new_target")) {
			$nbr = increment_prefix($nbr,$new_target);
		}

	} else {
		$new_target = "$filepath/$filename"."_$nbr.".$extension;
		if(is_file("$new_target")) {
			$nbr = increment_prefix($nbr,$new_target);
		}
	}
	return $nbr;
}


function clean_filename($prefix,$suffix) {

	global $destination;
	$prefix = strtolower($prefix);

	$a = array('ä','ö','ü','ß',' - ',' + ','_',' / ','/'); 
	$b = array('ae','oe','ue','ss','-','-','_','-','-');
	$prefix = str_replace($a, $b, $prefix);
	$prefix = preg_replace('/\s/s', '_', $prefix);  // replace blanks -> '_'
	$prefix = preg_replace('/[^a-z0-9_-]/isU', '', $prefix); // only a-z 0-9
	$prefix = trim($prefix);

	$target = "$destination/$prefix.$suffix";

	if(is_file($target)) {
		$prefix = $prefix . '_' . increment_prefix('0',"$target");	    
	}


	$filename = $prefix . '.' . $suffix;

	return $filename; 
}



function fc_write_media_data_name($filename) {
	$dbh = new PDO("sqlite:".CONTENT_DB);
	$sql = "INSERT INTO fc_media ( media_id, media_file ) VALUES ( NULL, :media_file ) ";
	$sth = $dbh->prepare($sql);
	$sth->bindParam(':media_file', $filename, PDO::PARAM_STR);
	$cnt_changes = $sth->execute();
	$dbh = null;
}

?>