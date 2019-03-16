<?php
 
/* Errors */
define('GD2_ERROR_NOTICE', 0);
define('GD2_ERROR_ERROR', 1);
 
class Gd2{
    
    
var $useGdFilters;
 
var $imgInfos = array(
    'type' => '',
    'width' => 0,
    'height' => 0
);
 
var $bgColor = array(
    'red' => 255,
    'green' => 255,
    'blue' => 255
);
 
var $_gdIsLoaded;
var $_imgHandler;
var $_supportedTypes = array();
var $_errors = array(
    'notices' => array(),
    'errors' => array()
);
 
 
/*
* PHP 5 Constructor
*/
function __construct($img = '', $red = 0, $green = 0, $blue = 0){

    if(!$this->_gdIsLoaded = extension_loaded('gd'))
        return $this->_setError('GD library is not installed in your system');
    elseif(!function_exists('imagecreatetruecolor'))
        return $this->_setError('This class require the version 2 of GD library');
  
    $types = imageTypes();

    if ($types & IMG_PNG){
        $this->_supportedTypes['png']['read'] = true;
        $this->_supportedTypes['png']['write'] = true;
    }
 
    if ($types & IMG_GIF || function_exists('imagegif')){
        $this->_supportedTypes['gif']['read'] = true;
        $this->_supportedTypes['gif']['write'] = true;
    }
    elseif (function_exists('imagecreatefromgif')){
        $this->_supportedTypes['gif']['read'] = true;
        $this->_supportedTypes['gif']['write'] = false;
    }

    if ($types & IMG_JPG){
        $this->_supportedTypes['jpeg']['read'] = true;
        $this->_supportedTypes['jpeg']['write'] = true;
    }

    $this->bgColor['red'] = min(255,max(0,$red));
    $this->bgColor['green'] = min(255,max(0,$green));
    $this->bgColor['blue'] = min(255,max(0,$blue));

    $this->useGdFilters = function_exists('imagefilter');

    if(is_file($img))
        $this->createFromFile($img);
		

}
 
/*
* Destructor
*/
function __destruct(){
    $this->destroy();
}
 
/************************************ Publics Methods ************************************/
 
/*
* Image resource destroyer
*
* @return bool
* @access public
*/
function destroy(){
    if(is_resource($this->_imgHandler)){
        imagedestroy($this->_imgHandler);
        return true;
    }
    return false;
}
 
/*
* Image resource destroyer
*
* @param string $file the file path
*
* @return bool
* @access public
*/
function createFromFile($file){
    if(!$this->_gdIsLoaded)
        return false;
    elseif(!is_file($file))
        return $this->_setError('Hata! Gorsel Bulunamadi.');
 
    $this->_init();

    $ext = $this->getExt($file);

    if(!$this->_isSupported($ext,'read'))
        return $this->_setError('Image "'.$ext.'" are not supported');

    //var_dump($ext);
    @ini_set("gd.jpeg_ignore_warning",1);

    if ($ext === 'jpeg')
        $this->_imgHandler = ImageCreateFromJPEG($file);
    elseif($ext === 'png')
        $this->_imgHandler = @imagecreatefrompng($file);
    elseif($ext === 'gif')
        $this->_imgHandler = @imagecreatefromgif($file);

    
    

    if(!$this->_imgHandler)
        return $this->_setError('Fail to create the image resource');
 
    $this->imgInfos['type'] = $ext;
    $this->imgInfos['width'] = imagesx($this->_imgHandler);
    $this->imgInfos['height'] = imagesy($this->_imgHandler);

    return true;
}


function Watermark($file,$align="TL",$margin=5){
    if($this->_isReady()){
        
        $ext = $this->getExt($file);

        if ($ext === 'jpeg')
            $w_Handler = ImageCreateFromJPEG($file);
        elseif($ext === 'png')
            $w_Handler = @imagecreatefrompng($file);
        elseif($ext === 'gif')
            $w_Handler = @imagecreatefromgif($file);

        $w_x = imagesx($w_Handler);
        $w_y = imagesy($w_Handler);
        
        if($align=="TL"){
            $dpy = $margin;
            $dpx = $margin;
        }else if($align =="TC"){
            $dpy = $margin;
            $dpx = ($this->imgInfos['width']/2)-($w_x/2);
        }else if($align=="TR"){
            $dpy = $margin;
            $dpx = $this->imgInfos['width']-($w_x+$margin);
        }else if($align=="CL"){
            $dpy = ($this->imgInfos['height']/2)-($w_y/$margin);
            $dpx = $margin;
        }else if($align=="CC"){
            $dpy = ($this->imgInfos['height']/2)-($w_y/2);
            $dpx = ($this->imgInfos['width']/2)-($w_x/2);
        }else if($align=="CR"){
            $dpy = ($this->imgInfos['height']/2)-($w_y/2);
            $dpx = $this->imgInfos['width']-($w_x+$margin);
        }else if($align=="BL"){
            $dpy = $this->imgInfos['height']-($w_y+$margin);
            $dpx = $margin;
        }else if($align=="BC"){
            $dpy = $this->imgInfos['height']-($w_y+$margin);
            $dpx = ($this->imgInfos['width']/2)-($w_x/2);
        }else if($align=="BR"){
            $dpy = $this->imgInfos['height']-($w_y+$margin);
            $dpx = $this->imgInfos['width']-($w_x+$margin);
        }else{
            $dpy = $margin;
            $dpx = $margin;
        }
        
        
        imagecopy($this->_imgHandler,$w_Handler, $dpx, $dpy, 0, 0, $w_x, $w_y);
        //return $this->resizeImage($new_width,$new_height,$x,$y,$size);
    }
    else return false;
}



 
/*
* Resize the image to the specified dimensions
*
* @param int $new_width the image width
* @param int $new_height the image height
* @param int $x vertical offset for crop resizing
* @param int $y horizontal offset for crop resizing
* @param int $size image dimensions for crop resizing
*
* @return bool
* @access public
*/
function resizeImage( $new_width, $new_height, $x = 0, $y = 0,$size = 0){
    if($this->_isReady()){
        $image_p = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($image_p, $this->_imgHandler, 0, 0,0,0, $new_width, $new_height, $this->imgInfos['width'], $this->imgInfos['height']);
        imagedestroy($this->_imgHandler);
        $this->_imgHandler = $image_p;
 
            if(!empty($size)){
                $image2 = imagecreatetruecolor($size,$size);
                imagecopyresampled($image2,$this->_imgHandler,$x,$y,0,0,$size,$size,$size,$size);
                imagedestroy($this->_imgHandler);
                $this->_imgHandler = $image2;
            }
        return true;
    }
    else return false;
}
 
/*
* Resize the image to the specified dimensions
*
* @param int $new_width the image width
* @param int $new_height the image height
* @param int $x vertical offset for crop resizing
* @param int $y horizontal offset for crop resizing
* @param int $size image dimensions for crop resizing
*
* @return bool
* @access public
*/
function oneSizeThumbnail($size){
    if($this->_isReady()){
        if($this->imgInfos['width'] > $this->imgInfos['height']){
            $size_percent = (int)($size / ($this->imgInfos['width'] / 100));
            $new_height = (int) ($size_percent * ($this->imgInfos['height']/100));
            $new_width = $size;
            $y = ((int)$size - (int)$new_height) /2 ;
            $x = 0;
        }else{
            $size_percent = (int)($size / ($this->imgInfos['height'] / 100));
            $new_width = (int) ($size_percent * ($this->imgInfos['width']/100));
            $new_height = $size;
            $x = ($size - $new_width) / 2;
            $y = 0;
        }
        return $this->resizeImage($new_width,$new_height,$x,$y,$size);
    }
    else return false;
}
 
/*
* Resize the image to the specified dimensions
*
* @param int $new_width the image width
* @param int $new_height the image height
* @param int $x vertical offset for crop resizing
* @param int $y horizontal offset for crop resizing
* @param int $size image dimensions for crop resizing
*
* @return bool
* @access public
*/
function maxSizeThumbnail($size){
    if($this->_isReady()){
        if($this->imgInfos['width'] > $this->imgInfos['height']){
            $size_percent = (int)($size / ($this->imgInfos['width'] / 100));
            $new_height = (int) ($size_percent * ($this->imgInfos['height']/100));
            $new_width = $size;
            $y = ($size - $new_height) / 2;
            $x = 0;
        }else{
            $size_percent = (int)($size / ($this->imgInfos['height'] / 100));
            $new_width = (int) ($size_percent * ($this->imgInfos['width']/100));
            $new_height = $size;
            $x = ($size - $new_width) / 2;
            $y = 0;
        }
 
        return $this->resizeImage($new_width,$new_height,$x,$y);
    }
    else return false;
}

/*MAKSİMUM GENİŞLİĞE GÖRE YÜKSEKLİĞİ AYARLIYOR*/
function WidthThumbnail($size){
    if($this->_isReady()){
            $size_percent = (int)($size / ($this->imgInfos['width'] / 100));
            $new_height = (int) ($size_percent * ($this->imgInfos['height']/100));
            $y = ($size - $new_height) / 2;
        return $this->resizeImage($size,$new_height,0,$y);
    }
    else return false;
}

function HeightThumbnail($size){
    if($this->_isReady()){
            $size_percent = (int)($size / ($this->imgInfos['height'] / 100));
            $new_width = (int) ($size_percent * ($this->imgInfos['width']/100));
            $h = ($size - $new_width) / 2;
        return $this->resizeImage($new_width,$size,0,$h);
    }
    else return false;
}

/*GENİŞLİK YÜKSEKLİĞE GÖRE, RESMİ CROPLUYOR*/
function ResizeWideCrop($width,$height){
    if($this->_isReady()){
        $this->WidthThumbnail($width);
        $fark = (imagesy($this->_imgHandler)-$height)/2;
        $this->cropImage($width,$height,0,$fark);
        return true;
    }
    else return false;
}


function BoxCrop(){
    if($this->_isReady()){
        
        if($this->imgInfos['width'] < $this->imgInfos['height'] ){
            $fark = $this->imgInfos['height']-$this->imgInfos['width'];
            $this->cropImage($this->imgInfos['width'],$this->imgInfos['width'],0,$fark/2);
        }else{
            $fark = $this->imgInfos['width']-$this->imgInfos['height'];
            $this->cropImage($this->imgInfos['height'],$this->imgInfos['height'],$fark/2,0);
        }
        return true;
    }
    else return false;
}


/*
* Crop the image to the specified dimensions
*
* @param int $width the croped image width
* @param int $height the croped image height
* @param int $x vertical offset for croping
* @param int $y horizontal offset for croping
*
* @return bool
* @access public
*/
function cropImage($width,$height,$x,$y){
    if($this->_isReady()){
        $image2 = imagecreatetruecolor($width, $height);
        imagecopymerge($image2,$this->_imgHandler,0,0,$x,$y,$width, $height,100);
        imagedestroy($this->_imgHandler);
        $this->_imgHandler = $image2;
 
        return true;
    }else return false;
}

/*
* Apply rotation to the image
*
* @param int $anle the rotation angle
* @param int $color the background color for some rotation
*
* @return bool
* @access public
*/
function imageRotate($angle,$color = 0){
    if($this->_isReady()){
        if(!is_numeric($angle))
            return $this->_setError('Invalid "angle" parameter');
        elseif(empty($angle) || $angle === 360)
            return true;
 
    if(empty($color))
        $color = imagecolorat($this->_imgHandler,0,0);
    elseif(!is_array($color))
        $color = $this->_hex2rgb($color);
 
    $this->_imgHandler = imagerotate($this->_imgHandler, $angle, imageColorAllocate($this->_imgHandler,$color['red'],$color['green'],$color['blue']));
    }else return false;
}
 
/*
* Reverse the image
*
* @return bool
* @access public
*/
function mirror($direction='h'){
    if($this->_isReady()){
        $tmpimage = imagecreatetruecolor($this->imgInfos['width'], $this->imgInfos['height']);
        if($direction=='h'){
            for ($x=0;$x<$this->imgInfos['width'];++$x)
                imagecopy($tmpimage,$this->_imgHandler, $x, 0, $this->imgInfos['width'] - $x - 1, 0, 1, $this->imgInfos['height']);

        }else if($direction=='v'){
            for ($y=0;$y<$this->imgInfos['height'];++$y)
                imagecopy($tmpimage,$this->_imgHandler, 0, $y, 0, $this->imgInfos['height'] - $y - 1, $this->imgInfos['width'], 1);
            
        }
        imagedestroy($this->_imgHandler);
        $this->_imgHandler = $tmpimage;
 
    return true;
    }
    return false;
}
 
/*
* Apply Negate filter to the image
*
* @return bool
* @access public
*/
function effectNegate(){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler, IMG_FILTER_NEGATE);
        else
        return false;
    }else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Colorize filter to the image
*
* @param string | array $color the color of the filter
*
* @return bool
* @access public
*/
function effectColorize($color){
    if($this->useGdFilters){
        if($this->_isReady()){
            if(!is_array($color))
                $color = $this->_hex2rgb($color);
 
            return imagefilter($this->_imgHandler,IMG_FILTER_COLORIZE,$color['red'],$color['green'],$color['blue']);
        }else return false;
    }else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Grayscale filter to the image
*
* @return bool
* @access public
*/
function effectGrayscale(){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler, IMG_FILTER_GRAYSCALE);
        else
            return false;
    }else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Edge Detect filter to the image
*
* @return bool
* @access public
*/
function effectEdgeDetect(){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler, IMG_FILTER_EDGEDETECT);
        else
            return false;
    }else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Selective Blur filter to the image
*
* @return bool
* @access public
*/
function effectSelectiveBlur(){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler, IMG_FILTER_SELECTIVE_BLUR);
        else
            return false;
    }
    else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Contrast filter to the image
*
* @param int $val the effect level
*
* @return bool
* @access public
*/
function effectContrast($val){
 
    if($this->useGdFilters){
        if($this->_isReady()){
            return imagefilter($this->_imgHandler, IMG_FILTER_CONTRAST,$val);
        }
        else return false;
    }
    else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Brightness filter to the image
*
* @param int $val the effect level
*
* @return bool
* @access public
*/
function effectBrightness($val){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler, IMG_FILTER_BRIGHTNESS,$val);
        else
            return false;
    }
    else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Gausian Blur filter to the image
*
* @return bool
* @access public
*/
function effectGaussianBlur(){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler, IMG_FILTER_GAUSSIAN_BLUR);
        else
            return false;
    }
    else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Smooth filter to the image
*
* @param int $val the effect level
*
* @return bool
* @access public
*/
function effectSmooth($val){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler,IMG_FILTER_SMOOTH,$val);
        else
            return false;
    }
    else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Emboss filter to the image
*
* @return bool
* @access public
*/
function effectEmboss(){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler,IMG_FILTER_EMBOSS);
        else
            return false;
    }
    else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply Mean Removal filter to the image
*
* @return bool
* @access public
*/
function effectMeanRemoval(){
    if($this->useGdFilters){
        if($this->_isReady())
            return imagefilter($this->_imgHandler,IMG_FILTER_MEAN_REMOVAL);
        else
            return false;
    }
    else return $this->_setError('Image filters is not available on PHP4');
}
 
/*
* Apply negate filter to the image
*
* @return bool
* @access public
*/
function setTransparent($color){
    if($this->_isReady()){
        if(!is_array($color))
            $color = $this->_hex2rgb($color);
 
        if(!imageistruecolor($this->_imgHandler))
            $this->toTrueColor();
 
        imagecolortransparent($this->_imgHandler,imagecolorallocate($this->_imgHandler,$color['red'],$color['green'],$color['blue']));
 
    }
    else return false;
}
 
/*
* Convert an image with palette color to true color image
*
* @return bool
* @access public
*/
function toTrueColor(){
    if($this->_isReady()){
        if(!imageistruecolor($this->_imgHandler)){
            $img2 = imagecreatetruecolor($this->imgInfos['width'], $this->imgInfos['height']);
 
            imagecopymerge($img2,$this->_imgHandler,0,0,0,0,$this->imgInfos['width'],$this->imgInfos['height'],$this->imgInfos['width'],$this->imgInfos['height'],100);
 
            imagedestroy($this->_imgHandler);
            $this->_imgHandler = $img2;
        }
        return true;
    }
    return false;
}
 
/*
* set the background color for the created images
*
* @param string|array $color the color in hex or rgb (all "rgb" or "r" or "g" or "b") format
*
* @return bool
* @access public
*/
function setBgColor($color){
    if(is_string($color)){
        $this->bgColor = $this->_hex2rgb($color);
        return true;
    }elseif(is_array($color)){
        if($this->_isRgbColor($color)){
            $this->bgColor = $color;
            return true;
        }elseif(isset($color['red'])){
            $this->bgColor['red'] = $color['red'];
            return true;
        }elseif(isset($color['green'])){
            $this->bgColor['green'] = $color['green'];
            return true;
        }elseif(isset($color['blue'])){
            $this->bgColor['blue'] = $color['blue'];
            return true;
        }
        else return false;
    }
    else return false;
}
 
/*
* display the image
*
* @param string $type the output type
*
* @return bool
* @access public
*/
function display($type = ''){
    if($this->_isReady()){
        if($type === '') // i don't use empty because i don't remember if anyone of the IMAGETYPE_* constante have 0 for value
            $type = $this->imgInfos['type'];
        elseif(is_numeric($type))
            $type = $this->type2Ext($type);
        else{
            $type = strtolower($type);
            if($type === 'jpg')
                $type = 'jpeg';
        }
 
        if(!$this->_isSupported($type,'write'))
            return $this->_setError('Image "'.$type.'" not supported for output');
 
        header('Content-type: image/'.$type);
 
        if ($type === 'jpeg')
            imagejpeg($this->_imgHandler);
        elseif($type === 'png')
            imagepng($this->_imgHandler);
        elseif($type === 'gif')
            imagegif($this->_imgHandler);
 
    }elseif($this->isError())
        $this->_getErrorImage();
    else
    $this->_getErrorImage('No image handler found');
}
 
/*
* save the image
*
* @param string $filePath the save path
* @param int $quality output quality for jpeg
*
* @return bool
* @access public
*/
function save( $filePath, $quality = 90){
    if($this->_isReady()){
        if(empty($filePath))
            return $this->_setError('Can not save image file in a empty file path');
 
        $type = $this->getExt($filePath);
 
        if(!$this->_isSupported($type,'write'))
            return $this->_setError('Image "'.$type.'" not supported for output');
 
        if ($type === 'jpeg'){
            if(!is_numeric($quality))
                $quality = 100;
            else
                $quality = min(100,max(0,$quality));
 
            imagejpeg($this->_imgHandler,$filePath,$quality);
        }elseif ($type === 'png')
            imagepng($this->_imgHandler,$filePath);
        elseif ($type === 'gif')
            imagegif($this->_imgHandler, $filePath);
    }
}
 
/*
* convert an imagetype to the image extension string
*
* @param string $type the type of file you want get
*
* @return str on success or bool on fail
* @access public
*/
function type2Ext($type){
    if($type === IMAGETYPE_GIF)
        return 'gif';
    elseif($type === IMAGETYPE_PNG)
        return 'png';
    elseif($type === IMAGETYPE_JPEG)
        return 'jpeg';
    else
        return false;
}
 
/*
* Public isSupported method
*
* @param string $type the type of file you want check
* @param string|array $mode the mode you want check
*
* @return bool
* @access public
*/
function isSupported($type, $mode = 'read') {
    return $this->_isSupported(strtolower($type),(is_array($mode) ? $mode : strtolower($mode)));
}
 
/*
* return the file extension
*
* @param string $fileName the file name or path
*
* @return str
* @access public
*/
function getExt($fileName){
    if(empty($fileName))
        return '';
    elseif(false === ($pos = strrpos($fileName,'.')))
        return '';
 
    $ext = strtolower(substr($fileName,++$pos));
 
    if($ext === 'jpg')
        $ext = 'jpeg';
 
    return $ext;
}
 
/*
* Check if the class have an error
*
* @return bool
* @access public
*/
function isError() {
    return !empty($this->_errors['errors']);
}
 
/*
* Return the consigned errors
*
* @param bool $toStr returned the value on array or on string
* @param bool $html if return string the separator must be a normal line return with space or a html line break
*
*
* @return array | string
* @access public
*/
function getErrors( $toStr = false, $html = false){
    if(empty($this->_errors['errors']))
        return '';
 
    if(!$toStr)
        return $this->_errors['errors'];
    elseif(count($this->_errors['errors']) === 1)
        return $this->_errors['errors'][0];
    else{
        $ret = '';
 
        foreach($this->_errors['errors'] as $error)
            $ret .= $error.($html ? '<br />' : " \n");
 
        return $ret;
    }
}
 
/*
* Return the last consigned error
*
* @return str
* @access public
*/
function getLastError(){
    if(empty($this->_errors['errors']))
        return false;
 
    return $this->_errors['errors'][(count($this->_errors)-1)];
}
 
/************************************ Privates Methodss ************************************/
 
/*
* Initialise the class attributs
*
* @return void
* @access private
*/
function _init(){
    $this->destroy();
    $this->imgInfos = array();
}
 
/*
* Check if the class can work
*
* @return bool
* @access private
*/
function _isReady() {
    return ($this->_gdIsLoaded && !$this->isError() && is_resource($this->_imgHandler));
}
 
/*
* Check if an extension is supported for reading and/or writting
*
* @param string $type the type of file you want check
* @param string|array $mode the mode you want check
*
* @return bool
* @access private
*/
function _isSupported($type, $mode){
    if(!isset($this->_supportedTypes[$type]))
        return false;
    elseif(is_array($mode))
        return ($this->_supportedTypes[$type]['read'] && $this->_supportedTypes[$type]['write']);
    elseif(!isset($this->_supportedTypes[$type][$mode]))
        return false;
    else
        return $this->_supportedTypes[$type][$mode];
}
 
/*
* error handling
*
* @param string $msg the error message
* @param string|array $level the level of the error
*
* @return bool
* @access private
*/
function _setError( $msg, $level = GD2_ERROR_ERROR){
    $msg = trim($msg);
 
    if($level === GD2_ERROR_NOTICE){
        $this->_errors['notices'][] = $msg;
 
        return true;
    }else{
        $this->_errors['errors'][] = $msg;
 
        return false;
    }
}
 
/*
* create an image with the given error or with the consigned errors
*
* @param string $test the error message
*
* @return void
* @access private
*/
function _getErrorImage($text = ''){
    if(!$this->_gdIsLoaded){
        $this->_gdIsNotLoaded();
        return false;
    }elseif(empty($text))
        $text = $this->getErrors(true);
 
    $errors = explode("\n",$text);
    $nbe = count($errors);
 
    $width = ($this->imgInfos['width'] < 300) ? 300 : $this->imgInfos['width'];
    $height = (30*$nbe)+60;
    $finalheight = ($this->imgInfos['height'] < $height) ? $height : $this->imgInfos['height'];
 
    $eImg = imagecreate($width,$finalheight);
    $bg = imagecolorallocate($eImg, 255, 255, 255);
    $textcolor = imagecolorallocate($eImg, 255, 0, 0);
 
    for($i=0,$j=30;$i<$nbe;$i++,$j+=10)
        imagestring($eImg, 2, 30, $j, trim($errors[$i]), $textcolor);
 
    if($this->_isSupported('jpeg','write')){
        header("Content-type: image/jpeg");
        imagejpeg($eImg);
    }elseif($this->_isSupported('gif','write')){
        header("Content-type: image/gif");
        imagegif($eImg);
    }elseif($this->_isSupported('png','write')){
        header("Content-type: image/png");
        imagepng($eImg);
    }
}
 
function _gdIsNotLoaded(){
    header("Content-type: image/jpeg");
    echo base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD//gA+Q1JFQVRPUjogZ2QtanBlZyB2MS4'.
    'wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBkZWZhdWx0IHF1YWxpdHkK/9sAQwAIBgYHBgUIBwcHCQkIC'.
    'gwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy/9sAQwEJCQk'.
    'MCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyM'.
    'jIy/8AAEQgAZAEsAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALU'.
    'QAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXG'.
    'BkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5S'.
    'VlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29'.
    '/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwA'.
    'BAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDR'.
    'EVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrK'.
    'ztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A9'.
    '/ooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACii'.
    'igAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKAC'.
    'iiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigArL1+WSLSx5UjRmW5t4WZDtbY8'.
    'yIwBHIJViMjkdQQa1KZLFHPC8M0ayRSKVdHGVYHggg9RSkrpo0pSUJxk+jRyUMcV74iTSvP1aO3tVu'.
    'g0b30isWH2YqQ6PuZcSkjeSRuI4wAN/QLqa98OaXd3D7557SKSRsAbmZAScDjqaJNA0aa3ht5NIsHg'.
    'g3eVG1shWPccnaMYGTycVo1EINO7OnEYiFSCjG+nf5/nfX03e5jaX5/2nX4Yp23Je4hM7NKsZaCJsY'.
    'LZ27mJ2ggc4GKyLfUb+4bwuLe5gglvtKkci4d3UtiBuFLbpGA3dWyAWOTgg9KmladH9q8uwtV+15+0'.
    '7YVHnZznfx83U9fU0w6LpRtFtDplmbZVKrCYF2AFgxAXGMFlB+oB7UnCVv67lRxNJSu1fbov5Wr+eu'.
    'tv8zJ0PWtV13ddRQ2cNmjQ5iYsZGEkEUhG7oCvmHBwd3TCY3HOfxfqAu/KgggmW5WKeyaVREGheeKN'.
    'SdsjthhLkMVTBU/KeQOySKONpGSNVaRtzlRgscAZPqcAD6AVXXStOR2dbC1DtIZSwhUEuWVi3TqWRD'.
    'n1UHsKThO2jHDE4dTblT000/wCDvv1+XpnSard6XeW0WrSWvkSRy/6RDG6+ZIDHsRUJJ3kNJhAWLbM'.
    'juovaLeSajoWn30yqstzbRzOEGFBZQTjPbmn31k975ai+uraMZEiQFV80HHBYqWXvypU89c4xYiijg'.
    'hSGGNY4o1CoiDCqBwAAOgq0mpeRhUnTlSVl73X8fLz6aadDDS/vYo9dIMD3EF6sUG5iqndHEVGGfG7'.
    '5wAoKBm/u7iavaNd3F3ZubsxfaI5DHIEQoVOAcMhLbSM9mYEYYHDCpk0rTo/tXl2Fqv2vP2nbCo87O'.
    'c7+Pm6nr6mpbW1t7K3S3tLeKCBM7Y4kCKuTk4A46kmiMZJjq1aUoNJa6a/JJ/j08zDm1nUYtRvDstf'.
    'sNrfwWe3DeZJ5ohGc5wu0y56HcOMLjcczUNc1Wfw1PMJoIWvtHm1C3aKNla3CqhKFt/zNiUYcbcFc7'.
    'TnA682tu2/dbxHfIsr5QfM642sfUjauD22j0ptvp9lZzTzW1pBBLcNumeKMK0h5OWIHJ5PX1NS4SfU'.
    '2p4qjCz5NVb52X+ev4bBZvIYTHPcwT3MTbZmgTYoP3gNpZip2lTye+e9U5Z508U2lvuU28llO4Ubgw'.
    'ZXiHPzbSCHHVcjBwfmIq9a2tvZW6W9pbxQQJnbHEgRVycnAHHUk019PspL6O+e0ga8jXalw0YMijng'.
    'NjIHJ/M1bTsjmjOCnJvZp9F1X4fL0OatPEOryadZTTx2Hn6hYC6gRCVCNmJdpLMA5YzAhcpgjbuOd4'.
    'Jr7Wbp9HWO8tbe4+3yQzqbd+v2eRwrxiTjGDxuYN8jhsYB6U6fZGFYTaQGJYTAqGMbRGcAoBj7p2jj'.
    'pwPSmf2Vp39nf2f8AYLX7D/z7eSvl9d33cY68/Ws/Zy6s6vrVFO8YWevRbO669lbTq9W76mHf+J3g1'.
    'S1+xmK40+SeC1dwq4Mku0ja5kBPyOj4WNgRn5hyVvaXGZbnX7aSadovtu1czvuQNBExCtnKjLMRgjG'.
    'eMVoyafZTXIuZbSB7gKqiVowWwGDAZxnAYBh7jNMTStOj+1eXYWq/a8/adsKjzs5zv4+bqevqarlle'.
    '7M3Xo+z5Yqztb8U7/n95zUGsasmheHILGFrq8u9OW4lldVlbCrGDkNLHkkyA53Z46HOQX+t6lKse8Q'.
    'WIXUbK0ltzKfN8xzDI4VwcMMOUK45AZt2Plron0XSpLGOxfTLNrONtyW7QKY1PPIXGAeT+ZqaTT7Ka'.
    '5FzLaQPcBVUStGC2AwYDOM4DAMPcZqfZzta5ssXQUubk6t+e911ttptoSyiQwuIXVJSp2M67lB7EgE'.
    'ZHtkfWubstU1O6s/D8Fn9lje90w3Mss4kl8sqIeg3ZfPmEctnvk4w3SSxRzwvDNGskUilXRxlWB4II'.
    'PUVFbafZWaxLa2kECxKyxiKMKEDEFgMDgEgE+pAq5RbehyUasIRfMrv/gNfm0/kM0q+/tPSLK/8vy/'.
    'tUEc2zdnbuUHGe+M1zQ8XzyR6dDGsBvJ7ZftS7G229w0sEW089VMzFo87uFBK5yetiijghSGGNY4o1'.
    'CoiDCqBwAAOgqjBo8S+a15PLqMkkZiL3aocRnqgVVVcHvxk4GSQBhSU2kkzSjUoRlKU43V9F9/9bmd'.
    'aa7cJrMun372oS1jnae5VTGp2LbuGwWO0BZyDkn7ucjpUXh7WdZ1uJbkpYJAnkeYuHDP5kEUjYOSF2'.
    'mQkfe3cD5cbjuLpWnJbwW6WFqsFvIJYYxCoWNwSQyjGAcknI9TUtva29ohS2t4oUOMrGgUcKFHT0VV'.
    'H0AHakoSvq9CpV6HI1GGr6/PV26X/AA6EWp3E1rp0stuIjPwsYlYAFiQAOSASSeFyuTgZXORjW/iKe'.
    'CaBdTaBIysySssTI4mXyykWzLYch3wql94UMpIOK6GWKOeF4Zo1kikUq6OMqwPBBB6iqMmjxC3htbO'.
    'eXT7SPcDBZKkauCckZ25XvyhU8k5zghyUr3RFCdFR5ai+fy76/LS3cxtO8QaleDTbiQWaWs8Nv5pAJ'.
    'XzZEVihffmNvnyqshDfKN4LgCG01fUtP0G5luLmC9vHvZYbaFYyr5+1mEnDSfMoLphcqAMKW53V0q6'.
    'VpyXEFwlharPbxiKGQQqGjQAgKpxkDBIwPU086fZM1yxtIC10oW4JjGZgBgB+PmGCRz2qVCXc2eKoX'.
    'soaXT2XRvT8d9+mxy83iDXraSzgurSC3lmZlJljBLgyQRI4VJWCgNOSRuJYR9V3cdLpV9/aekWV/wC'.
    'X5f2qCObZuzt3KDjPfGafbafZWaxLa2kECxKyxiKMKEDEFgMDgEgE+pAqWKKOCFIYY1jijUKiIMKoH'.
    'AAA6CqjGSerMa9alUglGFnfdad+mvl18tijpv2j7fq/n+b5f2tfI35xs8iLO3Pbdu6d8980aB9o/wC'.
    'Ec0v7Z5v2r7JF53nZ379g3bs85znOavJFHG0jJGqtI25yowWOAMn1OAB9AKIoo4IUhhjWOKNQqIgwq'.
    'gcAADoKpRszKdVSi1bt+CsPoooqjEKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAK'.
    'KKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoooo'.
    'AKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAoo'.
    'ooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigA'.
    'ooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiii'.
    'gAooooAKKKKACiiigAooooAKKKKACiiigAooooA//2Q==');
}
 
/*
* Check if the given color is an rgb color array
*
* @param array $color the color to test
*
* @return bool
* @access private
*/
function _isRgbColor($color){
    if(!isset($color['red'],$color['green'],$color['blue']))
        return false;
 
    return ($color['red'] < 255 && $color['red'] > 0 &&
    $color['green'] < 255 && $color['green'] > 0 &&
    $color['blue'] < 255 && $color['blue'] > 0);
}
 
/*
* convert an hex color to an rgb color array
*
* @param array $color the color to convert
*
* @return array
* @access private
*/
function _hex2rgb($hex){
    $hex = strtr($hex,'#','');
 
    if(strlen($hex) === 3)
        return array('red' => hexdec($color{0}.$color{0}),'green' => hexdec($color{1}.$color{1}),'blue' => hexdec($color{2}.$color{2}));
    else
        return array('red' => hexdec(substr($hex,0,2)),'green' => hexdec(substr($hex,2,2)),'blue' => hexdec(substr($hex,4,2)));
    }
}
?>