<?php
namespace ICE\lib\images;
define('IMAGETYPE_TGA',6666);

class ImageAbstract extends \ICE\core\IObject{
    public $width;
    public $height;
    public $header;
    public $handle;
    public $quality=100;

    function __construct($file=''){
        if(!empty($file)){
            $avInfos = getimagesize($file);
            $this->width = $avInfos[0];
            $this->height = $avInfos[1];
            $this->quality = 100;
            $this->header = image_type_to_mime_type($this->type);
        }
    }

    function __destruct(){
        imagedestroy($this->handle);
    }

    public static function factory($file){
        $avImgInfos = getimagesize($file);
               //var_dump($file);
        switch($avImgInfos[2]){
            case IMAGETYPE_GIF :
                      //  require_once('graphic/ImageGIF.inc.php');
            return new ImageGIF($file);
            break;
            case IMAGETYPE_JPEG :

                     //   require_once('graphic/ImageJPEG.inc.php');
            return new ImageJPEG($file);
            break;
            case IMAGETYPE_PNG :
                        //var_dump('png');
                     //           require_once('graphic/ImagePNG.inc.php');
            return new ImagePNG($file);
            break;
            case IMAGETYPE_JPEG :
            case IMAGETYPE_JPEG2000:
                     //   require_once('graphic/ImageJPEG.inc.php');
            return new ImageJPEG($file);
            break;
            default:
            if(strtolower(substr($file,-4))==".tga"){
                           //     require_once('graphic/ImageTGA.inc.php');
                return new TimageTGA($file);
            }

        }
    }

    public function isLandscape(){
        return $this->width > $this->height;
    }

    public function isPortrait(){
        //var_dump($this->width <= $this->height,$this->width,$this->height);
        return $this->width <= $this->height;
    }

    function mergeFrom($source, $dst_x,  $dst_y,  $src_x,  $src_y,  $src_w,  $src_h, $alpha=100){
		//var_dump($dst_x,$dst_y, $src_x,$src_y, $src_w, $src_h);
     imagecopymerge ( $this->handle, $source->handle, $dst_x,$dst_y, $src_x,$src_y, $src_w, $src_h, $alpha);
 }

 public static function create($width,$height,$type=IMAGETYPE_JPEG){
                //require_once('graphic/ImageJPEG.inc.php');

    $class = "";
    switch($type){
        case IMAGETYPE_GIF :
               //         require_once('graphic/ImageGIF.inc.php');
        $class= "ImageGif";
        break;

        case IMAGETYPE_PNG :
                        //var_dump('png');
              //                  require_once('graphic/ImagePNG.inc.php');

        $class= "ImagePNG";
        break;
        case IMAGETYPE_JPEG :
        case IMAGETYPE_JPEG2000:
             //           require_once('graphic/ImageJPEG.inc.php');
        $class= "\ICE\lib\images\ImageJPEG";
        break;
        default:
        if(strtolower(substr($file,-4))==".tga"){
              //                  require_once('graphic/ImageTGA.inc.php');
            $class= "ImageTGA";                               }
            break;

        }
                //var_dump($class);

        $tmp = new $class();
        $tmp->width = $width;
        $tmp->height = $height;
        $tmp->quality = 100;
        $tmp->handle = imagecreatetruecolor($width, $height);
        $trans_colour = imagecolorallocatealpha($tmp->handle, 0, 0, 0, 127);
        imagefill($tmp->handle, 0, 0, $trans_colour);
        return $tmp;

    }

    function enableAntiAlias(){
        imageantialias($this->handle,true);

    }

    function disableAntiAlias(){
        imageantialias($this->handle,false);

    }

    function disposition(){
        if($this->width > $this->height){
            return 'landscape';
        }else{
            return 'portrait' ;
        }
    }


    function getTextBox($text,$font,$size,$angle=0){
        $datas = imagettfbbox($size, $angle, $font, $text);

        $width = $datas[4]-$datas[0];
//              $height = abs($datas[5]-$datas[1]);
        $height = abs($datas[7] - $datas[1]) ;
        $baseline = $datas[7];
//              var_dump($width);
//              var_dump($height);
        $datas['width']= $width;
        $datas['height'] = $height;
        $datas['baseline'] = $baseline;
        return $datas;
//              array imagettfbbox ( int size, int angle, string fontfile, string text)
    }


    function drawTextKerning($text,$size,$x,$y,$color,$font,$angle=0,$kerning=0){

        for ($i=0;$i< strlen($text);$i++){
            $char = $text[$i];
                        if($pval){ // check for existing previous character
                            $box = $this->getTextBox($pval,$font, $size);
                            $x+=$box['width']+$kerning;
                        }
                        $pval = $char;
                        $this->drawText($char,$size,$x,$y,$color,$font,$angle);
                    }


                }

                function drawText($text,$size,$x,$y,$color,$font,$angle=0){
                    return imagettftext ( $this->handle, $size,$angle , $x ,$y, $color, $font,$text);

                //array imagettftext ( resource image, int size, int angle, int x, int y, int color, string fontfile, string text)
                }

                function drawPSText($text,$size,$x,$y,$fgColor,$bgColor,$font){
//array imagepstext  ( resource $image  , string $text  , resource $font_index  , int $size  , int $foreground  , int $background  , int $x  , int $y  [, int $space= 0  [, int $tightness= 0  [, float $angle= 0.0  [, int $antialias_steps= 4  ]]]] )
                    return imagepstext($this->handle,$text,$font,$size,$foreground,$background,$x,$y,0,0,0,16);
                }

        /*function drawPSText(){
                return imagepstext($im, 'Sample text is simple', $font, 12, $black, $white, 50, 50);
               
            }*/

            function setQuality($quality){
                $this->quality = $quality;
            }

            function clean(){
                imagedestroy($this->handle);
            }

            function getColor($r,$g,$b){
                return imagecolorallocate($this->handle,$r,$g,$b);
            }

            function getColorFromHexa($rgb){
                $rgb = hexdec($rgb);
                $r = ($rgb & 0xFF0000) >> 16;
                $g = ($rgb & 0x00FF00) >> 8;
                $b = ($rgb & 0x0000FF);
                return imagecolorallocate($this->handle,$r,$g,$b);
            }

            function getTransparency($color){
                return imagecolortransparent($this->handle,$color);
            }

            function line($x,$y,$x2,$y2,$color){
                imageline($this->handle,$x,$y,$x2,$y2,$color);
            }

            function writeToFile($file){
                imagegd($this->handle,$file);
            }

            function rotate($angle,$background = 0){
                $this->handle = imagerotate($this->handle,$angle,$background);
                $this->width = imagesx($this->handle);
                $this->height = imagesy($this->handle);
            }

            function filledRectangle($x1,$y1,$x2,$y2,$color){
                imagefilledrectangle($this->handle,$x1,$y1,$x2,$y2,$color);
            }

            function setBackgroundColor($color){
                $this->filledRectangle(0,0,$this->width,$this->height,$color);
            }
            function base64(){
                ob_start();
                imagegd($this->handle);
                $sBuffer = ob_get_contents();
                ob_end_clean();
                return base64_encode($sBuffer);
            }

            function getHeader(){

                return "Content-type: ".$this->header;
            }

            function printHeader(){

                header($this->getHeader());
            }

            function draw(){
                imagegd($this->handle);
            }

            function setColor($index,$r,$g,$b){
                return imagecolorset ($this->handle,$index,$r,$g,$b);
            }

            function getColorIndex($r,$g,$b){
                return imagecolorexact($this->handle,$r,$g,$b);
            }

            function setPixel($x,$y,$color){
                imagesetpixel($this->handle,$x,$y,$color);
            }

            function getPixelColor($x,$y){
                $rgb = imagecolorat($this->handle,$x,$y);
                $alpha = ($rgb & 0x7F000000) >> 24;
                $r = ($rgb & 0xFF0000) >> 16;
                $g = ($rgb & 0x00FF00) >> 8;
                $b = ($rgb & 0x0000FF);
                return array($r,$g,$b,$alpha);
            }

            function convertToFile($type=IMAGETYPE_JPEG,$file=null){
                $this->header = image_type_to_mime_type($type);
                $bWrite = !empty($file);
                if(!$bWrite){
                    header($this->getHeader());
                }
                switch($type){
                    case IMAGETYPE_GIF :
                    if($bWrite){
                        imagegif($this->handle,$file);
                    }else{
                        imagegif($this->handle);
                    }
                    break;
                    case IMAGETYPE_JPEG :
                    if($bWrite){
                        imagejpeg($this->handle,$file,$this->quality);
                    }else{
                        imagejpeg($this->handle,'',$this->quality);

                    }
                    break;
                    case IMAGETYPE_PNG :
                    if($bWrite){
//                                      imagealphablending($this->handle, false);
//                                      imagesavealpha($this->handle, true);
                        imagepng($this->handle,$file);
                    }else{
//                                      imagealphablending($this->handle, false);
//                                      imagesavealpha($this->handle, true);
                        imagepng($this->handle);
                    }
                    break;
                    default:
                }
            }

            function setAntialiasing($enable = true){
                imageantialias($this->handle,$enable);

            }

            function arc($x,$y,$width,$height,$start,$end,$color,$fill=false,$style=""){
                if($fill){
                    return imagefilledarc ( $this->handle, $x, $y, $width,$height, $start, $end, $color, $style);
                }else{
                    return imagearc ( $this->handle, $x, $y, $width,$height, $start, $end,$color);
                }
            }
            function propResizeWithWidth($width,$resample=true){

                $origWidth = $this->width;
                $origHeight = $this->height;
                
                
                
                $newWidth = $width;

                $newHeight = intval(ceil($origHeight * ($width / $origWidth)));
                
                
                if($newWidth > $origWidth){
                	$newWidth = $origWidth;
                }
                
                if($newHeight > $origHeight){
                	$newHeight = $origHeight;
                }
                
                $img = ImageAbstract::create($newWidth,$newHeight,$this->type);
               // var_dump($newWidth,$newHeight);
                if(!$resample){
                    imagecopyresized($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                }else{
                    imagecopyresampled($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                }
                
                return $img;
            }
            function propResizeWithHeight($height,$resample=true){
                $origWidth = $this->width;
                $origHeight = $this->height;
                $newWidth = intval(ceil($origWidth * ($height / $origHeight)));

                $newHeight = $height;
                $img = ImageAbstract::create($newWidth,$newHeight);

                if(!$resample){
                    imagecopyresized($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                }else{
                    imagecopyresampled($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                }

                return $img;
            }
            function resize($width,$height,$keepProportions=true,$resample=true){

                if(empty($width) || !is_numeric($width)){
                    $width=$height;
                }
                if(empty($height) || !is_numeric($height)){
                    $height=$width;
                }
               /* if($keepProportions){
                    $origWidth = $this->width;
                    $origHeight = $this->height;
                    if($origWidth >= $origHeight){
                        $newWidth = $width;
                        $newHeight = intval($origHeight * ($width / $origWidth));
                    }else{
                        $newHeight = $height;
                        $newWidth = intval($origWidth * ($height / $origHeight));
                    }
                }else{
                    $newWidth = $width;
                    $newHeight = $height;
                }*/
               /*
                $img = ImageAbstract::create($newWidth,$newHeight);
        //      var_dump($img);
               
                if(!$resample){
                        imagecopyresized($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                }else{
                        imagecopyresampled($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                }
               */
                if($keepProportions){
                    if($this->isPortrait()){
                        return $this->propResizeWithHeight($height,$resample);
                    }else{
                        return $this->propResizeWithWidth($width,$resample);

                    }
                }else{
                    $newWidth = $width;
                    $newHeight = $height;
                    if(!$resample){
                        imagecopyresized($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                    }else{
                        imagecopyresampled($img->handle,$this->handle,0,0,0,0,$newWidth,$newHeight,$this->width,$this->height);
                    }
                }
                return $img;

                /*
                $this->layers[$layerName]->clear();
                $this->layers[$layerName]->width="12";
                $this->layers[$layerName] = $this->layers['imageCopyTemp'];
                unset($this->layers['imageCopyTemp']);*/
            }

            function convertHexToRGB($hex) {
                $hex = str_replace("#", "", $hex);
                $color = array();

                if(strlen($hex) == 3) {
                    $color['r'] = hexdec(substr($hex, 0, 1) . $r);
                    $color['g'] = hexdec(substr($hex, 1, 1) . $g);
                    $color['b'] = hexdec(substr($hex, 2, 1) . $b);
                }
                else if(strlen($hex) == 6) {
                    $color['r'] = hexdec(substr($hex, 0, 2));
                    $color['g'] = hexdec(substr($hex, 2, 2));
                    $color['b'] = hexdec(substr($hex, 4, 2));
                }

                return $color;
            }

            function ImageCurveDown ( $x1, $y1, $x2, $y2, $height, $color) {
                $presicion = 1;

                for ($left = ($x1-$x2); $left < 0; $left++){
                    if ($y1 < $y2) {
                        $cy = $y2 + $height;
                        $cx = $x1 - $left;
                    } else {
                        $cy = $y1 + $height;
                        $cx = $x2 + $left;
                    }
                    $nx1 = abs($x1 - $cx);
                    $ny1 = abs($y1 - $cy);
                    $nx2 = abs($x2 - $cx);
                    $ny2 = abs($y2 - $cy);

                    if ($y1 < $y2) {
                        if ($nx2 == 0 || $ny1 == 0) continue;
                        $angle1 = atan($height/$nx2);
                        $A1 = $nx2/cos ($angle1);
                        $B1 = $ny2/sin ($angle1);
                        $angle2 = pi()/2 +atan($left/$ny1);
                        $A2 = $nx1/cos ($angle2);
                        $B2 = $ny1/sin ($angle2);
                    } else {
                        if ($ny2 == 0 || $nx1 == 0) continue;
                        $angle1 = atan($ny2/$nx2);
                        $A1 = abs($nx2/cos ($angle1));
                        $B1 = abs($ny2/sin ($angle1));
                        $angle2 = atan($height/$nx1);
                        $A2 = abs ($nx1/cos ($angle2));
                        $B2 = abs($ny1/sin ($angle2));
                    }

                    if (abs($A1 - $A2) < $presicion && abs ($B1 - $B2) < $presicion) {
                        ImageArc($this->handle, $cx, $cy, $A1*2, $B1*2, 180+rad2deg($angle2), 360-rad2deg($angle1), $color);
                    }
                }
            }
        }
