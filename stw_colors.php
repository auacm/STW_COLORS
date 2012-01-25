<?php
// hex() - get hex
// rgb() - get rgb
// hsb() - get hsb

// __toString() - for printing
// __construct($hex) - construct with hex string

// get_bright($offset) - return new stw_color object with offset on brightness(+/-)
// bright($offset) - return object with offset on brightness(+/-)
// get_sat($offset) - return new stw_color object with offset on sat(+/-)
// sat($offset) - return object with offset on sat(+/-)
// get_hue($offset) - return new stw_color object with offset on hue(+/-)
// hue($offset) - return object with offset on hue(+/-)

// adjust_hue(&$val,$offset,$step) - actually offsets the hue, but modulates color rather than clamp
// adjust(&$val,$offset,$step)          - offsets $val, but clamps it to SAT/BRIGHT range

// get_desat()  - return new stw_color object as grayscale
// desat()      - return object as grayscale

// contrast($hex)       - returns adjusted object so it exceeds minimum contrast with $hex
//                        allow options for adjusting only certain values to achieve contrast (H,S, or B)
// get_contrast($hex)   - returns new adjusted object so it exceeds minimum contrsat with $hex,
//                        allow options for adjusting only certain values to achieve contrast (H,S, or B)
// static gen_contrast($hex1,$hex2)     - returns new adjusted $hex1 as object so that it contrasts with $hex2,
//                                        $hex2 can either be a hex string or a stw_color object
//                                        allow options for adjusting only certain values to achieve contrast (H,S, or B)
// the_contrast($hex1,$hex2)    - returns contrast value between 2 colors

class   stw_color {
    const       DEFAULT_STEP    = 16;
    const       HUE             = 0;
    const       SAT             = 1;
    const       BRIGHT          = 2;
    const       RED             = 0;
    const       GREEN           = 1;
    const       BLUE            = 2;
    
    const       MAX_HUE         = 360.0;
    const       MAX_SAT         = 100.0;
    const       MAX_BRIGHT      = 100.0;
    protected   $maxes;
    protected   $_rgb;
    protected   $_hsb;

    // grabber function for hex, see also: __toString
    public      function        hex() {
            return $this->toHex($this->_rgb);
    }

    // grabber function for RGB
    public      function        rgb($newVal=NULL) {
        if ( $newVal != NULL && is_array($newVal) && sizeof($newVal) === 3 )
            $this->_rgb = $this->clampRGB($newVal);
            
            return $this->_rgb;
    }

    // grabber function for HSB
    public      function        hsb($newVal=NULL) {
        if ( $newVal != NULL && is_array($newVal) && sizeof($newVal) === 3 )
            $this->_hsb = $this->clampHSB($newVal);

        return $this->_hsb;
    }
    
    public      static function fromHSB($hsb) {
        $rgb    = self::toRGB($hsb);
        return new stw_color(self::toHex($rgb));
    }

    // take hex, format "#333", "333", "333000" or "#333000"
    // convert to HSB, RGB, store
    public      function        stw_color( $rgbHEX ) {
        $this->maxes    = array(self::MAX_HUE, self::MAX_SAT, self::MAX_BRIGHT);
        preg_match("/\#?([0-9A-Fa-f]{6}|[0-9A-Fa-f]{3})/", $rgbHEX, $subpatterns);
        $color_len  =   2;
        
        if ( strlen($subpatterns[1]) == 3 )
            $color_len  = 1;
            
        $color1 =   substr($subpatterns[1], 0, $color_len);
        $color2 =   substr($subpatterns[1], $color_len, $color_len);
        $color3 =   substr($subpatterns[1], $color_len*2, $color_len);

        // now for #333, repeat the values so they read #333333        
        if ( strlen($subpatterns[1]) == 3 ) {
            $color1 .=  $color1;
            $color2 .=  $color2;
            $color3 .=  $color3;
        }
        
        $this->_rgb = array(hexdec($color1), hexdec($color2), hexdec($color3));
        
        // convert RGB to HSB, store
        $this->_hsb =$this->toHSB($this->_rgb);
    }
    
    // print function, prints this colors hex
    public      function                __toString() {
        return $this->hex();
    }
    
    // return modified color after modifying current color
    public      function                bright($delta, $step = -1) {
        $this->adjust_hue($this->_hsb[self::BRIGHT],$offset,$step);
        $this->rgb($this->_toRGB());
        return $this;
    }
    
    // return new color using current color as base
    public      function                get_bright($delta, $step = -1) {
        $hsb    = $this->_hsb;
        $this->adjust_hue($hsb[self::BRIGHT],$offset,$step);
        return stw_color::fromHSB($hsb);
    }
    
    // return modified color after modifying current color
    public      function                sat($delta, $step = -1) {
        $this->adjust_hue($this->_hsb[self::SAT],$offset,$step);
        $this->rgb($this->_toRGB());
        return $this;
    }
    
    // return new color using current color as base
    public      function                get_sat($offset, $step = -1) {
        $hsb    = $this->_hsb;
        $this->adjust_hue($hsb[self::SAT],$offset,$step);
        return stw_color::fromHSB($hsb);
    }
    
    // return modified color after modifying current color
    public      function                hue($offset, $step = -1) {
        $this->adjust_hue($this->_hsb[self::HUE],$offset,$step);
        $this->rgb($this->_toRGB());
        return $this;
    }
    
    // return new color using current color as base
    public      function                get_hue($offset, $step = -1) {
        $hsb    = $this->_hsb;
        $this->adjust_hue($hsb[self::HUE],$offset,$step);
        return stw_color::fromHSB($hsb);
    }
    
    protected   function                adjust_hue(&$val,$offset,$step=-1) {
        if ( $step <= 0 )
            $step       = self::DEFAULT_STEP;
        
        $hsb            = $this->_hsb;
        $hsb[self::HUE] += $offset*$step;
            
        $delta  = (int)$hsb[self::HUE] - (int)$this->_hsb[self::HUE];
        if ( $delta < 0 )
            $delta      += (int)self::MAX_HUE;
        else
            $delta      = $delta%(int)self::MAX_HUE;
            
        $val    += $delta;
        $val    = $val%self::MAX_HUE;
        
        $val    = $this->clamp($val,0,self::MAX_HUE);
    }
    
    // return modified color after modifying $val
    protected   function                adjust(&$val,$offset,$step=-1) {
        if ( $step <= 0 )
            $step       = self::DEFAULT_STEP;
        $val    += $offset*$step;
        $val    = $this->clamp($val,0,self::MAX_BRIGHT); // should work for MAX_SAT too
    }
    
    // clamp value to [min,max]
    private     function clamp($v,$min,$max) { return (int)max($min,min($max,$v));    }
    
    // clamp RGB to valid RGB range
    private     function clampRGB(&$arg = NULL) {
        if ( $arg != NULL && is_array($arg) ) {
            $arg[self::RED]             = $this->clamp($arg[self::RED], 0, self::MAX_RGB );
            $arg[self::GREEN]           = $this->clamp($arg[self::GREEN], 0, self::MAX_RGB );
            $arg[self::BLUE]            = $this->clamp($arg[self::BLUE], 0, self::MAX_RGB );
            return; 
        }
        
        $this->_rgb[self::RED]          = $this->clamp($this->_rgb[self::RED], 0, self::MAX_RGB );
        $this->_rgb[self::GREEN]        = $this->clamp($this->_rgb[self::GREEN], 0, self::MAX_RGB );
        $this->_rgb[self::BLUE]         = $this->clamp($this->_rgb[self::BLUE], 0, self::MAX_RGB );
    }
    
    // clamp HSB to valid HSB range
    private     function clampHSB(&$arg = NULL ) {
        if ( $arg != NULL && is_array($arg) ) {
            $arg[self::HUE]             = $this->clamp($arg[self::HUE], 0, self::MAX_HUE );
            $arg[self::SAT]             = $this->clamp($arg[self::SAT], 0, self::MAX_SAT );
            $arg[self::BRIGHT]          = $this->clamp($arg[self::BRIGHT], 0, self::MAX_BRIGHT );
            return; 
        }
    
        $this->_hsb[self::HUE]          = $this->clamp($this->_hsb[self::HUE], 0, self::MAX_HUE );
        $this->_hsb[self::SAT]          = $this->clamp($this->_hsb[self::SAT], 0, self::MAX_SAT );
        $this->_hsb[self::BRIGHT]       = $this->clamp($this->_hsb[self::BRIGHT], 0, self::MAX_BRIGHT );
    }
    
    // version of toHex with optional RGB param, defaulting to object RGB
    protected   function        _toHex( $rgb = NULL ) {
        if ( $rgb == NULL || !is_array($rgb) )
            $rgb        = $this->_rgb;
            
        return $this->toHex($rgb);
    }
    
    // converts RGB value to hex
    public      static function    toHex($rgb) {
        $r      = $rgb[0];
        $g      = $rgb[1];
        $bl     = $rgb[2];
        
        $r      = ($r>0)?dechex($r):"00";
        $g      = ($g>0)?dechex($g):"00";
        $bl     = ($bl>0)?dechex($bl):"00";
        return '#'.$r.$g.$bl;
    }
    
    // version of toRGB with optional HSB param, defaulting to object HSB
    protected   function        _toRGB( $hsb = NULL ) {
        if ( $hsb == NULL || !is_array($hsb) )
            $hsb        = $this->_hsb;
            
        return $this->toRGB($hsb);
    }
    
    // converts HSB value to RGB
    public      static function toRGB( $hsb ) {
        $h  = $hsb[0];
        $s  = $hsb[1]/100;
        $b  = $hsb[2]/100;
        
        if ( $s == 0 )
            return array($b*255, $b*255, $b*255);

        $h  /=  60;
        $i  = (int)floor($h);
        $f  = $h-$i;
        $p  = ($b*(1-$s));
        $q  = ($b*(1-$s*$f));
        $t  = ($b*(1-$s*(1-$f)));

        $r  =0;
        $g  =0;
        $bl =0;
        switch($i){
            case 0:
                $r  = $b;
                $g  = $t;
                $bl = $p;
                break;
            case 1:
                $r  = $q;
                $g  = $b;
                $bl = $p;
                break;
            case 2:
                $r  = $p;
                $g  = $b;
                $bl = $t;
                break;
            case 3:
                $r  = $p;
                $g  = $q;
                $bl = $b;
                break;
            case 4:
                $r  = $t;
                $g  = $p;
                $bl = $b;
                break;
            default:
                $r  = $b;
                $g  = $p;
                $bl = $q;
                break;
        }
        
        return array((int)($r*255),(int)($g*255),(int)($bl*255));
    }

    
    // version of toHSB with optional RGB param, defaulting to object RGB
    protected   function        _toHSB( $rgb = NULL ) {
        if ( $rgb == NULL || !is_array($rgb) )
            $rgb        = $this->_rgb;
            
        return $this->toHSB($rgb);
    }
    
    // converts RGB value to HSB
    public      static function toHSB( $rgb = NULL ) {
        if ( $rgb == NULL || !is_array($rgb) )
            $rgb        = $this->_rgb;
            
        $r  = $rgb[0];
        $g  = $rgb[1];
        $bl  = $rgb[2];
        
        $minRGB = min(min($r,$g),$bl);
        $maxRGB = max(max($r,$g),$bl);
        $delta  = $maxRGB-$minRGB;
        $b      = $maxRGB;
        
        if ( $maxRGB!=0 )
            $s  =  255*$delta/$maxRGB;
        else
            $s  = 0;
        
        if ( $s != 0 ) {
            if ( $r == $maxRGB )
                $h  = ($g-$bl)/$delta;
            else if ( $g == $maxRGB )
                $h  = 2+($bl-$r)/$delta;
            else
                $h  = 4+($r-$g)/$delta;
        }
        else
            $h  = -1;
        $h  *= 60;
        if ( $h < 0 )
            $h  += 360;
        
        $s  = floor($s*100/255);
        $b  = floor($b*100/255);
        
        return array($h,$s,$b);
    }
}

// hues($num_hues, $start_hue, $end_hue)
//      - generates an array of stw_color's so that the range of hue values between
//        $start_hue and $end_hue are divided $num_hues times

class   stw_color_scheme {
    public      static function hues($num_hues, $start_hex) {
        if ( is_string($start_hex) )
            $start_hex  = new stw_color($start_hex);
        if ( (object)$start_hex != NULL && !is_a($start_hex, 'stw_color') )
            $start_hex  = new stw_color("#fff");
            
        $arr    = array();
        $hsb    = $start_hex->hsb();
        for ( $i = 0; $i < $num_hues; $i++ )
            array_push($arr, new stw_color($start_hex->get_hue(stw_color::MAX_HUE/$num_hues*$i*+1,1)));
            
        return $arr;
    }
    public      static function sats($num_sats, $start_hex) {
        if ( is_string($start_hex) )
            $start_hex  = new stw_color($start_hex);
        if ( (object)$start_hex != NULL && !is_a($start_hex, 'stw_color') )
            $start_hex  = new stw_color("#fff");
            
        $arr    = array();
        $hsb    = $start_hex->hsb();
        for ( $i = 0; $i < $num_sats+1; $i++ )
            array_push($arr, new stw_color($start_hex->get_sat(stw_color::MAX_SAT/$num_sats*$i*-1,1)));
            
        return $arr;
    }
    
    public      static function contrast( $color1, $color2 ) {
        
    }
    
    public      static function blackorwhite( $color1 ) {
        $arr    = $color1->rgb();
        $MAX_VAL        = 255*3;
        $color_val      = $arr[0]+$arr[1]+$arr[2];
        if ( $color_val > $MAX_VAL/2.0 )
            return "#000";
        else
            return "#fff";
    }
}

?>
<?php

function stw_color_hues_scheme( $code="") {
    $colors     = stw_color_scheme::hues(12,$code);
    ob_start();
    foreach ( $colors as $c ):
?>
<div style="background-color: <?php print $c; ?>; color: <?php print stw_color_scheme::blackorwhite($c); ?>; width: 100px; height: 100px; display:inline-block;"><?php print $c; ?></div><?php
    endforeach;
    $output     = ob_get_clean();
    return $output;
}

function stw_color_sats_scheme( $code="") {
    $colors     = stw_color_scheme::sats(11,$code);
    ob_start();
    foreach ( $colors as $c ):
?>
<div style="background-color: <?php print $c; ?>; color: <?php print stw_color_scheme::blackorwhite($c); ?>; width: 100px; height: 100px; display:inline-block;"><?php print $c; ?></div><?php
    endforeach;
    
    $output     = ob_get_clean();
    return $output;
}

