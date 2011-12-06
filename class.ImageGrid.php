<?

class ImageGrid {
	
	private $prefs;
	private $pixels;
	public $height;
	public $width;
	
    function __construct($prefs = array()) {
    
        $default_grid_prefs = array(
            'columns' => 3,
            'rows' => 2,
            'spacing_x' => 10,
            'spacing_y' => 6,
            'text_y' => 30,
            'image_width' => 48,
            'image_height' => 48,
            'padding' => 10,
            'margin_left' => 10,
            'margin_top' => 10,
            'show_labels' => true,
            'background_color' => '#000000',
            'font_path' => 'assets/fonts/'
        );

        $this->prefs = array_merge($default_grid_prefs, $prefs);

        if (!empty($this->prefs['padding'])) {
            $this->prefs['padding_left'] = $this->prefs['padding'];
            $this->prefs['padding_top'] = $this->prefs['padding'];
            $this->prefs['padding_right'] = $this->prefs['padding'];
            $this->prefs['padding_bottom'] = $this->prefs['padding'];
        }
    
		// Calculate element dimensions and positioning
        $this->prefs['image_left'] = $this->prefs['margin_left'] + $this->prefs['padding_left'];
        $this->prefs['image_top'] = $this->prefs['margin_top'] + $this->prefs['margin_left'];
    
        $this->prefs['total_spacing_y'] = $this->prefs['text_y'] + $this->prefs['spacing_y'];
    
        $this->width = (($this->prefs['image_width'] * $this->prefs['columns']) + ($this->prefs['spacing_x'] * ($this->prefs['columns'] - 1))) + $this->prefs['padding_left'] + $this->prefs['padding_right'];
        $total_vert_spacing = !empty($this->prefs['show_labels']) ? ($this->prefs['total_spacing_y'] * ($this->prefs['rows'] - 1)) + $this->prefs['text_y']: $this->prefs['spacing_y'] * $this->prefs['rows'] - 1;
        $this->height = ($this->prefs['image_height'] * $this->prefs['rows']) + $total_vert_spacing + $this->prefs['padding_top'];
        
        $this->pixels = array();
    	$this->pixels['transparent'] = new IMagickPixel('#ffffff');
        $this->pixels['transparent']->setcolorvalue(IMagick::COLOR_OPACITY, 1);        
        $this->pixels['white'] = new IMagickPixel('#ffffff');
        $this->pixels['black'] = new IMagickPixel('#000000');
        $this->pixels['background'] = new IMagickPixel($this->prefs['background_color']);     
    }
  
    public function __get($keyname) {
        if (isset($this->prefs[$keyname]))
            return $this->prefs[$keyname];
        return false;
    }
 
    public function annotate(&$canvas, $options = array()) {
        $draw = new IMagickDraw();
        $draw->setfontsize($options['font_size']);
        $draw->setfillcolor($options['font_color']);
        $draw->setfont($this->prefs['font_path'] . $options['font']);
        if (!empty($options['font_style']))
            $draw->setfontstyle($options['font_style']);   
    
        $offset_y = !empty($options['offset_y']) ? $options['offset_y'] : 0;
        $offset_x = !empty($options['offset_x']) ? $options['offset_x'] : 0;
        $angle = !empty($options['angle']) ? $options['angle'] : 0;
    
        $canvas->annotateimage($draw, $offset_x, $offset_y, $angle, $options['text']);
	}

    public function composite(&$target, &$source, $composite_type, $x_pos, $y_pos) {
        $source->borderimage($this->pixels['transparent'], $x_pos, 0);
        $target->compositeimage($source, $composite_type, 0, $y_pos);
    }

    public function write(&$canvas, $images_info = array()) {
        $grid_background = new IMagickDraw();
        $grid_background->setfillcolor($this->pixels['background']);
        $grid_background->roundrectangle($this->prefs['margin_left'], $this->prefs['margin_top'], $this->width + $this->prefs['margin_left'], $this->height + $this->prefs['margin_top'], 10, 10);
        $canvas->drawimage($grid_background);
        //$grid->blurimage(10, 90);
        //$grid_background->roundrectangle($this->prefs['margin_left'], $this->prefs['margin_top'], $this->width + $this->prefs['margin_left'], $this->height + $this->prefs['margin_top'], 10, 10);

    
        $verified_images = array();
        $verified_twats = array();
        
       // shuffle($images_info);
        
        foreach ($images_info as $image_info) {
            if (file_exists($image_info['image']) && filesize($image_info['image'])) {
                try {
                    $image = new IMagick($image_info['image']);
                }
                catch (Exception $e) {
                    print "Error instantiating " . $image_info['image'] . "\n"; 
                    print $e->getMessage() . "\n";
                    continue;
                }

                $verified_images[] = $image;
                $verified_twats[] = $image_info['label'];
            }
        }
    
        $ideal_grid_images = $this->prefs['rows'] * $this->prefs['columns'];
        $count = (count($verified_images) > $ideal_grid_images) ? $ideal_grid_images : count($verified_images);
        
        /*
            Composite columns, then add to grid
        */
        $column_objects = array();
        for ($j = 0; $j < $this->prefs['columns']; $j++) {
            $column_objects[] = new IMagick($this->prefs['image_path'] . 'blank.png');
        }
    
        for ($i = 0; $i < $count; $i++) {
            // go row-by-row
            $column_no = $i % $this->prefs['columns'];
    
            $row_no = floor($i / $this->prefs['columns']);
            $y_offset = (($this->prefs['image_height'] + $this->prefs['total_spacing_y']) * $row_no) + $this->prefs['image_top'];
   
            $image = $verified_images[$i];
    
            if ($image->getimagewidth() != $this->prefs['image_width'] ||
                $image->getimageheight() != $this->prefs['image_height']) {
                $image->scaleimage($this->prefs['image_width'], $this->prefs['image_height']);
            }
         
            $column_objects[$column_no]->compositeimage($image, IMagick::COMPOSITE_OVER, 0, $y_offset);
    
            $wrap_string_after = 888;
            $string_components = str_split('@' . $verified_twats[$i], $wrap_string_after);
            
            $part_offset = 10;
            //$angle = 0;
            foreach ($string_components as $string) {
            	$this->annotate($column_objects[$column_no], array(
                	'font_size' => '8',
                	'font_color' => '#000000',
                	'font' => 'verdanab.ttf',
                	'text' => $string,
                	'offset_y' => 1 + $part_offset + $y_offset + $this->prefs['image_height'] ,
                	'offset_x' => 0 + 1,
                	'angle' => 15
            	));        
            
                $this->annotate($column_objects[$column_no], array(
                	'font_size' => '8',
                	'font_color' => '#ffffff',
                	'font' => 'verdanab.ttf',
                	'text' => $string,
                	'offset_y' => $part_offset + $y_offset + $this->prefs['image_height'] ,
                	'offset_x' => 0,
                	'angle' => 15
            	));
            	//$angle += 90;
            	//$part_offset += 10; 
            }
     
        }
    
        for ($i = 0; $i < $this->prefs['columns']; $i++) {
            $x_offset = ($this->prefs['image_width'] + $this->prefs['spacing_x']) * $i + $this->prefs['image_left'];
    
			// this is to work around a spacing issue observed in graphicsmagick v. ?
            if ($x_offset)
                $column_objects[$i]->borderimage($this->pixels['transparent'], $x_offset, 0);
    
            $canvas->compositeimage($column_objects[$i], IMagick::COMPOSITE_ATOP, 0, 0);
        }
    }
}

?>
