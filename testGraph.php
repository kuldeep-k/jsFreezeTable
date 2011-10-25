<?php

$conn = new mysqli('localhost','root', 'root', 'thinkmusic');

$rs = $conn->query('show tables');
$tables = array();
while($row = $rs->fetch_row())
{
	$tables[] = $row[0];
}

$tables = array('action_lkp', 'song', 'song_action_xref', 'genre_lkp', 'sub_genre_lkp', 'sub_genre_lkp', 'artist_band_lkp', 'license_type_lkp', 'writer_lkp', 'skin_lkp', 'copyright_holder_lkp', 'label_lkp');
//$tables = array('action_lkp', 'song_action_xref');

$fk_structure = array();
$fld_structure = array();

$left_border = 0;
$right_border = 0;

$canvas = imagecreatetruecolor(2400, 800);
	$white = imagecolorallocate($canvas, 255, 255, 255);

	$black = imagecolorallocate($canvas, 0, 0, 0);
	imagefilledrectangle($canvas, 0, 0, 2400, 800, $white);
	//imagerectangle($canvas, 0, 0, 800, 800, $white);

foreach($tables as $table)
{
	
	$fk_structure[$table] = array();
	$rs = $conn->query('show create table '.$table);
	$row = $rs->fetch_row();
	$table_structure = explode(PHP_EOL, $row[1]);
	
	foreach($table_structure as $table_name => $table_structure_row)
	{
		if(preg_match('/FOREIGN/', $table_structure_row))
		{
			$table_structure_row_array = explode('CONSTRAINT', $table_structure_row);
			//echo '<pre>';
			//print_r(preg_split('/[CONSTRAINTS]*[FOREIGN KEY]*[REFERENCES]/', $table_structure_row, null, PREG_SPLIT_NO_EMPTY));
			//print_r($table_structure_row_array);
			$table_structure_row_array1 = explode('FOREIGN KEY', $table_structure_row_array[1]);
			
			$fk_name = replace_s($table_structure_row_array1[0]);
			$fk_structure[$table][$fk_name] = array();
			$table_structure_row_array2 = explode('REFERENCES', $table_structure_row_array1[1]);
			$fk_source_field_name = replace_s($table_structure_row_array2[0]);
			$fk_structure[$table][$fk_name]['source_field_name'] = $fk_source_field_name;

			$table_structure_row_array3 = explode(' ', trim($table_structure_row_array2[1]));
			$fk_dest_field_name = replace_s($table_structure_row_array3[1]);
			$fk_structure[$table][$fk_name]['dest_field_name'] = $fk_dest_field_name;
			$fk_dest_table_name = replace_s($table_structure_row_array3[0]);
			$fk_structure[$table][$fk_name]['dest_table_name'] = $fk_dest_table_name;	
			//print_r($table_structure_row_array3);
			//echo '</pre>';
			//echo 'sssssss';

			
			
		}
	}

}
/*	echo '<pre>';
	print_r($fk_structure);
	echo '</pre>';

die;
*/

	/**
Array
(
    [song_action_xref] => Array
        (
            [fk_sa_ac] => Array
                (
                    [source_field_name] => action_id
                    [dest_field_name] => action_id
                    [dest_table_name] => action_lkp
                )

            [fk_sa_sng] => Array
                (
                    [source_field_name] => song_id
                    [dest_field_name] => song_id
                    [dest_table_name] => song
                )

        )

)

	*/	
	

	foreach($fk_structure as $table_name => $const)
	{
		$rs1 = $conn->query('describe '.$table_name);
		$height = ($rs1->num_rows * 21) + 80; 

		imagerectangle($canvas, $left_border + 50, 50, $right_border + 200, 80, $black);

		imagerectangle($canvas, $left_border +  50, 50, $right_border + 200, $height, $black);
		//imagestring($canvas, 52, 52, 5,  $table_name, $black);
		$font = '/usr/share/fonts/truetype/freefont/FreeMono.ttf';

		imagettftext($canvas, 9, 0, $left_border +  55, 65, $black, $font, $table_name);

		$fld_structure[$table_name]  = array();
		$initial = 95;
		while($row1 = $rs1->fetch_row())
		{
		//print_r($row);
		//echo '<br/>';	
			$fld_structure[$table_name][$row1[0]] = array($left_border +  55, $initial);
			imagettftext($canvas, 9, 0, $left_border +  55, $initial, $black, $font, $row1[0]);
			$initial += 20;
		}
		//die;
		

		
		$left_border += 200; 
		$right_border += 200; 
	}

foreach($fk_structure as $table_name => $flds)
{
	foreach($flds as $fk_key => $flds)
	{
		if(!empty($flds['source_field_name']))
		{
			$arrow_src_coords = $fld_structure[$table_name][$flds['source_field_name']];
			$arrow_dest_coords = $fld_structure[$flds['dest_table_name']][$flds['dest_field_name']];
			arrow($canvas, $arrow_src_coords[0], $arrow_src_coords[1], $arrow_dest_coords[0], $arrow_dest_coords[1], 10, 5, $black);	
		}
	}

}


header('content-type: image/png');
		imagepng($canvas);
		imagedestroy($canvas);


function arrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));

    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

    $k = $awidth / $alength;

    $x2o = $x2 - $dx;
    $y2o = $dy - $y2;

    $x3 = $y2o * $k + $dx;
    $y3 = $x2o * $k + $dy;

    $x4 = $dx - $y2o * $k;
    $y4 = $dy - $x2o * $k;

    imageline($im, $x1, $y1, $dx, $dy, $color);
    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
}

function replace_s($str)
{
	return trim(str_replace(')', '', str_replace('(', '', str_replace('`', '', $str))));
}
?>
