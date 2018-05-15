<?php

define('CAPTCHA_WIDTH', 200); // max 500
define('CAPTCHA_HEIGHT', 50); // max 200
define('CAPTCHA_NUM_CHARS', 5);
define('CAPTCHA_NUM_LINES', 70);
define('CAPTCHA_CHAR_SHADOW', false);
define('CAPTCHA_OWNER_TEXT', '');
define('CAPTCHA_CHAR_SET', ''); // defaults to A-Z
define('CAPTCHA_BACKGROUND_IMAGES', '');
define('CAPTCHA_MIN_FONT_SIZE', 16);
define('CAPTCHA_MAX_FONT_SIZE', 25);
define('CAPTCHA_USE_COLOR', true);
define('CAPTCHA_FILE_TYPE', 'jpeg');

class PhpCaptcha {
    var $oImage;
    var $aFonts;
    var $iWidth;
    var $iHeight;
    var $iNumChars;
    var $iNumLines;
    var $iSpacing;
    var $bCharShadow;
    var $aCharSet;
    var $vBackgroundImages;
    var $iMinFontSize;
    var $iMaxFontSize;
    var $bUseColor;
    var $sFileType;
    var $sCode = '';
    
    function PhpCaptcha($aFonts, $iWidth = CAPTCHA_WIDTH, $iHeight = CAPTCHA_HEIGHT) {
        $this->aFonts = $aFonts;
        $this->SetNumChars(CAPTCHA_NUM_CHARS);
        $this->SetNumLines(CAPTCHA_NUM_LINES);
        $this->DisplayShadow(CAPTCHA_CHAR_SHADOW);
        $this->SetCharSet(CAPTCHA_CHAR_SET);
        $this->SetBackgroundImages(CAPTCHA_BACKGROUND_IMAGES);
        $this->SetMinFontSize(CAPTCHA_MIN_FONT_SIZE);
        $this->SetMaxFontSize(CAPTCHA_MAX_FONT_SIZE);
        $this->UseColor(CAPTCHA_USE_COLOR);
        $this->SetFileType(CAPTCHA_FILE_TYPE);   
        $this->SetWidth($iWidth);
        $this->SetHeight($iHeight);
    }
    
    function CalculateSpacing() {
        $this->iSpacing = (int)($this->iWidth / $this->iNumChars);
    }
    
    function SetWidth($iWidth) {
        $this->iWidth = $iWidth;
        if ($this->iWidth > 500) {
            $this->iWidth = 500; // to prevent perfomance impact
        }    
        $this->CalculateSpacing();
    }
    
    function SetHeight($iHeight) {
        $this->iHeight = $iHeight;
        if ($this->iHeight > 200) {
            $this->iHeight = 200; // to prevent performance impact
        }
    }
    
    function SetNumChars($iNumChars) {
        $this->iNumChars = $iNumChars;
        $this->CalculateSpacing();
    }
    
    function SetNumLines($iNumLines) {
        $this->iNumLines = $iNumLines;
    }
    
    function DisplayShadow($bCharShadow) {
        $this->bCharShadow = $bCharShadow;
    }
    
    function SetCharSet($vCharSet) {
        // check for input type
        if (is_array($vCharSet)) {
            $this->aCharSet = $vCharSet;
        } else {
            if ($vCharSet != '') {
                // split items on commas
                $aCharSet = explode(',', $vCharSet);

                // initialise array
                $this->aCharSet = array();

                // loop through items 
                foreach ($aCharSet as $sCurrentItem) {
                    // a range should have 3 characters, otherwise is normal character
                    if (strlen($sCurrentItem) == 3) {
                        // split on range character
                        $aRange = explode('-', $sCurrentItem);

                        // check for valid range
                        if (count($aRange) == 2 && $aRange[0] < $aRange[1]) {
                            // create array of characters from range
                            $aRange = range($aRange[0], $aRange[1]);

                            // add to charset array
                            $this->aCharSet = array_merge($this->aCharSet, $aRange);
                        }
                    } else {
                        $this->aCharSet[] = $sCurrentItem;
                    }
                }
            }
        }
    }
    
    function SetBackgroundImages($vBackgroundImages) {
        $this->vBackgroundImages = $vBackgroundImages;
    }
    
    function SetMinFontSize($iMinFontSize) {
        $this->iMinFontSize = $iMinFontSize;
    }
    
    function SetMaxFontSize($iMaxFontSize) {
        $this->iMaxFontSize = $iMaxFontSize;
    }
    
    function UseColor($bUseColor) {
        $this->bUseColor = $bUseColor;
    }
    
    function SetFileType($sFileType) {
        // check for valid file type
        if (in_array($sFileType, array('gif', 'png', 'jpeg'))) {
            $this->sFileType = $sFileType;
        } else {
            $this->sFileType = 'jpeg';
        }
    }
    
    function DrawLines() {
        for ($i = 0; $i < $this->iNumLines; $i++) {
            // allocate color
            if ($this->bUseColor) {
                $iLineColor = imagecolorallocate($this->oImage, rand(100, 250), rand(100, 250), rand(100, 250));
            } else {
                $iRandColor = rand(100, 250);
                $iLineColor = imagecolorallocate($this->oImage, $iRandColor, $iRandColor, $iRandColor);
            }
            
            // draw line
            imageline($this->oImage, rand(0, $this->iWidth), rand(0, $this->iHeight), rand(0, $this->iWidth), rand(0, $this->iHeight), $iLineColor);
        }
    }
    
    function GenerateCode() {
        // reset code
        $this->sCode = '';

        // loop through and generate the code letter by letter
        for ($i = 0; $i < $this->iNumChars; $i++) {
            if (count($this->aCharSet) > 0) {
                // select random character and add to code string
                $this->sCode .= $this->aCharSet[array_rand($this->aCharSet)];
            } else {
                // select random character and add to code string
                $this->sCode .= chr(rand(65, 90));
            }
        }

        return $this->sCode;
    }
    
    function DrawCharacters() {
        // loop through and write out selected number of characters
        for ($i = 0; $i < strlen($this->sCode); $i++) {
            // select random font
            $sCurrentFont = $this->aFonts[array_rand($this->aFonts)];

            // select random color
            if ($this->bUseColor) {
                $iTextColor = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));

                if ($this->bCharShadow) {
                    // shadow color
                    $iShadowColor = imagecolorallocate($this->oImage, rand(0, 100), rand(0, 100), rand(0, 100));
                }
            } else {
                $iRandColor = rand(0, 100);
                $iTextColor = imagecolorallocate($this->oImage, $iRandColor, $iRandColor, $iRandColor);

                if ($this->bCharShadow) {
                // shadow color
                $iRandColor = rand(0, 100);
                $iShadowColor = imagecolorallocate($this->oImage, $iRandColor, $iRandColor, $iRandColor);
                }
            }
            
            // select random font size
            $iFontSize = rand($this->iMinFontSize, $this->iMaxFontSize);
            
            // select random angle
            $iAngle = rand(-30, 30);
            
            // get dimensions of character in selected font and text size
            $aCharDetails = imageftbbox($iFontSize, $iAngle, $sCurrentFont, $this->sCode[$i], array());
			
            // calculate character starting coordinates
            $iX = $this->iSpacing / 4 + $i * $this->iSpacing;
            $iCharHeight = $aCharDetails[2] - $aCharDetails[5];
            $iY = $this->iHeight / 2 + $iCharHeight / 4; 
            
            // write text to image
            imagefttext($this->oImage, $iFontSize, $iAngle, $iX, $iY, $iTextColor, $sCurrentFont, $this->sCode[$i], array());
            
            if ($this->bCharShadow) {
                $iOffsetAngle = rand(-30, 30);
                
                $iRandOffsetX = rand(-5, 5);
                $iRandOffsetY = rand(-5, 5);
                
                imagefttext($this->oImage, $iFontSize, $iOffsetAngle, $iX + $iRandOffsetX, $iY + $iRandOffsetY, $iShadowColor, $sCurrentFont, $this->sCode[$i], array());
            }
        }
    }
    
    function WriteFile($sFilename) {
        switch ($this->sFileType) {
            case 'gif':
                imagegif($this->oImage, $sFilename);
                break;
            case 'png':
                imagepng($this->oImage, $sFilename);
                break;
            default:
                imagejpeg($this->oImage, $sFilename);
        }
    }
    
    function Create($secretPhrase, $directory) {
        // check for required gd functions
        if (!function_exists('imagecreate') || !function_exists("image$this->sFileType") || ($this->vBackgroundImages != '' && !function_exists('imagecreatetruecolor'))) {
            return false;
        }
        
        // get background image if specified and copy to CAPTCHA
        if (is_array($this->vBackgroundImages) || $this->vBackgroundImages != '') {
            // create new image
            $this->oImage = imagecreatetruecolor($this->iWidth, $this->iHeight);
            
            // create background image
            if (is_array($this->vBackgroundImages)) {
                $iRandImage = array_rand($this->vBackgroundImages);
                $oBackgroundImage = imagecreatefromjpeg($this->vBackgroundImages[$iRandImage]);
            } else {
                $oBackgroundImage = imagecreatefromjpeg($this->vBackgroundImages);
            }
            
            // copy background image
            imagecopy($this->oImage, $oBackgroundImage, 0, 0, 0, 0, $this->iWidth, $this->iHeight);
            
            // free memory used to create background image
            imagedestroy($oBackgroundImage);
        } else {
            // create new image
            $this->oImage = imagecreate($this->iWidth, $this->iHeight);
        }
        
        // allocate white background color
        imagecolorallocate($this->oImage, 255, 255, 255);
        
        // check for background image before drawing lines
        if (!is_array($this->vBackgroundImages) && $this->vBackgroundImages == '') {
            $this->DrawLines();
        }
        
        $sCode = $this->GenerateCode();
        $sFilename = md5($secretPhrase . $sCode) . '.jpg';
        
        $this->DrawCharacters();
        
        if (!file_exists($directory)) {
            mkdir($directory);
        }
        $this->WriteFile($directory . '/' . $sFilename);
        
        // free memory used in creating image
        imagedestroy($this->oImage);
        
        return $sFilename;
    }
    
    function Validate($secretPhrase, $hash, $code) {
        return (md5($secretPhrase . strtoupper($code)) == $hash) ? true : false;
    }
}
   
?>
